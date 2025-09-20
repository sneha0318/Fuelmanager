<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\PumpUser;
use App\Models\Nozzle;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get filter values
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $selectedUserId = $request->get('user');
        $selectedNozzleId = $request->get('nozzle');
        
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
            $users = PumpUser::whereHas('pump.pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email)
                      ->where('status', 'active');
            })->where('status', 'active')->orderBy('user_name')->get();
        }

        // Get nozzles based on user type
        if ($user->user_type === 'super_admin') {
            $nozzles = Nozzle::with('pump')->get();
        } else {
            // For admin users, only show nozzles from their assigned pumps
            $nozzles = Nozzle::whereHas('pump.pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email)
                      ->where('status', 'active');
            })->with('pump')->get();
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
        
        // Filter based on user type and pump assignments
        if ($user->user_type !== 'super_admin') {
            $salesQuery->whereHas('pump.pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email)
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

        $sales = $salesQuery->with(['pump', 'nozzle', 'paymentEntries'])->get();

        return view('reports.index', compact(
            'sales',
            'users',
            'nozzles',
            'startDate',
            'endDate',
            'selectedUserId',
            'selectedNozzleId'
        ));
    }

    public function pumpSalesReport(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $pumps = \App\Models\Pump::with(['sales' => function($query) use ($month, $year) {
            $query->whereMonth('sale_date', $month)
                  ->whereYear('sale_date', $year)
                  ->with(['paymentEntries.paymentItem']); // Load payment entries with payment types
        }])->get();

        // Calculate totals using actual payment amounts from PaymentItemEntry
        $totalSales = $pumps->sum(function($pump) {
            return $pump->sales->sum(function($sale) {
                // Use actual payment amounts, fallback to calculated if no payments exist
                $paymentAmount = $sale->paymentEntries->where('amount', '>', 0)->sum('amount');
                return $paymentAmount > 0 ? $paymentAmount : ($sale->net_sale_litre * $sale->rate);
            });
        });

        $totalLiters = $pumps->sum(function($pump) {
            return $pump->sales->sum(function($sale) {
                // Use actual liters from payment entries, fallback to net_sale_litre
                $paymentLiters = $sale->paymentEntries->where('amount', '>', 0)->sum('litre');
                return $paymentLiters > 0 ? $paymentLiters : $sale->net_sale_litre;
            });
        });

        return view('reports.pump-sales', compact('month', 'year', 'pumps', 'totalSales', 'totalLiters'));
    }
}
