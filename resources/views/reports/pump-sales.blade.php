@extends('layouts.master-without-page-title')

@section('title')
    Pump Sales Report
@endsection

@section('css')
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Pump Sales Report</h4>
                    <p class="text-muted mb-0">{{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }} Sales Summary</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Filter Report</h5>
                    <form method="GET" action="{{ route('pump.sales.report') }}">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="month" class="form-label">Month</label>
                                <select class="form-select" id="month" name="month">
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="year" class="form-label">Year</label>
                                <select class="form-select" id="year" name="year">
                                    @for($i = date('Y') - 2; $i <= date('Y') + 1; $i++)
                                        <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>
                                            {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('pump.sales.report') }}" class="btn btn-secondary">Reset</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-search me-1"></i>Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fas fa-gas-pump"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1">Total Pumps</p>
                            <h5 class="mb-0">{{ $pumps->count() }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="fas fa-tint"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1">Total Liters</p>
                            <h5 class="mb-0">{{ number_format($totalLiters, 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1">Total Sales</p>
                            <h5 class="mb-0">₹{{ number_format($totalSales, 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pump Sales Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Pump-wise Sales Details</h5>
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Pump Name</th>
                                    <th>Location</th>
                                    <th>Total Sales</th>
                                    <th>Total Liters</th>
                                    <th>Total Amount</th>
                                    <th>Average Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pumps as $index => $pump)
                                    @php
                                        // Use actual payment entries with fallback to calculated amounts
                                        $paymentLiters = $pump->sales->sum(function($sale) {
                                            $actualLiters = $sale->paymentEntries->where('amount', '>', 0)->sum('litre');
                                            return $actualLiters > 0 ? $actualLiters : $sale->net_sale_litre;
                                        });
                                        $paymentAmount = $pump->sales->sum(function($sale) {
                                            $actualAmount = $sale->paymentEntries->where('amount', '>', 0)->sum('amount');
                                            return $actualAmount > 0 ? $actualAmount : ($sale->net_sale_litre * $sale->rate);
                                        });
                                        $pumpLiters = $paymentLiters;
                                        $pumpAmount = $paymentAmount;
                                        $averageRate = $pumpLiters > 0 ? $pumpAmount / $pumpLiters : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $pump->pump_name }}</td>
                                        <td>{{ $pump->location }}</td>
                                        <td>{{ $pump->sales->count() }}</td>
                                        <td>{{ number_format($pumpLiters, 2) }}</td>
                                        <td>₹{{ number_format($pumpAmount, 2) }}</td>
                                        <td>₹{{ number_format($averageRate, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No pump sales data found for the selected period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($pumps->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3">Total</th>
                                        <th>{{ $pumps->sum(function($pump) { return $pump->sales->count(); }) }}</th>
                                        <th>{{ number_format($totalLiters, 2) }}</th>
                                        <th>₹{{ number_format($totalSales, 2) }}</th>
                                        <th>₹{{ $totalLiters > 0 ? number_format($totalSales / $totalLiters, 2) : '0.00' }}</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Individual Sales Details -->
    @if($pumps->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Detailed Sales Breakdown</h5>
                        <div class="accordion" id="pumpSalesAccordion">
                            @foreach($pumps as $index => $pump)
                                @if($pump->sales->count() > 0)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading{{ $pump->id }}">
                                            <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#collapse{{ $pump->id }}" 
                                                    aria-expanded="{{ $index == 0 ? 'true' : 'false' }}" 
                                                    aria-controls="collapse{{ $pump->id }}">
                                                {{ $pump->pump_name }} - {{ $pump->sales->count() }} Sales
                                            </button>
                                        </h2>
                                        <div id="collapse{{ $pump->id }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" 
                                             aria-labelledby="heading{{ $pump->id }}" data-bs-parent="#pumpSalesAccordion">
                                            <div class="accordion-body">
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Nozzle</th>
                                                                <th>Open Meter</th>
                                                                <th>Close Meter</th>
                                                                <th>Net Liters</th>
                                                                <th>Rate</th>
                                                                <th>Amount</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($pump->sales as $sale)
                                                                @php
                                                                    // Get actual payment amounts from PaymentItemEntry
                                                                    $actualAmount = $sale->paymentEntries->where('amount', '>', 0)->sum('amount');
                                                                    $actualLiters = $sale->paymentEntries->where('amount', '>', 0)->sum('litre');
                                                                @endphp
                                                                <tr>
                                                                    <td>{{ $sale->sale_date->format('d-m-Y') }}</td>
                                                                    <td>{{ $sale->nozzle->nozzle_number ?? 'N/A' }}</td>
                                                                    <td>{{ number_format($sale->open_meter, 2) }}</td>
                                                                    <td>{{ number_format($sale->close_meter, 2) }}</td>
                                                                    <td>{{ number_format($actualLiters > 0 ? $actualLiters : $sale->net_sale_litre, 2) }}</td>
                                                                    <td>₹{{ number_format($sale->rate, 2) }}</td>
                                                                    <td>₹{{ number_format($actualAmount > 0 ? $actualAmount : ($sale->net_sale_litre * $sale->rate), 2) }}</td>
                                                                    <td>
                                                                        <span class="badge bg-{{ $sale->status === 'Completed' ? 'success' : 'warning' }}">
                                                                            {{ $sale->status }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
