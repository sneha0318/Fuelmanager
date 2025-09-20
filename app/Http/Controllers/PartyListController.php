<?php

namespace App\Http\Controllers;

use App\Models\PartyList;
use App\Models\Pump;
use App\Models\Loan;
use App\Models\PaymentItem;
use App\Models\Payment;
use App\Models\PaymentItemEntry;
use App\Models\ExpensesCategory;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PartyListController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get party lists based on user type
        if ($user->user_type === 'super_admin') {
            // Super admin can see all party lists including expense entries
            $partyLists = PartyList::with(['creator', 'editor', 'loan.paymentEntries', 'expense.category'])
                ->whereIn('group_type', ['Credit Sale', 'Payment', 'Expense'])
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            // For admin and operator users, filter by their assigned pumps
            $userPumpIds = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('status', 'active')
                ->pluck('pump_id')
                ->toArray();

            if (empty($userPumpIds)) {
                // If user has no assigned pumps, return empty collection
                $partyLists = collect();
            } else {
                // Get all party lists first, then filter in PHP since we don't have direct foreign keys
                $allPartyLists = PartyList::with(['creator', 'editor', 'loan.paymentEntries', 'expense.category'])
                    ->whereIn('group_type', ['Credit Sale', 'Payment', 'Expense'])
                    ->orderBy('created_at', 'asc')
                    ->get();

                // Filter based on user's pump access
                $partyLists = $allPartyLists->filter(function($partyList) use ($userPumpIds, $user) {
                    // Check Credit Sale (loan) access
                    if ($partyList->group_type === 'Credit Sale') {
                      
                        $loan = $partyList->loan;
                        if (!$loan) {
                            $loan = Loan::where('company', $partyList->ledger_section)->first();
                        }
                        if (!$loan) {
                            $loan = Loan::whereRaw('LOWER(company) = LOWER(?)', [$partyList->ledger_section])->first();
                        }
                        
                        if ($loan && in_array($loan->pump_id, $userPumpIds)) {
                            return true;
                        }
                    }

                    // Check Payment access
                    if ($partyList->group_type === 'Payment') {
                        $payment = $partyList->getPaymentInstance(); // Use the custom method
                        if ($payment && in_array($payment->petrol_pump, $userPumpIds)) {
                            return true;
                        }
                    }

                    // Check Expense access
                    if ($partyList->group_type === 'Expense') {
                        // Primary check: if user created the party list entry, always show it
                        if ($partyList->created_by === $user->id) {
                            return true;
                        }
                        
                        // Secondary check: if user created the expense, show it
                        if ($partyList->expense && $partyList->expense->created_by === $user->id) {
                            return true;
                        }
                        
                        // Third check: if expense category belongs to user's pumps
                        if ($partyList->expense && $partyList->expense->category) {
                            $categoryPumpId = $partyList->expense->category->pump_id;
                            if ($categoryPumpId && in_array($categoryPumpId, $userPumpIds)) {
                                return true;
                            }
                            // Also include expenses from categories with no specific pump assigned
                            if (!$categoryPumpId) {
                                return true;
                            }
                        }
                        
                        // Fallback: if no expense relationship but user created the party list entry
                        if (!$partyList->expense && $partyList->created_by === $user->id) {
                            return true;
                        }
                    }

                    return false;
                });
            }
        }

        // Get pumps based on user type
        if ($user->user_type === 'super_admin') {
            $pumps = Pump::where('status', 'active')->orderBy('pump_name')->get();
        } else {
            // For admin and operator users, only show their assigned pumps
            $pumps = Pump::join('pump_users', 'pumps.id', '=', 'pump_users.pump_id')
                ->where('pump_users.email', $user->email)
                ->where('pump_users.status', 'active')
                ->where('pumps.status', 'active')
                ->select('pumps.*')
                ->orderBy('pumps.pump_name')
                ->get();
        }

        // Get expense categories for the dropdown
        if ($user->user_type === 'super_admin') {
            $expenseCategories = ExpensesCategory::with('pump')->orderBy('category_name')->get();
        } else {
            // For admin users, only show categories from their assigned pumps or categories they created
            $userPumpIds = DB::table('pump_users')
                ->where('email', $user->email)
                ->where('status', 'active')
                ->pluck('pump_id')
                ->toArray();

            $expenseCategories = ExpensesCategory::with('pump')->where(function($query) use ($userPumpIds, $user) {
                $query->whereIn('pump_id', $userPumpIds)
                      ->orWhere('created_by', $user->id)
                      ->orWhereNull('pump_id'); // Include categories not assigned to any pump
            })->orderBy('category_name')->get();
        }

        return view('party-list.index', compact('partyLists', 'pumps', 'expenseCategories'));
    }

    public function store(Request $request)
    {
        // Handle validation based on whether it's a new category or existing one
        if ($request->group_type === 'Expenses' && $request->expense_category_id === 'add_new') {
            $request->validate([
                'group_type' => 'required|string|max:255',
                'new_category_name' => 'required|string|max:200|regex:/^[A-Za-z0-9\s]+$/',
                'new_category_pump_id' => 'required|exists:pumps,id',
            ]);
        } else {
            $request->validate([
                'group_type' => 'required|string|max:255',
                'expense_category_id' => 'required_if:group_type,Expenses|exists:expenses_categories,id',
            ]);
        }

        try {
            $user = Auth::user();
            DB::beginTransaction();

            // Handle Expenses group type differently - only create category, not party list entry
            if ($request->group_type === 'Expenses') {
                if ($request->expense_category_id === 'add_new') {
                    // Check if user has permission to create categories for this pump
                    if ($user->user_type !== 'super_admin') {
                        $userPumpIds = DB::table('pump_users')
                            ->where('email', $user->email)
                            ->where('status', 'active')
                            ->pluck('pump_id')
                            ->toArray();

                        if (!in_array($request->new_category_pump_id, $userPumpIds)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'You do not have permission to create categories for this pump.'
                            ], 403);
                        }
                    }

                    // Check if category with this name already exists
                    $existingCategory = ExpensesCategory::where('category_name', $request->new_category_name)
                        ->where('pump_id', $request->new_category_pump_id)
                        ->first();
                    
                    if ($existingCategory) {
                        return response()->json([
                            'success' => false,
                            'message' => 'A category with this name already exists for the selected pump.'
                        ], 422);
                    }

                    // Create new expense category
                    $expenseCategory = ExpensesCategory::create([
                        'category_name' => $request->new_category_name,
                        'pump_id' => $request->new_category_pump_id,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Expense category created successfully!',
                        'new_category' => [
                            'id' => $expenseCategory->id,
                            'category_name' => $expenseCategory->category_name,
                            'pump_id' => $expenseCategory->pump_id
                        ],
                        'show_expense_form' => true
                    ]);
                } else {
                    // For existing categories, just return the category info to show expense form
                    $expenseCategory = ExpensesCategory::find($request->expense_category_id);
                    if (!$expenseCategory) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Expense category not found.'
                        ], 404);
                    }

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'message' => 'Ready to add expenses to this category!',
                        'selected_category' => [
                            'id' => $expenseCategory->id,
                            'category_name' => $expenseCategory->category_name,
                            'pump_id' => $expenseCategory->pump_id
                        ],
                        'show_expense_form' => true
                    ]);
                }
            }

            // For non-expense types, continue with normal party list creation
            $ledgerSection = $request->group_type . ' - ' . now()->format('Y-m-d H:i:s');

            $partyList = PartyList::create([
                'group_type' => $request->group_type,
                'ledger_section' => $ledgerSection,
                'status' => 'active',
                'created_by' => Auth::id(),
                'edited_by' => Auth::id()
            ]);

            DB::commit();

            // Reload the party list with relationships
            $partyList = PartyList::with(['creator', 'editor', 'loan.paymentEntries'])->find($partyList->id);

            return response()->json([
                'success' => true,
                'message' => 'Party list created successfully!',
                'data' => $partyList
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating party list: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeWithLoan(Request $request)
    {
        $request->validate([
            'group_type' => 'required|string|max:255',
            'loan_name' => 'required|string|max:255',
            'loan_company' => 'required|string|max:255',
            'loan_pump_id' => 'required|exists:pumps,id',
            'loan_phone_no' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            'loan_address' => 'required|string',
        ]);

        try {
            $user = Auth::user();

            // Check if user has permission to create loans for this pump
            if ($user->user_type !== 'super_admin') {
                $userPumpIds = DB::table('pump_users')
                    ->where('email', $user->email)
                    ->where('status', 'active')
                    ->pluck('pump_id')
                    ->toArray();

                if (!in_array($request->loan_pump_id, $userPumpIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to create loans for this pump.'
                    ], 403);
                }
            }

            DB::beginTransaction();

            // Create party list first
            $partyList = PartyList::create([
                'group_type' => $request->group_type,
                'ledger_section' => $request->loan_company, // Use loan_company as ledger_section
                'status' => 'active',
                'created_by' => Auth::id(),
                'edited_by' => Auth::id()
            ]);

            // Create loan if group type is Credit Sale
            if ($request->group_type === 'Credit Sale') {
                $loan = Loan::create([
                    'name' => $request->loan_name,
                    'company' => $request->loan_company,
                    'pump_id' => $request->loan_pump_id,
                    'address' => $request->loan_address,
                    'phone_no' => $request->loan_phone_no,
                    'created_by' => Auth::id()
                ]);
            }

            DB::commit();

            $message = 'Party list created successfully!';
            if (isset($loan)) {
                $message .= ' Loan for ' . $request->loan_name . ' has also been created.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'party_list' => $partyList,
                    'loan' => $loan ?? null
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating party list and loan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeWithPayment(Request $request)
    {
        $request->validate([
            'group_type' => 'required|string|max:255',
            'payment_item_name' => 'required|string|max:255',
            'payment_pump_id' => 'required|exists:pumps,id',
        ]);

        try {
            $user = Auth::user();

            // Check if user has permission to create payments for this pump
            if ($user->user_type !== 'super_admin') {
                $userPumpIds = DB::table('pump_users')
                    ->where('email', $user->email)
                    ->where('status', 'active')
                    ->pluck('pump_id')
                    ->toArray();

                if (!in_array($request->payment_pump_id, $userPumpIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to create payments for this pump.'
                    ], 403);
                }
            }

            DB::beginTransaction();

            // Get pump name for unique ledger section
            $pump = Pump::find($request->payment_pump_id);
            $pumpName = $pump ? $pump->pump_name : 'Pump ' . $request->payment_pump_id;

            // Create party list first with unique ledger section
            $partyList = PartyList::create([
                'group_type' => $request->group_type,
                'ledger_section' => $request->payment_item_name . ' (' . $pumpName . ')', // Include pump name for uniqueness
                'status' => 'active',
                'created_by' => Auth::id(),
                'edited_by' => Auth::id()
            ]);

            // Create payment if group type is Payment
            if ($request->group_type === 'Payment') {
                $payment = Payment::create([
                    'item_name' => $request->payment_item_name,
                    'petrol_pump' => $request->payment_pump_id,
                    'status' => 'active', // Default to active status
                    'created_on' => now(),
                    'edited_on' => now(),
                    'created_by' => Auth::id(),
                    'edited_by' => Auth::id()
                ]);
            }

            DB::commit();

            $message = 'Party list created successfully!';
            if (isset($payment)) {
                $message .= ' Payment item "' . $request->payment_item_name . '" has also been created.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'party_list' => $partyList,
                    'payment' => $payment ?? null
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating party list and payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeWithExpense(Request $request)
    {
        $request->validate([
            'group_type' => 'required|string|max:255',
            'expense_category' => 'required|string|max:255',
            'expense_pump_id' => 'required|exists:pumps,id',
        ]);

        try {
            $user = Auth::user();

            // Only allow admin and super_admin to create expenses
            if ($user->user_type === 'operator') {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create expense categories.'
                ], 403);
            }

            // Check if user has permission to create expenses for this pump
            if ($user->user_type !== 'super_admin') {
                $userPumpIds = DB::table('pump_users')
                    ->where('email', $user->email)
                    ->where('status', 'active')
                    ->pluck('pump_id')
                    ->toArray();

                if (!in_array($request->expense_pump_id, $userPumpIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to create expenses for this pump.'
                    ], 403);
                }
            }

            DB::beginTransaction();

            // Get pump name for consistent naming
            $pump = Pump::find($request->expense_pump_id);
            $pumpName = $pump ? $pump->pump_name : 'Pump ' . $request->expense_pump_id;

            // Use consistent naming format: "CategoryName (PumpName)"
            $categoryDisplayName = $request->expense_category . ' (' . $pumpName . ')';

            // Create party list
            $partyList = PartyList::create([
                'group_type' => $request->group_type,
                'ledger_section' => $categoryDisplayName,
                'status' => 'active',
                'created_by' => Auth::id(),
                'edited_by' => Auth::id()
            ]);

            DB::commit();

            $message = 'Party list created successfully!';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'party_list' => $partyList
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error creating party list and expense category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'group_type' => 'required|string|max:255',
            'ledger_section' => 'required|string|max:255',
        ]);

        try {
            $user = Auth::user();
            $partyList = PartyList::with(['loan'])->findOrFail($id);

            // Check if user has permission to update this party list
            if ($user->user_type !== 'super_admin') {
                $userPumpIds = DB::table('pump_users')
                    ->where('email', $user->email)
                    ->where('status', 'active')
                    ->pluck('pump_id')
                    ->toArray();

                $hasAccess = false;

                if ($partyList->loan && in_array($partyList->loan->pump_id, $userPumpIds)) {
                    $hasAccess = true;
                } elseif ($partyList->group_type === 'Payment') {
                    $payment = $partyList->getPaymentInstance(); // Use custom method
                    if ($payment && in_array($payment->petrol_pump, $userPumpIds)) {
                        $hasAccess = true;
                    }
                }

                if (!$hasAccess) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to update this party list.'
                    ], 403);
                }
            }

            $partyList->update([
                'group_type' => $request->group_type,
                'ledger_section' => $request->ledger_section,
                'edited_by' => Auth::id()
            ]);

            // Reload the party list with relationships
            $partyList = PartyList::with(['creator', 'editor', 'loan.paymentEntries'])->find($partyList->id);

            return response()->json([
                'success' => true,
                'message' => 'Party list updated successfully!',
                'data' => $partyList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating party list: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $partyList = PartyList::with(['creator', 'editor'])->findOrFail($id);

            // Check if this is a Credit Sale
            if ($partyList->group_type === 'Credit Sale') {
                // Try to find the associated loan - first by the relationship, then manually
                $loan = $partyList->loan;
                
                // If the relationship doesn't work, try to find manually
                if (!$loan) {
                    $loan = Loan::where('company', $partyList->ledger_section)->first();
                }
                
                // If still no loan found, try case-insensitive search
                if (!$loan) {
                    $loan = Loan::whereRaw('LOWER(company) = LOWER(?)', [$partyList->ledger_section])->first();
                }

                if ($loan) {
                    // Load the necessary relationships for the loan
                    $loan->load(['paymentEntries.nozzle', 'paymentEntries.paymentItem', 'pump.pumpUsers']);

                    // Check user permissions
                    $user = Auth::user();
                    if ($user->user_type !== 'super_admin' && $loan->pump) {
                        // Check if user has access to this pump (active pump user relationship)
                        $userPumpIds = DB::table('pump_users')
                            ->where('email', $user->email)
                            ->where('status', 'active')
                            ->pluck('pump_id')
                            ->toArray();
                        
                        if (!in_array($loan->pump_id, $userPumpIds)) {
                            return redirect()->route('party-list.index')->with('error', 'You do not have permission to view this loan details.');
                        }
                    }

                    // Get payment items for the payment form
                    // Payment items are general and not pump-specific, so show all active items
                    $paymentItems = PaymentItem::where('status', 'active')->get();

                    return view('loans.show', compact('loan', 'paymentItems'))->with('fromPartyList', true);
                } else {
                    return redirect()->route('party-list.index')->with('error', 'No loan found for this Credit Sale entry. The loan data may have been deleted or there may be a data mismatch.');
                }
            } else {
                // For non-credit sales, redirect back with a message
                return redirect()->route('party-list.index')->with('error', 'This party list does not have associated loan details.');
            }
        } catch (\Exception $e) {
            return redirect()->route('party-list.index')->with('error', 'An error occurred while loading the loan details.');
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();

            DB::beginTransaction();

            $partyList = PartyList::with(['loan'])->findOrFail($id);

            // Check if user has permission to delete this party list
            if ($user->user_type !== 'super_admin') {
                $userPumpIds = DB::table('pump_users')
                    ->where('email', $user->email)
                    ->where('status', 'active')
                    ->pluck('pump_id')
                    ->toArray();

                $hasAccess = false;

                // Check Credit Sale (loan) access
                if ($partyList->group_type === 'Credit Sale') {
                    $loan = $partyList->getLoanInstance(); // Use the custom method for robust loan finding
                    if ($loan && in_array($loan->pump_id, $userPumpIds)) {
                        $hasAccess = true;
                    }
                } elseif ($partyList->group_type === 'Payment') {
                    $payment = $partyList->getPaymentInstance(); // Use custom method
                    if ($payment && in_array($payment->petrol_pump, $userPumpIds)) {
                        $hasAccess = true;
                    }
                } elseif ($partyList->group_type === 'Expense' && $partyList->expense_id) {
                    // Check expense access - user can delete if they have access to the expense's category pump
                    $expense = Expense::with('category')->find($partyList->expense_id);
                    if ($expense && $expense->category) {
                        // If expense category has a pump_id, check if user has access to it
                        if ($expense->category->pump_id && in_array($expense->category->pump_id, $userPumpIds)) {
                            $hasAccess = true;
                        } elseif (!$expense->category->pump_id) {
                            // If category has no pump assigned, allow access
                            $hasAccess = true;
                        }
                    } elseif ($expense && !$expense->category) {
                        // If expense has no category, allow access
                        $hasAccess = true;
                    }
                }

                if (!$hasAccess) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to delete this party list.'
                    ], 403);
                }
            }

            // Track what gets deleted for the success message
            $deletedLoan = false;
            $deletedPayment = false;

            // If this is a Credit Sale type party list, also delete the associated loan
            if ($partyList->group_type === 'Credit Sale') {
                $loan = $partyList->getLoanInstance(); // Use the custom method for robust loan finding
                if ($loan) {
                    Log::info('Deleting associated loan with party list', [
                        'party_list_id' => $partyList->id,
                        'loan_id' => $loan->id,
                        'loan_company' => $loan->company
                    ]);
                    $loan->delete();
                    $deletedLoan = true;
                }
            }

            // If this is a Payment type party list, also delete the associated payment
            if ($partyList->group_type === 'Payment') {
                $payment = $partyList->getPaymentInstance(); // Use custom method
                if ($payment) {
                    $payment->delete();
                    $deletedPayment = true;
                }
            }

            // Track what gets deleted for the success message
            $deletedExpense = false;

            // If this is an Expense type party list, also delete the associated expense
            if ($partyList->group_type === 'Expense' && $partyList->expense_id) {
                $expense = Expense::find($partyList->expense_id);
                if ($expense) {
                    Log::info('Deleting associated expense with party list', [
                        'party_list_id' => $partyList->id,
                        'expense_id' => $expense->id,
                        'expense_name' => $expense->name
                    ]);
                    // Delete the expense - this will automatically cascade delete the party list
                    $expense->delete();
                    $deletedExpense = true;
                    
                    // Party list will be automatically deleted by foreign key constraint
                    // So we don't need to delete it manually
                } else {
                    // If expense doesn't exist but party list does, delete the orphaned party list
                    $partyList->delete();
                }
            } else {
                // For non-expense party lists, delete normally
                $partyList->delete();
            }

            DB::commit();

            // Build the success message based on what was deleted
            $message = 'Party list deleted successfully!';
            if ($deletedLoan) {
                $message .= ' The associated loan has also been removed from Loan Management.';
            } elseif ($deletedPayment) {
                $message .= ' The associated payment has also been removed.';
            } elseif ($deletedExpense) {
                $message .= ' The associated expense has also been removed from Expense Management.';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting party list: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show payment amounts grouped by payment type
     */
    public function showPaymentTypeDetails(Request $request, $paymentType)
    {
        try {
            $user = Auth::user();

            // Get all payments with this payment type (item_name) that user has access to
            $query = Payment::where('item_name', $paymentType);

            // For non-super admin users, filter by accessible pumps
            if ($user->user_type !== 'super_admin') {
                $userPumpIds = DB::table('pump_users')
                    ->where('email', $user->email)
                    ->where('status', 'active')
                    ->pluck('pump_id')
                    ->toArray();

                $query->whereIn('petrol_pump', $userPumpIds);
            }

            $payments = $query->get(); // Get all accessible payments with this name

            if ($payments->isEmpty()) {
                return redirect()->route('party-list.index')->with('error', 'Payment type not found.');
            }

            // Get all payment IDs for this payment type
            $paymentIds = $payments->pluck('id')->toArray();

            // Get date filter from request or default to today
            $selectedDate = $request->get('date');

            // Default to today's date in the configured timezone if no date is provided
            if (!$selectedDate) {
                $selectedDate = Carbon::now(config('app.timezone'))->format('Y-m-d');
            }

            // Validate and format date if provided
            if ($selectedDate) {
                try {
                    // HTML date input sends YYYY-MM-DD format
                    $selectedDate = Carbon::createFromFormat('Y-m-d', $selectedDate)->format('Y-m-d');
                } catch (\Exception $e) {
                    // If date parsing fails, use today's date
                    $selectedDate = Carbon::now(config('app.timezone'))->format('Y-m-d');
                }
            }

            // Build query for payment entries from all accessible payments with this name
            $query = PaymentItemEntry::whereIn('payment_item_id', $paymentIds)
                ->with(['nozzle.pump', 'creator', 'paymentItem']);

            // Apply date filter (we always have a selectedDate now)
            $query->whereDate('created_at', $selectedDate);

            $paymentEntries = $query->orderBy('created_at', 'asc')->get();

            // Calculate totals
            $totalAmount = $paymentEntries->sum('amount');
            $totalLitre = $paymentEntries->sum('litre');
            $totalEntries = $paymentEntries->count();
            $averageRate = $totalLitre > 0 ? round($totalAmount / $totalLitre, 2) : 0;

            // Group by date for summary
            $dailySummary = $paymentEntries->groupBy(function($entry) {
                return $entry->created_at->format('Y-m-d');
            })->map(function($entries, $date) {
                return [
                    'date' => $date,
                    'amount' => $entries->sum('amount'),
                    'litre' => $entries->sum('litre'),
                    'count' => $entries->count()
                ];
            })->sortByDesc('date');

            // For backward compatibility, we'll also pass the first payment as 'payment'
            $payment = $payments->first();

            return view('party-list.payment-details', compact(
                'payment',   // First payment for backward compatibility
                'payments',  // All accessible payments with this name
                'paymentEntries',
                'totalAmount',
                'totalLitre',
                'totalEntries',
                'averageRate',
                'dailySummary',
                'selectedDate',
                'paymentType'  // Add payment type for display
            ));

        } catch (\Exception $e) {
            Log::error('Error showing payment type details: ' . $e->getMessage());
            return redirect()->route('party-list.index')->with('error', 'An error occurred while loading payment details.');
        }
    }

    /**
     * Show expense details for a specific category from Party List Management
     */
    public function showExpenseDetails($categoryId)
    {
        try {
            $user = Auth::user();
            $category = ExpensesCategory::findOrFail($categoryId);

            // Check if user has permission to view this expense category
            if ($user->user_type === 'operator') {
                return redirect()->route('party-list.index')->with('error', 'You do not have permission to view expense details.');
            }

            // For non-super admin users, check if they created the expenses OR have access to the pump
            if ($user->user_type !== 'super_admin') {
                $hasAccess = false;

                // Check if user created the category
                if ($category->created_by === $user->id) {
                    $hasAccess = true;
                }

                // Check if user has access to the pump associated with this category
                if (!$hasAccess && $category->pump_id) {
                    $userPumpIds = DB::table('pump_users')
                        ->where('email', $user->email)
                        ->where('status', 'active')
                        ->pluck('pump_id')
                        ->toArray();

                    if (in_array($category->pump_id, $userPumpIds)) {
                        $hasAccess = true;
                    }
                }

                if (!$hasAccess) {
                    return redirect()->route('party-list.index')->with('error', 'You do not have permission to view this expense category.');
                }
            }

            $expenses = Expense::where('category_id', $categoryId)->with('category')->get();

            return view('expenses.party-list-view', compact('category', 'expenses'));

        } catch (\Exception $e) {
            Log::error('Error showing expense details: ' . $e->getMessage());
            return redirect()->route('party-list.index')->with('error', 'An error occurred while loading expense details.');
        }
    }

    /**
     * Show expense details by category name (for cases where direct ID relationship is not established)
     */
    public function showExpenseDetailsByName($categoryName)
    {
        try {
            $user = Auth::user();

            // Check if user has permission to view expense details
            if ($user->user_type === 'operator') {
                return redirect()->route('party-list.index')->with('error', 'You do not have permission to view expense details.');
            }

            // Find expense category by name
            $category = ExpensesCategory::where('category_name', $categoryName)->first();

            if (!$category) {
                return redirect()->route('party-list.index')->with('error', 'Expense category not found.');
            }

            // For non-super admin users, check if they created the expenses OR have access to the pump
            if ($user->user_type !== 'super_admin') {
                $hasAccess = false;

                // Check if user created the category
                if ($category->created_by === $user->id) {
                    $hasAccess = true;
                }

                // Check if user has access to the pump associated with this category
                if (!$hasAccess && $category->pump_id) {
                    $userPumpIds = DB::table('pump_users')
                        ->where('email', $user->email)
                        ->where('status', 'active')
                        ->pluck('pump_id')
                        ->toArray();

                    if (in_array($category->pump_id, $userPumpIds)) {
                        $hasAccess = true;
                    }
                }

                if (!$hasAccess) {
                    return redirect()->route('party-list.index')->with('error', 'You do not have permission to view this expense category.');
                }
            }

            $expenses = Expense::where('category_id', $category->id)->with('category')->get();

            return view('expenses.party-list-view', compact('category', 'expenses'));

        } catch (\Exception $e) {
            Log::error('Error showing expense details by name: ' . $e->getMessage());
            return redirect()->route('party-list.index')->with('error', 'An error occurred while loading expense details.');
        }
    }
}
