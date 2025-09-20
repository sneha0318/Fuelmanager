<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Nozzle;
use App\Models\PaymentItem;
use App\Models\PaymentItemEntry;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PaymentItemController extends Controller
{
    public function show(Request $request, $sale_id)
    {
        $user = Auth::user();
        $sale = Sale::with('nozzle.item')->findOrFail($sale_id);

        // Check if user has permission to view this sale
        if ($user->user_type === 'operator') {
            $hasPermission = $sale->pump->shifts()
                ->whereHas('pumpUser', function($query) use ($user) {
                    $query->where('email', $user->email);
                })
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->where('nozzle_id', $sale->nozzle_id)
                ->exists();

            if (!$hasPermission) {
                return redirect()->route('new-sales.index')
                    ->with('error', 'You do not have permission to view this sale.');
            }
        }

        // Get the selected date from request or default to today
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $nozzle = $sale->nozzle;

        // Get payment items from payment management for this pump only
        $paymentItems = Payment::select('id', 'item_name as name')
            ->where('status', 'active')
            ->where('petrol_pump', $nozzle->pump_id)
            ->orderBy('item_name')
            ->get();

        // Ensure Cash payment item exists
        $cashPayment = Payment::where('item_name', 'Cash')
            ->where('petrol_pump', $nozzle->pump_id)
            ->first();

        if (!$cashPayment) {
            $cashPayment = Payment::create([
                'item_name' => 'Cash',
                'item_desc' => 'Cash payments',
                'status' => 'active',
                'petrol_pump' => $nozzle->pump_id,
                'created_on' => now(),                        'created_by' => Auth::id(),
                        'edited_by' => Auth::id()
            ]);
            $paymentItems->push((object)[
                'id' => $cashPayment->id,
                'name' => 'Cash'
            ]);
        }

        // Get the latest sale rate for this nozzle on the selected date
        $latestSale = Sale::where('nozzle_id', $sale->nozzle->id)
            ->where('status', 'Completed')
            ->whereDate('sale_date', $selectedDate)
            ->latest()
            ->first();

        $currentRate = $latestSale ? $latestSale->rate : $nozzle->item->current_rate ?? 0;

        // Get payment entries for this nozzle on the selected date
        $paymentEntries = PaymentItemEntry::with(['paymentItem', 'vehicle'])
            ->where('sale_id', $sale_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get the credit sale payment item
        $creditSalePayment = Payment::where('item_name', 'Credit Sale')->first();
        $creditSaleId = $creditSalePayment ? $creditSalePayment->id : null;

        // Get credit sales
        $creditSales = $paymentEntries->where('payment_item_id', $creditSaleId);

        // Get loans for this pump only
        $loans = Loan::where('pump_id', $nozzle->pump_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all nozzles for the dropdown
        $nozzles = Nozzle::orderBy('nozzle_number')->get();

        // Get all customers for the dropdown for this pump only
        $customers = Loan::select('id', 'name', 'id as loan_id')
            ->where('pump_id', $nozzle->pump_id)
            ->orderBy('name')
            ->get();

        return view('payment-items.show', compact(
            'nozzle',
            'nozzles',
            'paymentItems',
            'paymentEntries',
            'currentRate',
            'sale',
            'loans',
            'customers',
            'selectedDate',
            'creditSales'
        ));
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $sale = Sale::findOrFail($request->sale_id);

            // Check if user has permission to add payments
            if ($user->user_type === 'operator') {
                $hasPermission = $sale->pump->shifts()
                    ->whereHas('pumpUser', function($query) use ($user) {
                        $query->where('email', $user->email);
                    })
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->where('nozzle_id', $request->nozzle_id)
                    ->exists();

                if (!$hasPermission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to add payments for this sale.'
                    ], 403);
                }
            }

            // Check stock quantity
            $nozzle = Nozzle::findOrFail($request->nozzle_id);
            $stock = \App\Models\Stock::where('item_id', $nozzle->item_id)
                ->where('pump_id', $nozzle->pump_id)
                ->latest()
                ->first();

            if (!$stock) {
                return response()->json([
                    'success' => false,
                    'message' => 'No stock available for this item. Please add stock first.'
                ], 422);
            }

            // Calculate sold quantity for today
            $soldQuantity = \App\Models\PaymentItemEntry::whereHas('nozzle', function($query) use ($nozzle) {
                $query->where('item_id', $nozzle->item_id)
                      ->where('pump_id', $nozzle->pump_id);
            })
            ->whereDate('created_at', $stock->date)
            ->sum('litre');

            $remainingQuantity = $stock->quantity - $soldQuantity;

            if ($remainingQuantity < $request->litre) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available quantity: ' . number_format($remainingQuantity, 2) . ' L'
                ], 422);
            }

            // Generate automatic bill number
            $billNumber = $this->generateDailyBillNumber($request->nozzle_id, $request->sale_id);

            // Validate the request
            $validated = $request->validate([
                'nozzle_id' => 'required|exists:nozzles,id',
                'payment_item_id' => 'required',
                'litre' => 'required|numeric|min:0',
                'rate' => 'required|numeric|min:0',
                'amount' => 'required|numeric|min:0',
                'customer_name' => 'required_if:payment_item_id,credit_sale',
                'loan_id' => 'required_if:payment_item_id,credit_sale',
                'driver_name' => 'nullable|string|max:255',
                'mobile_number' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
                'vehicle_name' => 'nullable|string|max:255',
                'vehicle_number' => 'nullable|string|max:50'
            ], [
                'mobile_number.size' => 'Mobile number must be exactly 10 digits.',
                'mobile_number.regex' => 'Mobile number must contain only digits.'
            ]);

            // Start a database transaction
            DB::beginTransaction();

            $paymentItemId = $request->payment_item_id;

            // Handle credit sale
            if ($paymentItemId === 'credit_sale') {
                // Get the nozzle to get the petrol pump ID
                $nozzle = Nozzle::findOrFail($request->nozzle_id);

                // Get or create the credit sale payment item
                $creditSalePayment = Payment::firstOrCreate(
                    ['item_name' => 'Credit Sale'],
                    [
                        'item_desc' => 'Credit sales for customers',
                        'status' => 'active',
                        'created_on' => now(),
                        'petrol_pump' => $nozzle->pump_id
                    ]
                );
                $paymentItemId = $creditSalePayment->id;
            }

            // Create payment entry
            $paymentEntry = PaymentItemEntry::create([
                'nozzle_id' => $request->nozzle_id,
                'payment_item_id' => $paymentItemId,
                'sale_id' => $request->sale_id,
                'bill_no' => $billNumber,
                'litre' => $request->litre,
                'rate' => $request->rate,
                'amount' => $request->amount,
                'status' => 'completed',
                'customer_name' => $request->customer_name,
                'driver_name' => $request->driver_name,
                'mobile_number' => $request->mobile_number,
                'vehicle_name' => $request->vehicle_name,
                'vehicle_number' => $request->vehicle_number
            ]);

            // Commit the transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment entry recorded successfully',
                'data' => $paymentEntry
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();

            // Log the error
            Log::error('Payment Item Store Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment entry: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $paymentEntry = PaymentItemEntry::findOrFail($request->id);
        $sale = Sale::findOrFail($paymentEntry->sale_id);

        // Check if user has permission to update payments
        if ($user->user_type === 'operator') {
            $hasPermission = $sale->pump->shifts()
                ->whereHas('pumpUser', function($query) use ($user) {
                    $query->where('email', $user->email);
                })
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->where('nozzle_id', $paymentEntry->nozzle_id)
                ->exists();

            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update payments for this sale.'
                ], 403);
            }
        }

        $request->validate([
            'id' => 'required|exists:payment_items_entries,id',
            'payment_item_id' => 'required|exists:payments,id',
            'litre' => 'required|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'customer_name' => 'nullable|string|max:255',
            'driver_name' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
            'vehicle_name' => 'nullable|string|max:255',
            'vehicle_number' => 'nullable|string|max:50'
        ], [
            'mobile_number.size' => 'Mobile number must be exactly 10 digits.',
            'mobile_number.regex' => 'Mobile number must contain only digits.'
        ]);

        try {
            DB::beginTransaction();

            $paymentEntry->update([
                'payment_item_id' => $request->payment_item_id,
                // bill_no is not updated to preserve the original auto-generated number
                'litre' => $request->litre,
                'rate' => $request->rate,
                'amount' => $request->amount,
                'customer_name' => $request->customer_name,
                'driver_name' => $request->driver_name,
                'mobile_number' => $request->mobile_number,
                'vehicle_name' => $request->vehicle_name,
                'vehicle_number' => $request->vehicle_number
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment entry updated successfully',
                    'data' => $paymentEntry->fresh()->load('paymentItem')
                ]);
            }

            return redirect()->route('payment-items.show', ['nozzle' => $paymentEntry->nozzle_id])
                ->with('success', 'Payment entry updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update payment entry: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to update payment entry: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $paymentEntry = PaymentItemEntry::findOrFail($id);
            $sale = Sale::findOrFail($paymentEntry->sale_id);

            // Check if user has permission to delete payments
            if ($user->user_type === 'operator') {
                $hasPermission = $sale->pump->shifts()
                    ->whereHas('pumpUser', function($query) use ($user) {
                        $query->where('email', $user->email);
                    })
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->where('nozzle_id', $paymentEntry->nozzle_id)
                    ->exists();

                if (!$hasPermission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to delete payments for this sale.'
                    ], 403);
                }
            }

            DB::beginTransaction();

            $paymentEntry->delete();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment entry deleted successfully',
                ]);
            }

            return redirect()->back()->with('success', 'Payment entry deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete payment entry: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete payment entry: ' . $e->getMessage());
        }
    }

    public function searchCustomers(Request $request)
    {
        $search = $request->get('search');
        $nozzleId = $request->get('nozzle_id');

        // Get the nozzle to get the pump ID
        $nozzle = Nozzle::findOrFail($nozzleId);

        $customers = Loan::where('pump_id', $nozzle->pump_id)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('phone_no', 'like', "%{$search}%");
            })
            ->select('id', 'name', 'company', 'phone_no')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

    public function getPaymentData($nozzleId, Request $request)
    {
        try {
            $selectedDate = $request->get('date', now()->format('Y-m-d'));

            // Get payment entries for the selected date
            $paymentEntries = PaymentItemEntry::with(['paymentItem'])
                ->where('nozzle_id', $nozzleId)
                ->whereDate('created_at', '>=', $selectedDate . ' 00:00:00')
                ->whereDate('created_at', '<=', $selectedDate . ' 23:59:59')
                ->orderBy('created_at', 'desc')
                ->get();

            // Format payment history
            $paymentHistory = $paymentEntries->map(function ($entry) {
                // Extract sequence number from bill_no (format: BILL-YYYYMMDD-XXX)
                $billParts = explode('-', $entry->bill_no);
                $sequenceNumber = count($billParts) >= 3 ? (int)$billParts[2] : $entry->bill_no;
                
                return [
                    'id' => $entry->id,
                    'date' => $entry->created_at->format('Y-m-d H:i'),
                    'bill_no' => $sequenceNumber,
                    'payment_item' => $entry->paymentItem->item_name ?? 'N/A',
                    'rate' => number_format($entry->rate, 2),
                    'litre' => number_format($entry->litre, 2),
                    'amount' => number_format($entry->amount, 2)
                ];
            });

            // Calculate payment summary
            $paymentSummary = $paymentEntries->groupBy('payment_item_id')->map(function ($entries) {
                return [
                    'payment_item' => $entries->first()->paymentItem->item_name ?? 'N/A',
                    'litre' => number_format($entries->sum('litre'), 2),
                    'amount' => number_format($entries->sum('amount'), 2)
                ];
            })->values();

            return response()->json([
                'paymentHistory' => $paymentHistory,
                'paymentSummary' => $paymentSummary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch payment data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate automatic daily bill number in format BILL-YYYYMMDD-XXX
     */
    private function generateDailyBillNumber($nozzleId, $saleId)
    {
        $today = now()->format('Ymd');
        $prefix = "BILL-{$today}-";
        
        // Get the last bill number for this specific sale
        $lastEntry = PaymentItemEntry::where('nozzle_id', $nozzleId)
            ->where('sale_id', $saleId)
            ->where('bill_no', 'LIKE', $prefix . '%')
            ->orderBy('bill_no', 'desc')
            ->first();
        
        $nextNumber = 1;
        
        if ($lastEntry) {
            // Extract the sequence number from the last bill number
            $lastBillNo = $lastEntry->bill_no;
            $lastSequence = (int) substr($lastBillNo, -3);
            $nextNumber = $lastSequence + 1;
        }
        
        // Format as 3-digit number with leading zeros
        $sequenceNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        
        return $prefix . $sequenceNumber;
    }

    /**
     * Get the next bill sequence number for display
     */
    private function getNextBillSequence($nozzleId, $saleId)
    {
        $today = now()->format('Ymd');
        $prefix = "BILL-{$today}-";
        
        // Get the last bill number for this specific sale
        $lastEntry = PaymentItemEntry::where('nozzle_id', $nozzleId)
            ->where('sale_id', $saleId)
            ->where('bill_no', 'LIKE', $prefix . '%')
            ->orderBy('bill_no', 'desc')
            ->first();
        
        $nextNumber = 1;
        
        if ($lastEntry) {
            // Extract the sequence number from the last bill number
            $lastBillNo = $lastEntry->bill_no;
            $lastSequence = (int) substr($lastBillNo, -3);
            $nextNumber = $lastSequence + 1;
        }
        
        return $nextNumber;
    }
}

