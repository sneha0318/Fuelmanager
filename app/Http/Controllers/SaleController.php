<?php

namespace App\Http\Controllers;

use App\Models\Nozzle;
use App\Models\Sale;
use App\Models\PaymentItemEntry;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check if user has permission to create sales
        if ($user->user_type === 'operator') {
            $hasPermission = Shift::where('pump_id', $request->pump_id)
                ->whereHas('pumpUser', function($query) use ($user) {
                    $query->where('email', $user->email);
                })
                ->where('is_active', true)
                ->whereDate('start_date', '<=', now())
                ->whereDate('end_date', '>=', now())
                ->exists();

            if (!$hasPermission) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to create sales for this pump.');
            }
        }

        $request->validate([
            'sale_date' => 'required|date',
            'pump_id' => 'required|exists:pumps,id',
            'nozzle_id' => 'required|exists:nozzles,id',
            'open_meter' => 'required|numeric|min:0',
            'close_meter' => 'nullable|numeric|min:0',
            'test_litre' => 'nullable|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'status' => 'required|in:Pending,Completed'
        ]);

        $sale = Sale::create([
            'sale_date' => $request->sale_date,
            'pump_id' => $request->pump_id,
            'nozzle_id' => $request->nozzle_id,
            'open_meter' => $request->open_meter,
            'close_meter' => $request->close_meter,
            'test_litre' => $request->test_litre,
            'rate' => $request->rate,
            'status' => $request->status,
            'close_time' => $request->close_time ? date('H:i:s', strtotime($request->close_time)) : null,
            'close_date' => $request->status === 'Completed' ? now()->toDateString() : null
        ]);

        return redirect()->route('new-sales.index')->with('success', 'Sale created successfully');
    }

    public function close(Request $request)
    {
        try {
            $sale = Sale::where('nozzle_id', $request->nozzle_id)
                ->where('status', 'Pending')
                ->first();

            if ($sale) {
                $user = Auth::user();
                $closeDate = $request->date ?? now()->format('Y-m-d');
                $closeTime = $request->close_time ?? now()->format('H:i:s');

                // Calculate net_sale_litre as the sum of all payment entries' litre for this sale
                $netSaleLitre = $sale->paymentEntries()->sum('litre');
                // Update sale with closing details
                $sale->update([
                    'close_meter' => $request->close_meter,
                    'close_date' => $closeDate,
                    'close_time' => $closeTime,
                    'status' => 'Completed',
                    'variation' => $request->variation ?? 0,
                    'edited_by' => $user->id,
                    'net_sale_litre' => $netSaleLitre
                ]);

                // Update nozzle's current meter
                $nozzle = Nozzle::find($request->nozzle_id);
                if ($nozzle) {
                    $nozzle->update([
                        'current_meter' => $request->close_meter,
                        'edited_by' => $user->id
                    ]);
                }

                // Update attendance record if exists
                $activeShift = Shift::where('pump_id', $sale->pump_id)
                    ->whereHas('pumpUser', function($query) use ($user) {
                        $query->where('email', $user->email);
                    })
                    ->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now())
                    ->first();

                if ($activeShift) {
                    \App\Models\Attendance::updateOrCreate(
                        [
                            'date' => $sale->sale_date,
                            'operator_id' => $user->id,
                            'shift_id' => $activeShift->id,
                        ],
                        [
                            'closing_meter_time' => $closeDate . ' ' . $closeTime,
                            'edited_by' => $user->id
                        ]
                    );

                    // Automatically deactivate only the current operator's active shifts when sale is closed
                    Shift::whereHas('pumpUser', function($query) use ($user) {
                        $query->where('email', $user->email);
                    })
                        ->where('is_active', true)
                        ->whereDate('start_date', '<=', now())
                        ->whereDate('end_date', '>=', now())
                        ->update(['is_active' => false]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Sale closed successfully. Closing meter updated to ' . $request->close_meter
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No pending sale found for this nozzle'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close sale: ' . $e->getMessage()
            ], 500);
        }
    }

    public function customerCreditSales()
    {
        $customers = Loan::all();
        return view('sales.customer-credit-sales', compact('customers'));
    }

    public function getCustomerCreditSales($customerId)
    {
        $creditSales = PaymentItemEntry::where('customer_name', $customerId)
            ->whereHas('paymentItem', function($query) {
                $query->where('name', 'Credit Sale');
            })
            ->with(['nozzle', 'paymentItem'])
            ->orderBy('created_at', 'desc')
            ->get();

        $paymentHistory = Payment::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'creditSales' => $creditSales->map(function($sale) {
                return [
                    'date' => $sale->created_at->format('Y-m-d H:i'),
                    'bill_no' => $sale->bill_no,
                    'nozzle_number' => $sale->nozzle->nozzle_number,
                    'litre' => $sale->litre,
                    'rate' => $sale->rate,
                    'amount' => $sale->amount,
                    'driver_name' => $sale->driver_name,
                    'vehicle_name' => $sale->vehicle_name,
                    'vehicle_number' => $sale->vehicle_number,
                    'status' => $sale->status
                ];
            }),
            'paymentHistory' => $paymentHistory->map(function($payment) {
                return [
                    'date' => $payment->created_at->format('Y-m-d H:i'),
                    'payment_type' => $payment->payment_type,
                    'amount' => $payment->amount,
                    'description' => $payment->description
                ];
            })
        ]);
    }
}
