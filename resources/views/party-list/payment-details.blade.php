@extends('layouts.master-without-page-title')

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        function clearFilter() {
            // Redirect to the same page without date parameter to show today's data
            window.location.href = "{{ route('party-list.payment-details', $paymentType ?? $payment->item_name) }}";
        }
    </script>
@endsection

@section('title')
    Payment Details - {{ $paymentType ?? $payment->item_name }}
@endsection

@section('css')
    <style>
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
        }
        
        .summary-card .card-body {
            background: transparent;
        }
        
        .alert-info {
            background-color: #e7f3ff;
            border-color: #b8daff;
            color: #004085;
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Payment Details: {{ $paymentType ?? $payment->item_name }}</h4>
                    <p class="text-muted mb-0">
                        @if(isset($payments) && $payments->count() > 1)
                            Total payment amounts for {{ $paymentType ?? $payment->item_name }} across {{ $payments->count() }} pumps
                        @else
                            Total payment amounts for {{ $paymentType ?? $payment->item_name }}
                        @endif
                    </p>
                </div>
                <div class="mt-3 mt-sm-0">
                    <a href="{{ route('party-list.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Party List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET" action="{{ route('party-list.payment-details', $paymentType ?? $payment->item_name) }}" id="dateFilterForm">
                @php
                    $selectedDate = request('date') ?? \Carbon\Carbon::now(config('app.timezone'))->format('Y-m-d');
                @endphp
                <div class="input-group">
                    <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}">
                    <button type="submit" class="btn btn-outline-primary">Filter</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="clearFilter()">Clear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-12 mb-3">
            <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-info-circle me-2"></i>
                <div>
                    <strong>Showing data for:</strong>
                    @if($selectedDate)
                        {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}
                        ({{ \Carbon\Carbon::parse($selectedDate)->format('l') }})
                    @else
                        All available data
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1 text-white">Total Amount</h5>
                            <h3 class="mb-0 text-white">₹{{ number_format($totalAmount, 2) }}</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-white rounded-circle">
                                <i class="fas fa-rupee-sign fa-2x text-primary"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1 text-white">Total Litre</h5>
                            <h3 class="mb-0 text-white">{{ number_format($totalLitre, 2) }}</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-white rounded-circle">
                                <i class="fas fa-gas-pump fa-2x text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1 text-white">Total Entries</h5>
                            <h3 class="mb-0 text-white">{{ $totalEntries }}</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-white rounded-circle">
                                <i class="fas fa-list fa-2x text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="card summary-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-1 text-white">Average Rate</h5>
                            <h3 class="mb-0 text-white">₹{{ number_format($averageRate, 2) }}</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-white rounded-circle">
                                <i class="fas fa-calculator fa-2x text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daily Summary</h5>
                </div>
                <div class="card-body">
                    @if($dailySummary->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-centered table-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Amount</th>
                                        <th>Total Litre</th>
                                        <th>Transactions</th>
                                        <th>Average Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailySummary as $summary)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($summary['date'])->format('d M Y') }}</td>
                                            <td>₹{{ number_format($summary['amount'], 2) }}</td>
                                            <td>{{ number_format($summary['litre'], 2) }}</td>
                                            <td>
                                                <span class="badge bg-primary">{{ $summary['count'] }}</span>
                                            </td>
                                            <td>₹{{ $summary['litre'] > 0 ? number_format($summary['amount'] / $summary['litre'], 2) : '0.00' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-calendar-times fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Data Found</h5>
                            <p class="text-muted mb-0">No payment entries found for the selected date range.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Entries Details -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payment Entries</h5>
                </div>
                <div class="card-body">
                    @if($paymentEntries->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-centered table-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Bill No</th>
                                        <th>Pump/Nozzle</th>
                                        <th>Customer</th>
                                        <th>Vehicle</th>
                                        <th>Rate</th>
                                        <th>Litre</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentEntries as $index => $entry)
                                        <tr>
                                            <td>
                                                <div>{{ $entry->created_at->format('d M Y') }}</div>
                                                <div class="text-muted small">{{ $entry->created_at->setTimezone('Asia/Kolkata')->format('H:i') }}</div>
                                            </td>
                                            <td>
                                                {{ $index + 1 }}
                                            </td>
                                            <td>
                                                @if($entry->nozzle && $entry->nozzle->pump)
                                                    <div>{{ $entry->nozzle->pump->pump_name }}</div>
                                                    <div class="text-muted small">{{ $entry->nozzle->nozzle_name }}</div>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($entry->customer_name)
                                                    <div>{{ $entry->customer_name }}</div>
                                                    @if($entry->driver_name)
                                                        <div class="text-muted small">Driver: {{ $entry->driver_name }}</div>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($entry->vehicle_name || $entry->vehicle_number)
                                                    <div>{{ $entry->vehicle_name ?: 'N/A' }}</div>
                                                    @if($entry->vehicle_number)
                                                        <div class="text-muted small">{{ $entry->vehicle_number }}</div>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>₹{{ number_format($entry->rate, 2) }}</td>
                                            <td>{{ number_format($entry->litre, 2) }}</td>
                                            <td>₹{{ number_format($entry->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-active">
                                        <th colspan="6" class="text-end">Total:</th>
                                        <th>{{ number_format($paymentEntries->sum('litre'), 2) }}</th>
                                        <th>₹{{ number_format($paymentEntries->sum('amount'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-receipt fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Payment Entries Found</h5>
                            <p class="text-muted mb-0">
                                @if($selectedDate)
                                    No payment entries found for {{ $paymentType ?? $payment->item_name }} on {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }}.
                                @else
                                    No payment entries found for {{ $paymentType ?? $payment->item_name }}.
                                @endif
                            </p>
                            @if($selectedDate)
                                <button type="button" class="btn btn-outline-primary mt-2" onclick="clearFilter()">
                                    <i class="fas fa-times me-1"></i>Clear Filter
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <strong>Payment Type:</strong>
                        </div>
                        <div class="col-sm-6">
                            {{ $paymentType ?? $payment->item_name }}
                        </div>
                    </div>
                    <hr>
                    @if(isset($payments) && $payments->count() > 1)
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Available on Pumps:</strong>
                            </div>
                            <div class="col-sm-6">
                                @foreach($payments as $paymentItem)
                                    <span class="badge bg-primary me-1 mb-1">{{ $paymentItem->pump->pump_name ?? 'N/A' }}</span>
                                @endforeach
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Total Pumps:</strong>
                            </div>
                            <div class="col-sm-6">
                                {{ $payments->count() }}
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Pump:</strong>
                            </div>
                            <div class="col-sm-6">
                                {{ $payment->pump->pump_name ?? 'N/A' }}
                            </div>
                        </div>
                    @endif
                    <hr>
                    <div class="row">
                        <div class="col-sm-6">
                            <strong>Status:</strong>
                        </div>
                        <div class="col-sm-6">
                            @if(isset($payments) && $payments->count() > 1)
                                @php
                                    $activeCount = $payments->where('status', 'active')->count();
                                    $allActive = $activeCount === $payments->count();
                                @endphp
                                @if($allActive)
                                    <span class="badge bg-success">All Active</span>
                                @else
                                    <span class="badge bg-warning">{{ $activeCount }}/{{ $payments->count() }} Active</span>
                                @endif
                            @else
                                <span class="badge bg-{{ $payment->status === 'active' ? 'success' : 'danger' }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-6">
                            <strong>Created On:</strong>
                        </div>
                        <div class="col-sm-6">
                            @if(isset($payments) && $payments->count() > 1)
                                {{ \Carbon\Carbon::parse($payments->min('created_on'))->setTimezone('Asia/Kolkata')->format('d M Y H:i') }}
                                @if($payments->min('created_on') !== $payments->max('created_on'))
                                    <br><small class="text-muted">Earliest entry</small>
                                @endif
                            @else
                                {{ \Carbon\Carbon::parse($payment->created_on)->setTimezone('Asia/Kolkata')->format('d M Y H:i') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        @if(isset($payments) && $payments->count() > 1)
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pump-wise Breakdown</h5>
                </div>
                <div class="card-body">
                    @php
                        $pumpBreakdown = $paymentEntries->groupBy(function($entry) {
                            return $entry->nozzle && $entry->nozzle->pump ? $entry->nozzle->pump->pump_name : 'Unknown';
                        });
                    @endphp
                    @foreach($pumpBreakdown as $pumpName => $entries)
                        <div class="row mb-2">
                            <div class="col-sm-4">
                                <strong>{{ $pumpName }}:</strong>
                            </div>
                            <div class="col-sm-8">
                                <div class="d-flex justify-content-between">
                                    <span>{{ $entries->count() }} entries</span>
                                    <span>₹{{ number_format($entries->sum('amount'), 2) }}</span>
                                </div>
                                <div class="progress mt-1" style="height: 4px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: {{ $totalAmount > 0 ? ($entries->sum('amount') / $totalAmount) * 100 : 0 }}%">
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection
