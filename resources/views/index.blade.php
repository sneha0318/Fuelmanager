@extends('layouts.master-without-page-title')


@section('title')
    Dashboard
@endsection

@section('css')
<style>
    .dashboard-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    }
    .stat-card {
        background: linear-gradient(45deg, #fff, #f8f9fa);
        border-radius: 12px;
        padding: 1.5rem;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .chart-container {
        min-height: 350px;
        position: relative;
    }
    .progress {
        height: 8px;
        border-radius: 4px;
    }
    .user-feed-item {
        padding: 1rem;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    .user-feed-item:hover {
        background: rgba(0,0,0,0.02);
    }
    .custom-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 500;
    }
    .stock-status {
        font-size: 0.875rem;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
    }
    .expense-category {
        padding: 0.5rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
    }
    .party-list-item {
        border-left: 3px solid #0ab39c;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
        border-radius: 0 8px 8px 0;
    }

    /* Mobile adjustments */
    @media (max-width: 575.98px) {
        .stat-card {
            padding: 0.9rem;
        }
        .stat-card p {
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }
        .stat-card h5 {
            font-size: 1rem;
            margin-bottom: 0;
        }
        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 20px;
        }
    }

    /* Custom tablet breakpoint: keep 2-column (2x2) layout until width >= 500px - below 500px switch to single column */
    .overall-sales-row .col-12 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }

    @media (min-width: 500px) {
        .overall-sales-row .col-12 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }
    }

    @media (min-width: 1200px) {
        .overall-sales-row .col-12 {
            flex: 0 0 25% !important;
            max-width: 25% !important;
        }
    }
</style>
@endsection

@section('content')
    @php
        $user = Auth::user();

        // Fetch pumps based on user type
        if ($user->user_type === 'super_admin') {
            $pumps = \App\Models\Pump::where('status', 'active')->get();
            $users = \App\Models\PumpUser::with('pump')->whereIn('user_type', ['admin', 'operator'])->get();
        } else {
            // For admin and operator users, only show their assigned pumps
            $pumps = \App\Models\Pump::whereHas('pumpUsers', function($query) use ($user) {
                $query->where('email', $user->email);
            })->where('status', 'active')->get();

            $users = \App\Models\PumpUser::with('pump')
                ->whereIn('pump_id', $pumps->pluck('id'))
                ->whereIn('user_type', ['admin', 'operator'])
                ->get();
        }

        // Fetch stock data
        $stocks = \App\Models\Stock::with(['item', 'pump'])
            ->whereIn('pump_id', $pumps->pluck('id'))
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        // Fetch expenses data - removed pump_id filter
        $expenses = \App\Models\Expense::with(['category'])
            ->whereHas('category', function($query) {
                $query->whereNotNull('id');
            })
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        // Fetch party list data - removed pump_id filter
        $partyLists = \App\Models\PartyList::with(['creator', 'editor', 'loan.paymentEntries'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    @endphp

    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Welcome Back, <span class="text-primary">{{ Auth::user()->name }}</span></h4>
                    <p class="text-muted mb-0">Here's an overview of your dashboard.</p>
                </div>
                {{-- <div class="mt-3 mt-sm-0">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div> --}}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xxl-{{ Auth::user()->user_type === 'operator' ? '12' : '9' }}">
            <div class="card dashboard-card mb-4">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fas fa-cart-plus"></i>
                        </div>
                        <h4 class="card-title mb-0">Overall Sales</h4>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $statCol = Auth::user()->user_type === 'operator' ? '6' : (in_array(Auth::user()->user_type, ['admin', 'super_admin']) ? '3' : '4');
                    @endphp
                    <div class="row g-2 overall-sales-row justify-content-center align-items-stretch">
                        <!-- Today's Sale -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="stat-card h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-muted mb-2">Today's Sale</p>
                                        @php
                                            $totalAmount = 0;
                                            if ($user->user_type === 'operator') {
                                                $activeShift = \App\Models\Shift::whereHas('pumpUser', function($query) use ($user) {
                                                    $query->where('email', $user->email);
                                                })
                                                ->where('is_active', true)
                                                ->whereDate('start_date', '<=', now())
                                                ->whereDate('end_date', '>=', now())
                                                ->first();
                                                if ($activeShift && $activeShift->nozzle) {
                                                    $totalAmount = \App\Models\PaymentItemEntry::where('nozzle_id', $activeShift->nozzle_id)
                                                        ->where('amount', '>', 0)
                                                        ->whereDate('created_at', now())
                                                        ->sum('amount');
                                                }
                                            } else {
                                                $totalAmount = \App\Models\PaymentItemEntry::whereIn('nozzle_id', function($query) use ($pumps) {
                                                    $query->select('id')
                                                        ->from('nozzles')
                                                        ->whereIn('pump_id', $pumps->pluck('id'));
                                                })
                                                ->where('amount', '>', 0)
                                                ->whereDate('created_at', now())
                                                ->sum('amount');
                                            }
                                        @endphp
                                        <h5 class="mb-0">
                                            @if($user->user_type === 'operator')
                                                <span class="text-dark">₹{{ number_format($totalAmount, 2) }}</span>
                                            @else
                                                <a href="{{ route('daily-check-report.index') }}" class="text-dark">₹{{ number_format($totalAmount, 2) }}</a>
                                            @endif
                                        </h5>
                                    </div>
                                    <div class="stat-icon bg-success bg-opacity-10 text-success ms-2">
                                        <i class="fas fa-rupee-sign"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Today's Litre -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="stat-card h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-muted mb-2">Today's Litre</p>
                                        @php
                                            $totalLiters = 0;
                                            if ($user->user_type === 'operator') {
                                                $activeShift = \App\Models\Shift::whereHas('pumpUser', function($query) use ($user) {
                                                    $query->where('email', $user->email);
                                                })
                                                ->where('is_active', true)
                                                ->whereDate('start_date', '<=', now())
                                                ->whereDate('end_date', '>=', now())
                                                ->first();
                                                if ($activeShift && $activeShift->nozzle && $activeShift->nozzle->item && $activeShift->nozzle->item->item_type === 'Litre') {
                                                    $totalLiters = \App\Models\PaymentItemEntry::where('nozzle_id', $activeShift->nozzle_id)
                                                        ->where('amount', '>', 0)
                                                        ->whereDate('created_at', now())
                                                        ->sum('litre');
                                                }
                                            } else {
                                                $nozzleIds = \App\Models\Nozzle::whereIn('pump_id', $pumps->pluck('id'))
                                                    ->whereHas('item', function($query) {
                                                        $query->where('item_type', 'Litre');
                                                    })
                                                    ->pluck('id');
                                                $totalLiters = \App\Models\PaymentItemEntry::whereIn('nozzle_id', $nozzleIds)
                                                    ->where('amount', '>', 0)
                                                    ->whereDate('created_at', now())
                                                    ->sum('litre');
                                            }
                                        @endphp
                                        <h5 class="mb-0">
                                            @if($user->user_type === 'operator')
                                                <span class="text-dark">{{ number_format($totalLiters, 2) }} Ltr</span>
                                            @else
                                                <a href="{{ route('daily-check-report.index') }}" class="text-dark">{{ number_format($totalLiters, 2) }} Ltr</a>
                                            @endif
                                        </h5>
                                    </div>
                                    <div class="stat-icon bg-info bg-opacity-10 text-info ms-2">
                                        <i class="fas fa-gas-pump"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Today's KG -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="stat-card h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-muted mb-2">Today's KG</p>
                                        @php
                                            $totalKg = 0;
                                            if ($user->user_type === 'operator') {
                                                $activeShift = \App\Models\Shift::whereHas('pumpUser', function($query) use ($user) {
                                                    $query->where('email', $user->email);
                                                })
                                                ->where('is_active', true)
                                                ->whereDate('start_date', '<=', now())
                                                ->whereDate('end_date', '>=', now())
                                                ->first();
                                                if ($activeShift && $activeShift->nozzle && $activeShift->nozzle->item && $activeShift->nozzle->item->item_type === 'KG') {
                                                    $totalKg = \App\Models\PaymentItemEntry::where('nozzle_id', $activeShift->nozzle_id)
                                                        ->where('amount', '>', 0)
                                                        ->whereDate('created_at', now())
                                                        ->sum('litre'); // For KG, 'litre' field stores KG value
                                                }
                                            } else {
                                                $nozzleIds = \App\Models\Nozzle::whereIn('pump_id', $pumps->pluck('id'))
                                                    ->whereHas('item', function($query) {
                                                        $query->where('item_type', 'KG');
                                                    })
                                                    ->pluck('id');
                                                $totalKg = \App\Models\PaymentItemEntry::whereIn('nozzle_id', $nozzleIds)
                                                    ->where('amount', '>', 0)
                                                    ->whereDate('created_at', now())
                                                    ->sum('litre'); // For KG, 'litre' field stores KG value
                                            }
                                        @endphp
                                        <h5 class="mb-0">
                                            @if($user->user_type === 'operator')
                                                <span class="text-dark">{{ number_format($totalKg, 2) }} KG</span>
                                            @else
                                                <a href="{{ route('daily-check-report.index') }}" class="text-dark">{{ number_format($totalKg, 2) }} KG</a>
                                            @endif
                                        </h5>
                                    </div>
                                    <div class="stat-icon bg-success bg-opacity-10 text-success ms-2" style="padding: 0;">
                                        <i class="fas fa-leaf"></i>
                                        {{-- <img src="{{ asset('images/cng.avif') }}" alt="KG Icon" style="width: 40px; height: 40px; object-fit: contain; display: block; margin: auto;" /> --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Monthly Sales -->
                        @if(Auth::user()->user_type === 'admin' || Auth::user()->user_type === 'super_admin')
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="stat-card h-100">
                                <div>
                                    <p class="text-muted mb-2">Monthly Sales</p>
                                    <div class="d-flex flex-column flex-sm-row align-items-center gap-2">
                                        @php
                                            $currentMonth = request()->get('month', now()->month);
                                            $currentYear = request()->get('year', now()->year);
                                        @endphp

                                        <select class="form-select form-select-sm" id="monthSelector" style="width:120px;min-width:0;">
                                            @for($i = 1; $i <= 12; $i++)
                                                <option value="{{ $i }}" {{ $i == $currentMonth ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
                                            @endfor
                                        </select>

                                        <a href="{{ route('pump.sales.report', ['month' => $currentMonth, 'year' => $currentYear]) }}" class="text-dark w-100">
                                            @php
                                                $monthlySales = \App\Models\Sale::whereIn('pump_id', $pumps->pluck('id'))
                                                    ->whereMonth('sale_date', $currentMonth)
                                                    ->whereYear('sale_date', $currentYear)
                                                    ->with('paymentEntries')
                                                    ->get()
                                                    ->sum(function($sale) {
                                                        $paymentAmount = $sale->paymentEntries->where('amount', '>', 0)->sum('amount');
                                                        return $paymentAmount > 0 ? $paymentAmount : ($sale->net_sale_litre * $sale->rate);
                                                    });
                                            @endphp
                                            <h5 class="mb-0 text-truncate text-center text-sm-start">₹{{ number_format($monthlySales, 2) }}</h5>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                                <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div>
                                    <p class="text-danger mb-1 fw-semibold">Due Payments</p>
                                    <h4 class="mb-0 fw-bold"><a href="{{ route('loans.index') }}" class="text-dark">₹{{ number_format(($loans ?? collect())->whereIn('pump_id', $pumps->pluck('id'))->sum(function($loan) {
                                        return $loan->paymentEntries->where('amount', '>', 0)->sum('amount') -
                                               abs($loan->paymentEntries->where('amount', '<', 0)->sum('amount'));
                                    }), 2) }}</a></h4>
                                </div>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-danger" style="width: 75%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <p class="text-success mb-1 fw-semibold">Total Payments Made</p>
                                    <h4 class="mb-0 fw-bold"><a href="{{ route('loans.index') }}" class="text-dark">₹{{ number_format(($loans ?? collect())->whereIn('pump_id', $pumps->pluck('id'))->sum(function($loan) {
                                        return abs($loan->paymentEntries->where('amount', '<', 0)->sum('amount'));
                                    }), 2) }}</a></h4>
                                </div>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 85%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-4">
                                <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <p class="text-info mb-1 fw-semibold">Total Loan Users</p>
                                    <h4 class="mb-0 fw-bold">{{ ($loans ?? collect())->whereIn('pump_id', $pumps->pluck('id'))->count() }}</h4>
                                </div>
                            </div>
                            <div class="hstack gap-2 mt-4">
                                <a href="{{ route('loans.index') }}" class="btn btn-dark btn-sm rounded-pill">View Loans</a>
                                <a href="{{ route('loans.create') }}" class="btn btn-info btn-sm rounded-pill">Add Loan</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-{{ Auth::user()->user_type === 'operator' ? '12' : '8' }}">
                    <div class="card dashboard-card">
                        <div class="card-header bg-transparent border-0">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <h4 class="card-title mb-0">Sales by Product Category</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if(Auth::user()->user_type === 'operator')
                                <div class="col-12">
                                    @php
                                        $activeShift = \App\Models\Shift::whereHas('pumpUser', function($query) use ($user) {
                                            $query->where('email', $user->email);
                                        })
                                        ->where('is_active', true)
                                        ->whereDate('start_date', '<=', now())
                                        ->whereDate('end_date', '>=', now())
                                        ->first();

                                        $nozzle = $activeShift ? $activeShift->nozzle : null;
                                        $itemSales = [];
                                        $totalLitres = 0;

                                        if ($nozzle && $nozzle->item) {
                                            $salesLitres = \App\Models\PaymentItemEntry::where('nozzle_id', $nozzle->id)
                                                ->where('amount', '>', 0)
                                                ->whereDate('created_at', now())
                                                ->sum('litre');

                                            $itemSales[$nozzle->item->name] = [
                                                'name' => $nozzle->item->name,
                                                'litres' => $salesLitres
                                            ];
                                            $totalLitres = $salesLitres;
                                        }
                                    @endphp

                                    @if($nozzle && $nozzle->item)
                                        <div class="d-flex align-items-center mb-4">
                                            <div class="stat-icon bg-light me-3">
                                                @php
                                                    $logos = [
                                                        'CNG' => 'fas fa-leaf text-success',
                                                        'PETROL' => 'fas fa-gas-pump text-primary',
                                                        'DIESEL' => 'fas fa-truck-monster text-warning'
                                                    ];
                                                    $logoClass = $logos[strtoupper($nozzle->item->name)] ?? 'mdi mdi-circle text-primary';
                                                @endphp
                                                <i class="{{ $logoClass }}"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="mb-1">{{ strtoupper($nozzle->item->name) }}</h5>
                                                <div class="progress" style="height: 4px;">
                                                    <div class="progress-bar bg-primary" style="width: 100%"></div>
                                                </div>
                                                <small class="text-muted">{{ number_format($salesLitres, 2) }} Ltr</small>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center py-4">
                                            <i class="fas fa-gas-pump fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No Active Nozzle Assigned</h5>
                                        </div>
                                    @endif
                                </div>
                                @else
                                <div class="col-sm-6">
                                    @php
                                        // Get all active items for the user's pumps
                                        $items = App\Models\Item::whereHas('activePumps', function($query) use ($pumps) {
                                            $query->whereIn('pump_id', $pumps->pluck('id'));
                                        })->where('status', 'Active')->get();

                                        // Get sales data from multiple sources
                                        $itemSales = [];
                                        $totalLitres = 0;
                                        $today = \Carbon\Carbon::today();

                                        // Method 1: Get from PaymentItemEntry (today's data)
                                        foreach ($items as $item) {
                                            $nozzleIds = App\Models\Nozzle::where('item_id', $item->id)
                                                ->whereIn('pump_id', $pumps->pluck('id'))
                                                ->pluck('id');

                                            $paymentLitres = App\Models\PaymentItemEntry::whereIn('nozzle_id', $nozzleIds)
                                                ->where('amount', '>', 0)
                                                ->whereDate('created_at', $today)
                                                ->sum('litre');

                                            if ($paymentLitres > 0) {
                                                $itemSales[$item->name] = [
                                                    'name' => $item->name,
                                                    'litres' => $paymentLitres
                                                ];
                                                $totalLitres += $paymentLitres;
                                            }
                                        }

                                        // Method 2: If no payment data, try Sales table (today's data)
                                        if (empty($itemSales)) {
                                            $salesData = App\Models\Sale::whereIn('pump_id', $pumps->pluck('id'))
                                                ->whereDate('sale_date', $today)
                                                ->selectRaw('nozzle_id, SUM(net_sale_litre) as total_litres')
                                                ->groupBy('nozzle_id')
                                                ->get();

                                            foreach ($salesData as $sale) {
                                                if ($sale->nozzle_id && $sale->total_litres > 0) {
                                                    $nozzle = App\Models\Nozzle::find($sale->nozzle_id);
                                                    if ($nozzle && $nozzle->item) {
                                                        $itemName = $nozzle->item->name;
                                                        $currentLitres = isset($itemSales[$itemName]) ? $itemSales[$itemName]['litres'] : 0;
                                                        $itemSales[$itemName] = [
                                                            'name' => $itemName,
                                                            'litres' => $currentLitres + $sale->total_litres
                                                        ];
                                                        $totalLitres += $sale->total_litres;
                                                    }
                                                }
                                            }
                                        }

                                        // Method 3: Final fallback - distribute total sales
                                        if (empty($itemSales)) {
                                            $totalSalesLitres = App\Models\Sale::whereIn('pump_id', $pumps->pluck('id'))
                                                ->whereDate('sale_date', $today)
                                                ->sum('net_sale_litre');

                                            if ($totalSalesLitres > 0 && $items->isNotEmpty()) {
                                                $totalLitres = $totalSalesLitres;
                                                $perItemLitres = $totalSalesLitres / min(3, $items->count());
                                                
                                                foreach ($items->take(3) as $item) {
                                                    $itemSales[$item->name] = [
                                                        'name' => $item->name,
                                                        'litres' => $perItemLitres
                                                    ];
                                                }
                                            }
                                        }

                                        // Sort items by sales volume (descending)
                                        if (!empty($itemSales)) {
                                            uasort($itemSales, function($a, $b) {
                                                return $b['litres'] <=> $a['litres'];
                                            });
                                        }

                                        // Take top 10 items
                                        $topItems = array_slice($itemSales, 0, 10, true);

                                        // Create chunks for display
                                        $itemChunks = array_chunk($topItems, 2, true);
                                    @endphp

                                    @if(!empty($topItems))
                                        @foreach($itemChunks as $index => $chunk)
                                            <div class="row mb-3">
                                                @foreach($chunk as $itemName => $itemData)
                                                    <div class="col-6">
                                                        <div class="d-flex align-items-center">
                                                            @php
                                                                $percentage = $totalLitres > 0 ? round(($itemData['litres'] / $totalLitres) * 100) : 0;
                                                                $logos = [
                                                                    'CNG' => 'fas fa-leaf text-success',
                                                                    'PETROL' => 'fas fa-gas-pump text-primary',
                                                                    'DIESEL' => 'fas fa-truck-monster text-warning'
                                                                ];
                                                                $logoClass = $logos[strtoupper($itemData['name'])] ?? 'mdi mdi-circle text-primary';
                                                            @endphp
                                                            <div class="stat-icon bg-light me-3">
                                                                <i class="{{ $logoClass }}"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1">{{ strtoupper($itemData['name']) }}</h6>
                                                                <div class="progress" style="height: 4px;">
                                                                    <div class="progress-bar bg-primary" style="width: {{ $percentage }}%"></div>
                                                                </div>
                                                                <small class="text-muted">{{ $percentage }}% ({{ number_format($itemData['litres'], 1) }} Ltr)</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="text-center py-4">
                                            <i class="fas fa-chart-pie fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No sales data for today</p>
                                            <small class="text-muted">{{ date('d M Y') }}</small>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-sm-6">
                                    @php
                                        // Prepare data for the chart - use today's sales amounts, not litres
                                        $chartSeries = [];
                                        $chartLabels = [];
                                        $todaySalesData = [];

                                        // Get today's sales amounts (not litres) for chart
                                        if (!empty($topItems)) {
                                            // Method 1: Get amounts from PaymentItemEntry for today
                                            foreach ($topItems as $itemName => $itemData) {
                                                $nozzleIds = App\Models\Nozzle::whereHas('item', function($query) use ($itemName) {
                                                    $query->where('name', $itemName);
                                                })
                                                ->whereIn('pump_id', $pumps->pluck('id'))
                                                ->pluck('id');

                                                $todayAmount = App\Models\PaymentItemEntry::whereIn('nozzle_id', $nozzleIds)
                                                    ->where('amount', '>', 0)
                                                    ->whereDate('created_at', $today)
                                                    ->sum('amount');

                                                if ($todayAmount == 0) {
                                                    // Method 2: Try Sales table for today using actual payment amounts
                                                    $todayAmount = App\Models\Sale::whereIn('pump_id', $pumps->pluck('id'))
                                                        ->whereIn('nozzle_id', $nozzleIds)
                                                        ->whereDate('sale_date', $today)
                                                        ->with('paymentEntries')
                                                        ->get()
                                                        ->sum(function($sale) {
                                                            $paymentAmount = $sale->paymentEntries->where('amount', '>', 0)->sum('amount');
                                                            return $paymentAmount > 0 ? $paymentAmount : ($sale->net_sale_litre * $sale->rate);
                                                        });
                                                }

                                                if ($todayAmount > 0) {
                                                    $todaySalesData[$itemName] = $todayAmount;
                                                }
                                            }

                                            // Sort by amount and prepare chart data
                                            if (!empty($todaySalesData)) {
                                                arsort($todaySalesData);
                                                foreach (array_slice($todaySalesData, 0, 8, true) as $itemName => $amount) {
                                                    $chartSeries[] = floatval($amount);
                                                    $chartLabels[] = $itemName;
                                                }
                                            } else {
                                                // Fallback: use litre data if no amount data
                                                foreach (array_slice($topItems, 0, 8, true) as $item) {
                                                    $chartSeries[] = floatval($item['litres']);
                                                    $chartLabels[] = $item['name'];
                                                }
                                            }
                                        } else {
                                            // If no data available, show placeholder
                                            $chartSeries = [0];
                                            $chartLabels = ['No Data'];
                                        }
                                    @endphp
                                    <div class="chart-container">
                                        <div id="gradient_chart"
                                            data-colors='["--bs-primary", "--bs-success", "--bs-warning", "--bs-danger", "--bs-info", "--bs-dark", "--bs-purple", "--bs-orange"]'
                                            data-series='@json($chartSeries)'
                                            data-labels='@json($chartLabels)'
                                            class="apex-charts" dir="ltr"></div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    @if(Auth::user()->user_type === 'admin' || Auth::user()->user_type === 'super_admin')
                    <div class="card dashboard-card">
                        <div class="card-header bg-transparent border-0">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h4 class="card-title mb-0">Monthly Sales</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            @php
                                $currentYear = request()->get('year', now()->year);
                                $monthlyData = [];
                                $monthlyLabels = [];

                                for($i = 1; $i <= 12; $i++) {
                                    // Get actual payment amounts with fallback to calculated amounts
                                    $monthlySales = \App\Models\Sale::whereIn('pump_id', $pumps->pluck('id'))
                                        ->whereMonth('sale_date', $i)
                                        ->whereYear('sale_date', $currentYear)
                                        ->with('paymentEntries')
                                        ->get()
                                        ->sum(function($sale) {
                                            $paymentAmount = $sale->paymentEntries->where('amount', '>', 0)->sum('amount');
                                            return $paymentAmount > 0 ? $paymentAmount : ($sale->net_sale_litre * $sale->rate);
                                        });
                                    $monthlyData[] = $monthlySales;
                                    $monthlyLabels[] = date('M', mktime(0, 0, 0, $i, 1));
                                }
                            @endphp
                            <div class="chart-container">
                                <div id="monthly_sales_chart"
                                    data-series='@json($monthlyData)'
                                    data-labels='@json($monthlyLabels)'
                                    class="apex-charts"
                                    dir="ltr"></div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-header bg-transparent border-0">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <h4 class="card-title mb-0">In Progress</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>SN</th>
                                            <th>Status</th>
                                            <th>Operators</th>
                                            <th>Assigned Nozzles</th>
                                            <th>Location</th>
                                            <th>Progress</th>
                                            <th>Start date</th>
                                            <th>Estimation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $pumps = App\Models\Pump::with(['pumpUsers' => function($query) {
                                                $query->whereHas('shifts', function($q) {
                                                    $q->where('is_active', true)
                                                      ->whereDate('start_date', '<=', now())
                                                      ->whereDate('end_date', '>=', now());
                                                });
                                            }])->whereIn('id', $pumps->pluck('id'))->get();
                                        @endphp
                                        @forelse($pumps as $index => $pump)
                                            @if($pump->pumpUsers->count() > 0)
                                                @foreach($pump->pumpUsers as $operatorIndex => $operator)
                                                    <tr>
                                                        @if($operatorIndex === 0)
                                                            <td class="align-middle" rowspan="{{ $pump->pumpUsers->count() }}">{{ $index + 1 }}</td>
                                                            <td class="align-middle" rowspan="{{ $pump->pumpUsers->count() }}">
                                                                <span class="badge bg-success-subtle text-success">Active</span>
                                                            </td>
                                                        @endif
                                                        <td class="align-middle">
                                                            <span class="badge custom-badge bg-{{ ['primary', 'info', 'success', 'warning', 'danger'][$operatorIndex % 5] }}">
                                                                {{ $operator->user_name }}
                                                            </span>
                                                        </td>
                                                        <td class="align-middle">
                                                            @php
                                                                $activeShift = $operator->shifts()
                                                                    ->where('is_active', true)
                                                                    ->whereDate('start_date', '<=', now())
                                                                    ->whereDate('end_date', '>=', now())
                                                                    ->first();

                                                                $nozzle = $activeShift ? $activeShift->nozzle : null;
                                                            @endphp
                                                            @if($nozzle)
                                                                <span class="badge bg-light text-dark">
                                                                    Nozzle #{{ $nozzle->nozzle_number }}
                                                                    @if($nozzle->item)
                                                                        - {{ $nozzle->item->name }}
                                                                    @endif
                                                                </span>
                                                            @else
                                                                <span class="badge bg-secondary">No Nozzle Assigned</span>
                                                            @endif
                                                        </td>
                                                        @if($operatorIndex === 0)
                                                            <td class="align-middle" rowspan="{{ $pump->pumpUsers->count() }}">{{ $pump->pump_name }}</td>
                                                        @endif
                                                        <td class="align-middle">
                                                            @php
                                                                $operatorLiters = 0;
                                                                if($activeShift && $activeShift->nozzle) {
                                                                    $operatorLiters = App\Models\PaymentItemEntry::where('nozzle_id', $activeShift->nozzle_id)
                                                                        ->where('amount', '>', 0)
                                                                        ->whereDate('created_at', now())
                                                                        ->sum('litre');
                                                                }
                                                            @endphp
                                                            <div class="d-flex align-items-center">
                                                                <span class="text-muted">{{ number_format($operatorLiters, 2) }} Ltr</span>
                                                            </div>
                                                        </td>
                                                        @if($operatorIndex === 0)
                                                            <td class="align-middle" rowspan="{{ $pump->pumpUsers->count() }}">{{ now()->format('d/m/Y') }}</td>
                                                            <td class="align-middle" rowspan="{{ $pump->pumpUsers->count() }}">{{ now()->addDay()->format('d/m/Y') }}</td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td class="align-middle">{{ $index + 1 }}</td>
                                                    <td class="align-middle">
                                                        <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="badge bg-secondary">No Active Operators</span>
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="badge bg-secondary">No Nozzles Assigned</span>
                                                    </td>
                                                    <td class="align-middle">{{ $pump->pump_name }}</td>
                                                    <td class="align-middle">
                                                        <span class="text-muted">0.00 Ltr</span>
                                                    </td>
                                                    <td class="align-middle">{{ now()->format('d/m/Y') }}</td>
                                                    <td class="align-middle">{{ now()->addDay()->format('d/m/Y') }}</td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">No pumps found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-{{ Auth::user()->user_type === 'operator' ? '12' : '3' }}">
            <div class="row g-4">
                @if(Auth::user()->user_type === 'admin' || Auth::user()->user_type === 'super_admin')
                <div class="col-xxl-12 col-xl-6 order-1">
                    <div class="card dashboard-card sales-analytics-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="card-title mb-0">Sales Analytics</h4>
                                <select class="form-select form-select-sm rounded-pill" id="analyticsMonthSelector">
                                    @php
                                        $currentMonth = request()->get('month', now()->month);
                                        $currentYear = request()->get('year', now()->year);
                                    @endphp
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ $i == $currentMonth ? 'selected' : '' }}>
                                            {{ date('F', mktime(0,0,0,$i,1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            @php
                                // Get all active items for the user's pumps
                                $items = App\Models\Item::whereHas('activePumps', function($query) use ($pumps) {
                                    $query->whereIn('pump_id', $pumps->pluck('id'));
                                })->where('status', 'Active')->get();

                                // Initialize variables
                                $itemSales = [];
                                $totalSales = 0;

                                // Method 1: Try to get data from Sales table using actual payment amounts
                                $salesData = App\Models\Sale::whereIn('pump_id', $pumps->pluck('id'))
                                    ->whereMonth('sale_date', $currentMonth)
                                    ->whereYear('sale_date', $currentYear)
                                    ->with('paymentEntries')
                                    ->get();

                                if ($salesData->isNotEmpty()) {
                                    foreach ($salesData as $sale) {
                                        if ($sale->nozzle_id) {
                                            $nozzle = App\Models\Nozzle::find($sale->nozzle_id);
                                            if ($nozzle && $nozzle->item) {
                                                $itemName = $nozzle->item->name;
                                                
                                                // Use actual payment amount, fallback to calculated amount
                                                $paymentAmount = $sale->paymentEntries->where('amount', '>', 0)->sum('amount');
                                                $saleAmount = $paymentAmount > 0 ? $paymentAmount : ($sale->net_sale_litre * $sale->rate);
                                                
                                                $itemSales[$itemName] = ($itemSales[$itemName] ?? 0) + $saleAmount;
                                                $totalSales += $saleAmount;
                                            }
                                        }
                                    }
                                }

                                // Method 2: If no sales data, try PaymentItemEntry directly
                                if (empty($itemSales)) {
                                    foreach ($items as $item) {
                                        $nozzleIds = App\Models\Nozzle::where('item_id', $item->id)
                                            ->whereIn('pump_id', $pumps->pluck('id'))
                                            ->pluck('id');

                                        if ($nozzleIds->isNotEmpty()) {
                                            $paymentSales = App\Models\PaymentItemEntry::whereIn('nozzle_id', $nozzleIds)
                                                ->where('amount', '>', 0)
                                                ->whereMonth('created_at', $currentMonth)
                                                ->whereYear('created_at', $currentYear)
                                                ->sum('amount');

                                            if ($paymentSales > 0) {
                                                $itemSales[$item->name] = $paymentSales;
                                                $totalSales += $paymentSales;
                                            }
                                        }
                                    }
                                }

                                // Method 3: Final fallback - get total sales using actual payment amounts and distribute evenly
                                if (empty($itemSales)) {
                                    $allSales = App\Models\Sale::whereIn('pump_id', $pumps->pluck('id'))
                                        ->whereMonth('sale_date', $currentMonth)
                                        ->whereYear('sale_date', $currentYear)
                                        ->with('paymentEntries')
                                        ->get()
                                        ->sum(function($sale) {
                                            $paymentAmount = $sale->paymentEntries->where('amount', '>', 0)->sum('amount');
                                            return $paymentAmount > 0 ? $paymentAmount : ($sale->net_sale_litre * $sale->rate);
                                        });
                                    
                                    if ($allSales > 0 && $items->isNotEmpty()) {
                                        $totalSales = $allSales;
                                        $perItemSales = $allSales / min(3, $items->count()); // Distribute among max 3 items
                                        
                                        foreach ($items->take(3) as $item) {
                                            $itemSales[$item->name] = $perItemSales;
                                        }
                                    }
                                }

                                // Sort by sales amount (descending) and take top 5
                                if (!empty($itemSales)) {
                                    arsort($itemSales);
                                    $topItems = array_slice($itemSales, 0, 5, true);
                                } else {
                                    $topItems = [];
                                }

                                // Prepare chart data
                                $chartData = [];
                                $chartLabels = [];
                                $chartColors = ['#0ab39c', '#3577f1', '#f06548', '#f7b84b', '#405189'];

                                foreach ($topItems as $itemName => $salesAmount) {
                                    $chartData[] = floatval($salesAmount);
                                    $chartLabels[] = $itemName;
                                }

                                // If still no data, show placeholder
                                if (empty($chartData)) {
                                    $chartData = [0];
                                    $chartLabels = ['No Data'];
                                    $totalSales = 0;
                                    $topItems = [];
                                }
                            @endphp
                            <div class="chart-container">
                                <div id="sales_analytics_chart"
                                    data-series='@json($chartData)'
                                    data-labels='@json($chartLabels)'
                                    data-colors='@json($chartColors)'
                                    class="apex-charts" dir="ltr"></div>
                            </div>
                            <div class="row text-center mt-4">
                                @php $index = 0; @endphp
                                @if(!empty($topItems) && $totalSales > 0)
                                    @foreach($topItems as $itemName => $salesAmount)
                                        @php
                                            $percentage = round(($salesAmount / $totalSales) * 100, 1);
                                            $dotColor = $chartColors[$index % count($chartColors)];
                                        @endphp
                                        <div class="col-4">
                                            <div class="p-2">
                                                <p class="mb-2 text-truncate">
                                                    <i class="mdi mdi-circle font-size-10 me-1 sales-analytics-dot" style="color: {{ $dotColor }}"></i>
                                                    {{ $itemName }}
                                                </p>
                                                <h5 class="fw-semibold">{{ $percentage }}%</h5>
                                                <small class="text-muted">₹{{ number_format($salesAmount, 0) }}</small>
                                            </div>
                                        </div>
                                        @php $index++; @endphp
                                    @endforeach
                                @else
                                    <div class="col-12">
                                        <div class="text-center py-3">
                                            <i class="fas fa-chart-pie fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No sales data found for {{ date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) }}</p>
                                            <small class="text-muted">Try selecting a different month or check if sales data exists</small>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="col-xxl-12 col-xl-6 order-2">
                    @if(Auth::user()->user_type === 'admin' || Auth::user()->user_type === 'super_admin')
                    <div class="card dashboard-card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">User Feeds</h4>
                            <div class="rich-list rich-list-flush">
                                @foreach($users as $user)
                                <div class="user-feed-item">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1">{{ $user->user_name }}</h5>
                                            <div class="d-flex align-items-center">
                                                <span class="text-muted me-3">
                                                    <i class="fas fa-phone-alt me-1"></i>
                                                    {{ $user->mobile_number ?? 'No Mobile' }}
                                                </span>
                                                <span class="text-muted">
                                                    <i class="fas fa-gas-pump me-1"></i>
                                                    {{ $user->pump ? $user->pump->pump_name : ($user->pump_name ?? 'No Pump') }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <span class="badge custom-badge bg-primary-subtle text-primary">
                                                {{ ucfirst($user->user_type ?? 'N/A') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- New Stock Management Section -->
    <div class="row mt-4">
        <div class="col-xl-6">
            <div class="card dashboard-card">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <h4 class="card-title mb-0">Stock Overview</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Pump</th>
                                    <th>Quantity</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stocks as $stock)
                                    <tr>
                                        <td>{{ $stock->item->name ?? 'N/A' }}</td>
                                        <td>{{ $stock->pump->pump_name ?? 'N/A' }}</td>
                                        <td>{{ number_format($stock->quantity, 2) }}</td>
                                        <td>{{ $stock->date->format('d/m/Y') }}</td>
                                        <td>
                                            @php
                                                $statusClass = $stock->quantity > 0 ? 'success' : 'danger';
                                                $statusText = $stock->quantity > 0 ? 'In Stock' : 'Out of Stock';
                                            @endphp
                                            <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} stock-status">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No stock records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="{{ route('stock-management.index') }}" class="btn btn-primary">View All Stock</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Overview Section -->
        <div class="col-xl-6">
            <div class="card dashboard-card">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h4 class="card-title mb-0">Recent Expenses</h4>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($expenses as $expense)
                        <div class="expense-category">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $expense->category ? $expense->category->category_name : 'Uncategorized' }}</h6>
                                    <small class="text-muted">{{ $expense->date->format('d M Y') }}</small>
                                </div>
                                <div class="text-end">
                                    <h6 class="mb-1">₹{{ number_format($expense->amount, 2) }}</h6>
                                    <small class="text-muted">{{ $expense->name }}</small>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No recent expenses found</p>
                        </div>
                    @endforelse
                    <div class="text-end mt-3">
                        <a href="{{ route('expenses.index') }}" class="btn btn-primary">View All Expenses</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Party List Overview Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-header bg-transparent border-0">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="card-title mb-0">Recent Party List Entries</h4>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($partyLists as $partyList)
                        <div class="party-list-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $partyList->ledger_section }}</h6>
                                    <small class="text-muted">{{ $partyList->group_type }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge custom-badge bg-{{ $partyList->status === 'active' ? 'success' : 'danger' }}-subtle text-{{ $partyList->status === 'active' ? 'success' : 'danger' }}">
                                        {{ ucfirst($partyList->status) }}
                                    </span>
                                    <small class="d-block text-muted mt-1">{{ $partyList->created_at->format('d M Y') }}</small>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No party list entries found</p>
                        </div>
                    @endforelse
                    <div class="text-end mt-3">
                        <a href="{{ route('party-list.index') }}" class="btn btn-primary">View All Party Lists</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/dashboard.init.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Monthly Sales Chart
            const monthlySalesChart = document.getElementById('monthly_sales_chart');
            if (monthlySalesChart) {
                const series = JSON.parse(monthlySalesChart.dataset.series);
                const labels = JSON.parse(monthlySalesChart.dataset.labels);

                const options = {
                    series: [{
                        name: 'Monthly Sales',
                        data: series
                    }],
                    chart: {
                        type: 'area',
                        height: 350,
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    xaxis: {
                        categories: labels,
                        labels: {
                            style: {
                                colors: '#6c757d'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function(value) {
                                return '₹' + value.toLocaleString('en-IN');
                            },
                            style: {
                                colors: '#6c757d'
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) {
                                return '₹' + value.toLocaleString('en-IN');
                            }
                        }
                    },
                    colors: ['#0ab39c'],
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.2,
                            stops: [0, 90, 100]
                        }
                    }
                };

                const chart = new ApexCharts(monthlySalesChart, options);
                chart.render();
            }

            // Initialize Sales Analytics Chart
            const salesAnalyticsChart = document.getElementById('sales_analytics_chart');
            if (salesAnalyticsChart) {
                const series = JSON.parse(salesAnalyticsChart.dataset.series);
                const labels = JSON.parse(salesAnalyticsChart.dataset.labels);
                const colors = JSON.parse(salesAnalyticsChart.dataset.colors);

                const options = {
                    series: series,
                    chart: {
                        type: 'donut',
                        height: 350,
                        toolbar: {
                            show: false
                        },
                        animations: {
                            enabled: true,
                            easing: 'easeinout',
                            speed: 800
                        }
                    },
                    labels: labels,
                    colors: colors,
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%',
                                labels: {
                                    show: true,
                                    name: {
                                        show: true,
                                        fontSize: '22px',
                                        fontFamily: 'Helvetica, Arial, sans-serif',
                                        fontWeight: 600,
                                        offsetY: -10
                                    },
                                    value: {
                                        show: true,
                                        fontSize: '16px',
                                        fontFamily: 'Helvetica, Arial, sans-serif',
                                        fontWeight: 400,
                                        offsetY: 16,
                                        formatter: function (val) {
                                            return '₹' + Number(val).toLocaleString('en-IN', {minimumFractionDigits: 2});
                                        }
                                    },
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        fontSize: '16px',
                                        fontWeight: 600,
                                        formatter: function (w) {
                                            const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                            return '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
                                        }
                                    }
                                }
                            }
                        }
                    }
                };

                const chart = new ApexCharts(salesAnalyticsChart, options);
                chart.render();
            }

            // Month selector change handlers
            const monthSelector = document.getElementById('monthSelector');
            const analyticsMonthSelector = document.getElementById('analyticsMonthSelector');

            function handleMonthChange(selector) {
                if (selector) {
                    selector.addEventListener('change', function() {
                        const selectedMonth = this.value;
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('month', selectedMonth);
                        currentUrl.searchParams.set('year', new Date().getFullYear());
                        window.location.href = currentUrl.toString();
                    });
                }
            }

            handleMonthChange(monthSelector);
            handleMonthChange(analyticsMonthSelector);
        });
    </script>
@endsection
