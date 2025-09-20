<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\PaymentItemEntry;
use App\Models\PartyList;
use App\Models\Pump;
use App\Models\PumpUser;
use App\Models\Nozzle;
use App\Models\PaymentItem;
use App\Models\PaymentEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->user_type === 'super_admin') {
            $loans = Loan::with(['paymentEntries', 'pump'])->get();
            $pumps = Pump::where('status', 'active')->get();
            // Get all active payment items for super admin
            $paymentItems = Payment::select('id', 'item_name as name', 'item_desc')
                ->where('status', 'active')
                ->orderBy('item_name')
                ->get();
        } else {
            // For admin and operator users, only show loans for their assigned pumps
            $loans = Loan::join('pumps', 'loans.pump_id', '=', 'pumps.id')
                ->join('pump_users', 'pumps.id', '=', 'pump_users.pump_id')
                ->where('pump_users.email', $user->email)
                ->where('pump_users.status', 'active')
                ->where('pumps.status', 'active')
                ->select('loans.*')
                ->with(['paymentEntries', 'pump'])
                ->get();

            // For admin users, only show their assigned pumps
            $pumps = Pump::join('pump_users', 'pumps.id', '=', 'pump_users.pump_id')
                ->where('pump_users.email', $user->email)
                ->where('pump_users.status', 'active')
                ->where('pumps.status', 'active')
                ->select('pumps.*')
                ->get();

            // Get payment items only for the user's assigned pumps
            $paymentItems = Payment::select('id', 'item_name as name', 'item_desc')
                ->where('status', 'active')
                ->whereIn('petrol_pump', function($query) use ($user) {
                    $query->select('pump_id')
                        ->from('pump_users')
                        ->where('email', $user->email)
                        ->where('status', 'active');
                })
                ->orderBy('item_name')
                ->get();
        }

        // Get payment entries
        $paymentEntries = PaymentItemEntry::with(['paymentItem'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('loans.index', compact('loans', 'pumps', 'paymentItems', 'paymentEntries'));
    }

    public function create()
    {
        $user = Auth::user();

        if ($user->user_type === 'super_admin') {
            $pumps = Pump::where('status', 'active')->get();
        } else {
            // For admin users, only show their assigned pumps
            $pumps = Pump::join('pump_users', 'pumps.id', '=', 'pump_users.pump_id')
                ->where('pump_users.email', $user->email)
                ->where('pump_users.status', 'active')
                ->where('pumps.status', 'active')
                ->select('pumps.*')
                ->get();
        }

        return view('loans.create', compact('pumps'));
    }

    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'company' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'phone_no' => 'required|string|size:10|regex:/^[0-9]{10}$/',
                'pump_id' => 'required|exists:pumps,id'
            ]);

            // Verify user has access to the selected pump
            $user = Auth::user();
            if ($user->user_type !== 'super_admin') {
                $pump = Pump::join('pump_users', 'pumps.id', '=', 'pump_users.pump_id')
                    ->where('pump_users.email', $user->email)
                    ->where('pump_id', $request->pump_id)
                    ->first();

                if (!$pump) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have access to this pump.'
                        ], 403);
                    }
                    return redirect()->back()->with('error', 'You do not have access to this pump.')->withInput();
                }
            }

            // Start a database transaction for synchronized creation
            DB::beginTransaction();

            // Create the loan
            $loan = Loan::create($validated);
            
            // Load the pump relationship for the response
            $loan->load('pump');

            // Automatically create corresponding Party List entry
            \App\Models\PartyList::create([
                'group_type' => 'Credit Sale',
                'ledger_section' => $validated['company'], // Use company name as ledger section
                'status' => 'active',
                'created_by' => Auth::id(),
                'edited_by' => Auth::id()
            ]);

            DB::commit();

            // Handle AJAX request
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Loan created successfully. A corresponding Party List entry has also been added.',
                    'loan' => $loan
                ]);
            }

            // Redirect to index page with success message
            return redirect()->route('loans.index')->with([
                'success' => 'Loan created successfully. A corresponding Party List entry has also been added.',
                'action' => 'create'
            ]);
        } catch (ValidationException $e) {
            // Handle validation errors
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed. Please check your input.',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            // Log the error
            Log::error('Loan creation failed: ' . $e->getMessage());

            // Handle AJAX request
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create loan. Please try again.'
                ], 500);
            }

            // Redirect back with error message
            return redirect()->back()->with('error', 'Failed to create loan. Please try again.')->withInput();
        }
    }

    public function show(Loan $loan)
    {
        $user = Auth::user();

        // Check if user has access to this loan
        if ($user->user_type !== 'super_admin' &&
            (!$loan->pump || !$loan->pump->pumpUsers->contains('email', $user->email))) {
            return redirect()->route('loans.index')->with('error', 'You do not have permission to view this loan.');
        }

        // Get payment items for this specific pump
        $paymentItems = Payment::select('id', 'item_name as name', 'item_desc')
            ->where('status', 'active')
            ->where('petrol_pump', $loan->pump_id)
            ->orderBy('item_name')
            ->get();

        return view('loans.show', compact('loan', 'paymentItems'));
    }

    public function edit(Loan $loan)
    {
        $user = Auth::user();

        // Check if user has access to this loan
        if ($user->user_type !== 'super_admin') {
            $hasAccess = Pump::join('pump_users', 'pumps.id', '=', 'pump_users.pump_id')
                ->where('pump_users.email', $user->email)
                ->where('pumps.id', $loan->pump_id)
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('loans.index')->with('error', 'You do not have access to this loan.');
            }
        }

        if ($user->user_type === 'super_admin') {
            $pumps = Pump::where('status', 'active')->get();
        } else {
            // For admin users, only show their assigned pumps
            $pumps = Pump::join('pump_users', 'pumps.id', '=', 'pump_users.pump_id')
                ->where('pump_users.email', $user->email)
                ->where('pump_users.status', 'active')
                ->where('pumps.status', 'active')
                ->select('pumps.*')
                ->get();
        }

        return view('loans.edit', compact('loan', 'pumps'));
    }

    public function update(Request $request, Loan $loan)
    {
        // Check if user has access to this loan
        $user = Auth::user();
        if ($user->user_type !== 'super_admin') {
            $hasAccess = Pump::join('pump_users', 'pumps.id', '=', 'pump_users.pump_id')
                ->where('pump_users.email', $user->email)
                ->where('pumps.id', $loan->pump_id)
                ->exists();

            if (!$hasAccess) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to this loan.'
                    ], 403);
                }
                return redirect()->route('loans.index')->with('error', 'You do not have access to this loan.');
            }
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:200|regex:/^[a-zA-Z\s]+$/',
                'company' => 'required|string|max:200|regex:/^[a-zA-Z\s]+$/',
                'address' => 'required|string|max:200',
                'phone_no' => 'required|string|size:10|regex:/^[0-9]{10}$/',
                'pump_id' => 'required|exists:pumps,id'
            ]);

            // Verify user has access to the selected pump
            if ($user->user_type !== 'super_admin') {
                $pump = Pump::join('pump_users', 'pumps.id', '=', 'pump_users.pump_id')
                    ->where('pump_users.email', $user->email)
                    ->where('pump_id', $request->pump_id)
                    ->first();

                if (!$pump) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You do not have access to this pump.'
                        ], 403);
                    }
                    return redirect()->back()->with('error', 'You do not have access to this pump.')->withInput();
                }
            }

            // Start a database transaction for synchronized update
            DB::beginTransaction();

            $loan->update($validated);
            
            // Load the pump relationship for the response
            $loan->load('pump');

            // Update or create corresponding Party List entry
            // First, try to find an existing party list entry that might correspond to this loan
            // We'll look for Credit Sale entries with the same company name
            $existingPartyList = PartyList::where('group_type', 'Credit Sale')
                ->where('ledger_section', $loan->company)
                ->first();

            if ($existingPartyList) {
                // Update existing party list entry
                $existingPartyList->update([
                    'ledger_section' => $validated['company'],
                    'edited_by' => Auth::id()
                ]);
            } else {
                // Create new party list entry if none exists
                PartyList::create([
                    'group_type' => 'Credit Sale',
                    'ledger_section' => $validated['company'],
                    'status' => 'active',
                    'created_by' => Auth::id(),
                    'edited_by' => Auth::id()
                ]);
            }

            DB::commit();

            // Handle AJAX request
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Loan updated successfully. Corresponding Party List entry has been synchronized.',
                    'loan' => $loan
                ]);
            }

            return redirect()->route('loans.index')->with([
                'success' => 'Loan updated successfully. Corresponding Party List entry has been synchronized.',
                'action' => 'edit'
            ]);
        } catch (ValidationException $e) {
            // Handle validation errors
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed. Please check your input.',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            Log::error('Loan update failed: ' . $e->getMessage());

            // Handle AJAX request
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update loan. Please try again.'
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to update loan. Please try again.')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $loan = Loan::findOrFail($id);

            // Start a database transaction for synchronized deletion
            DB::beginTransaction();

            // Find and delete corresponding party list entries
            // Look for Credit Sale entries with the same company name
            $correspondingPartyLists = PartyList::where('group_type', 'Credit Sale')
                ->where('ledger_section', $loan->company)
                ->get();

            // Delete the loan
            $loan->delete();

            // Delete corresponding party list entries if they exist
            foreach ($correspondingPartyLists as $partyList) {
                $partyList->delete();
            }

            DB::commit();

            $message = 'Loan deleted successfully.';
            if ($correspondingPartyLists->count() > 0) {
                $message .= ' Corresponding Party List entries have also been removed.';
            }

            return redirect()->route('loans.index')->with([
                'success' => $message,
                'action' => 'delete'
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollBack();

            Log::error('Loan deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete the loan. Please try again.');
        }
    }

    public function searchCustomers(Request $request)
    {
        $search = $request->get('q') ?? $request->get('search');
        $customers = Loan::where('name', 'like', "%{$search}%")
            ->orWhere('company', 'like', "%{$search}%")
            ->orWhere('phone_no', 'like', "%{$search}%")
            ->select('id', 'name', 'company', 'phone_no')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

    public function payment(Request $request, Loan $loan)
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0',
                'payment_method' => 'required|string'
            ]);

            // Get the payment item
            $paymentItem = Payment::where('item_name', $validated['payment_method'])->first();
            if (!$paymentItem) {
                throw new \Exception('Payment method not found');
            }

            // Calculate total amount from payment entries
            $totalAmount = $loan->paymentEntries->where('amount', '>', 0)->sum('amount') - abs($loan->paymentEntries->where('amount', '<', 0)->sum('amount'));

            // Validate payment amount doesn't exceed total amount
            if ($validated['amount'] > $totalAmount) {
                throw new \Exception('Payment amount cannot exceed total amount');
            }

            // Get the first nozzle from the loan's payment entries
            $firstEntry = $loan->paymentEntries->where('amount', '>', 0)->first();
            if (!$firstEntry) {
                throw new \Exception('No nozzle found for this loan');
            }

            // Create payment entry
            PaymentItemEntry::create([
                'customer_name' => $loan->name,
                'amount' => -$validated['amount'], // Negative amount for payments
                'payment_item_id' => $paymentItem->id,
                'bill_no' => $firstEntry->bill_no,
                'nozzle_id' => $firstEntry->nozzle_id,
                'litre' => 0,
                'rate' => 0,
                'driver_name' => '',
                'vehicle_number' => '',
                'vehicle_name' => ''
            ]);

            return redirect()->route('loans.show', $loan)->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            Log::error('Payment failed: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
