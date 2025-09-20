<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Payment;
use App\Models\PumpUser;
use App\Models\PaymentItem;
use App\Models\Nozzle;
use App\Models\Pump;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentItemEntry;
use Illuminate\Support\Facades\Auth;

class DailyCheckReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get filter values
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $selectedUserId = $request->get('user_id');
        $selectedPaymentItemId = $request->get('payment_item_id');
        $selectedNozzleId = $request->get('nozzle_id');
        
        // Default to today's date if no date range is provided
        if (!$startDate && !$endDate) {
            $startDate = $endDate = Carbon::today()->format('Y-m-d');
        } elseif (!$endDate) {
            $endDate = $startDate;
        } elseif (!$startDate) {
            $startDate = $endDate;
        }

        // Get users based on user type
        if ($user->user_type === 'super_admin') {
            $users = PumpUser::where('status', 'active')->orderBy('user_name')->get();
        } else {
            // For admin users, only show users from their assigned pumps
            $users = PumpUser::whereHas('pump', function($query) use ($user) {
                $query->whereHas('pumpUsers', function($q) use ($user) {
                    $q->where('email', $user->email);
                });
            })->where('status', 'active')->orderBy('user_name')->get();
        }
        
        // Get payment items based on user type
        if ($user->user_type === 'super_admin') {
            $paymentItems = Payment::where('status', 'active')->orderBy('item_name')->get();
        } else {
            // For admin users, only show payment items for their assigned pumps
            $paymentItems = Payment::whereIn('petrol_pump', function($query) use ($user) {
                $query->select('pump_id')
                    ->from('pump_users')
                    ->where('email', $user->email)
                    ->where('status', 'active');
            })->where('status', 'active')->orderBy('item_name')->get();
        }
        
        // Get nozzles based on user type
        if ($user->user_type === 'super_admin') {
            $nozzles = Nozzle::with('pump')->get();
        } else {
            // For admin users, only show nozzles from their assigned pumps
            $nozzles = Nozzle::whereIn('pump_id', function($query) use ($user) {
                $query->select('pump_id')
                    ->from('pump_users')
                    ->where('email', $user->email)
                    ->where('status', 'active');
            })->with('pump')->get();
        }
        
        // Get allocated nozzles for the selected user
        $allocatedNozzles = [];
        if ($selectedUserId) {
            $selectedUser = PumpUser::find($selectedUserId);
            if ($selectedUser && $selectedUser->pump_id) {
                // Get nozzles from the user's assigned pump
                $allocatedNozzles = Nozzle::where('pump_id', $selectedUser->pump_id)
                    ->with('pump')
                    ->get();
            }
        }

        // Build sales query with relationships
        $salesQuery = Sale::with(['pump.pumpUsers', 'nozzle', 'paymentItems']);
        
        // Apply date range filter
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $salesQuery->whereDate('sale_date', $startDate);
            } else {
                $salesQuery->whereBetween('sale_date', [$startDate, $endDate]);
            }
        }
        
        // Filter by user type
        if ($user->user_type !== 'super_admin') {
            $salesQuery->whereIn('pump_id', function($query) use ($user) {
                $query->select('pump_id')
                    ->from('pump_users')
                    ->where('email', $user->email)
                    ->where('status', 'active');
            });
        }
        
        if ($selectedUserId) {
            $pumpUser = PumpUser::find($selectedUserId);
            if ($pumpUser && $pumpUser->pump_id) {
                $salesQuery->where('pump_id', $pumpUser->pump_id);
            }
        }
        
        if ($selectedNozzleId) {
            $salesQuery->where('nozzle_id', $selectedNozzleId);
        }
        $sales = $salesQuery->get();

        // Get payment item entries for the selected date range
        $paymentEntriesQuery = PaymentItemEntry::with(['nozzle.pump.pumpUsers', 'paymentItem', 'customer']);
        
        // Apply date range filter for payment entries
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $paymentEntriesQuery->whereDate('created_at', $startDate);
            } else {
                $paymentEntriesQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
        }
            
        // Filter payment entries by user type
        if ($user->user_type !== 'super_admin') {
            $paymentEntriesQuery->whereHas('nozzle', function($query) use ($user) {
                $query->whereIn('pump_id', function($q) use ($user) {
                    $q->select('pump_id')
                        ->from('pump_users')
                        ->where('email', $user->email)
                        ->where('status', 'active');
                });
            });
        }
            
        if ($selectedUserId) {
            $pumpUser = PumpUser::find($selectedUserId);
            if ($pumpUser && $pumpUser->pump_id) {
                $paymentEntriesQuery->whereHas('nozzle', function($query) use ($pumpUser) {
                    $query->where('pump_id', $pumpUser->pump_id);
                });
            }
        }
        
        if ($selectedNozzleId) {
            $paymentEntriesQuery->where('nozzle_id', $selectedNozzleId);
        }
        
        if ($selectedPaymentItemId) {
            $paymentEntriesQuery->where('payment_item_id', $selectedPaymentItemId);
        }
        
        $paymentEntries = $paymentEntriesQuery->get();

        // Get vehicles for the selected date range
        $vehiclesQuery = Vehicle::with(['paymentItems']);
        
        // Apply date range filter for vehicles
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $vehiclesQuery->whereDate('created_at', $startDate);
            } else {
                $vehiclesQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
        }
            
        // Filter vehicles by user type
        if ($user->user_type !== 'super_admin') {
            $vehiclesQuery->whereHas('loan', function($query) use ($user) {
                $query->whereIn('pump_id', function($q) use ($user) {
                    $q->select('pump_id')
                        ->from('pump_users')
                        ->where('email', $user->email)
                        ->where('status', 'active');
                });
            });
        }
            
        if ($selectedUserId) {
            $pumpUser = PumpUser::find($selectedUserId);
            if ($pumpUser && $pumpUser->pump_id) {
                $vehiclesQuery->whereHas('loan', function($query) use ($pumpUser) {
                    $query->where('pump_id', $pumpUser->pump_id);
                });
            }
        }
        
        if ($selectedNozzleId) {
            $vehiclesQuery->whereHas('loan', function($query) use ($selectedNozzleId) {
                $query->where('nozzle_id', $selectedNozzleId);
            });
        }
        
        $vehicles = $vehiclesQuery->get();

        // Calculate totals
        $totalSales = $sales->sum('net_sale_litre');
        $totalPayments = $paymentEntries->count();
        $totalShifts = $sales->groupBy('user_id')->count();

        return view('daily-check-report.index', compact(
            'sales',
            'paymentEntries',
            'users',
            'paymentItems',
            'nozzles',
            'allocatedNozzles',
            'totalSales',
            'totalPayments',
            'totalShifts',
            'startDate',
            'endDate',
            'selectedUserId',
            'selectedPaymentItemId',
            'selectedNozzleId',
            'vehicles'
        ));
    }

    public function export(Request $request)
    {
        // Get filter values
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $selectedUserId = $request->get('user_id');
        $selectedPaymentItemId = $request->get('payment_item_id');
        $selectedNozzleId = $request->get('nozzle_id');
        
        // Default to today's date if no date range is provided
        if (!$startDate && !$endDate) {
            $startDate = $endDate = Carbon::today()->format('Y-m-d');
        } elseif (!$endDate) {
            $endDate = $startDate;
        } elseif (!$startDate) {
            $startDate = $endDate;
        }

        // Build sales query
        $salesQuery = Sale::query();
        
        // Apply date range filter
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $salesQuery->whereDate('sale_date', $startDate);
            } else {
                $salesQuery->whereBetween('sale_date', [$startDate, $endDate]);
            }
        }
        
        if ($selectedUserId) {
            $pumpUser = PumpUser::find($selectedUserId);
            if ($pumpUser && $pumpUser->pump_id) {
                $salesQuery->where('pump_id', $pumpUser->pump_id);
            }
        }
        
        if ($selectedNozzleId) {
            $salesQuery->where('nozzle_id', $selectedNozzleId);
        }
        $sales = $salesQuery->get();

        // Build payments query
        $paymentsQuery = Payment::query();
        
        // Apply date range filter for payments
        if ($startDate && $endDate) {
            if ($startDate === $endDate) {
                $paymentsQuery->whereDate('created_on', $startDate);
            } else {
                $paymentsQuery->whereBetween('created_on', [$startDate, $endDate]);
            }
        }
        if ($selectedUserId) {
            $paymentsQuery->where('user_id', $selectedUserId);
        }
        if ($selectedPaymentItemId) {
            $paymentsQuery->where('id', $selectedPaymentItemId);
        }
        $payments = $paymentsQuery->get();
        
        // Convert created_on to Carbon objects
        $payments->transform(function ($payment) {
            if (is_string($payment->created_on)) {
                $payment->created_on = Carbon::parse($payment->created_on);
            }
            return $payment;
        });

        // Prepare CSV content
        $dateRangeText = $startDate === $endDate ? $startDate : "$startDate to $endDate";
        $csv = "Daily Check Report - " . $dateRangeText . "\n\n";
        
        // Sales section
        $csv .= "Sales Details\n";
        $csv .= "Date,Pump,Nozzle,Open Meter,Close Meter,Net Sale (Litres),Rate\n";
        foreach ($sales as $sale) {
            $csv .= $sale->sale_date->format('Y-m-d H:i:s') . ",";
            $csv .= $sale->pump_id . ",";
            $csv .= $sale->nozzle_id . ",";
            $csv .= $sale->open_meter . ",";
            $csv .= $sale->close_meter . ",";
            $csv .= $sale->net_sale_litre . ",";
            $csv .= $sale->rate . "\n";
        }

        // Payments section
        $csv .= "\nPayments Details\n";
        $csv .= "Date,Item Name,Petrol Pump,Description\n";
        foreach ($payments as $payment) {
            $csv .= $payment->created_on->format('Y-m-d H:i:s') . ",";
            $csv .= $payment->item_name . ",";
            $csv .= $payment->petrol_pump . ",";
            $csv .= $payment->item_desc . "\n";
        }

        // Set headers for CSV download
        $dateRangeForFilename = $startDate === $endDate ? $startDate : $startDate . "_to_" . $endDate;
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=daily-check-report-" . $dateRangeForFilename . ".csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        return response($csv, 200, $headers);
    }
} 