@extends('layouts.master-without-page-title')
@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <!-- Date Range Picker CSS and JS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
    $(function() {
        // Initialize date range picker
        $('#daterange').daterangepicker({
            startDate: moment('{{ request("start_date", \Carbon\Carbon::now()->format("Y-m-d")) }}'),
            endDate: moment('{{ request("end_date", \Carbon\Carbon::now()->format("Y-m-d")) }}'),
            locale: {
                format: 'DD/MM/YYYY',
                cancelLabel: 'Cancel',
                applyLabel: 'Apply'
            },
            autoApply: false,
            autoUpdateInput: false
        });

        // Update input when Apply is clicked and automatically submit form
        $('#daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
            $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
            // Automatically submit the form when Apply is clicked
            $('#dateFilterForm').submit();
        });

        // Clear input when Cancel is clicked
        $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
            $('#start_date').val('');
            $('#end_date').val('');
        });

        // Reset filter functionality
        $('#resetFilter').on('click', function() {
            $('#daterange').val('');
            $('#start_date').val('');
            $('#end_date').val('');
            // Redirect to the same page without any query parameters
            window.location.href = window.location.pathname;
        });

        document.addEventListener('DOMContentLoaded', function () {
            const userSelect = document.getElementById('user');
            const nozzleSelect = document.getElementById('nozzle');

            userSelect.addEventListener('change', function () {
                const userId = this.value;

                if (!userId) {
                    nozzleSelect.innerHTML = '<option value="">- Select -</option>';
                    return;
                }

                // Fetch nozzles for the selected user
                fetch(`/api/nozzles/by-user/${userId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    nozzleSelect.innerHTML = '<option value="">- Select -</option>';
                    data.forEach(nozzle => {
                        const option = document.createElement('option');
                        option.value = nozzle.id;
                        option.textContent = `${nozzle.nozzle_number} (${nozzle.pump_name})`;
                        nozzleSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching nozzles:', error);
                    nozzleSelect.innerHTML = '<option value="">Error loading nozzles</option>';
                });
            });
        });
    });
    </script>
@endsection
@section('title')
    Detail Report
@endsection

@section('css')
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Check Summary</h4>
                    <p class="text-muted mb-0">Detail Report</p>
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

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Find Report</h5>
                    <form method="GET" action="{{ url('report') }}" id="dateFilterForm">
                        <div class="row">
                            <!-- Date Range Filter -->
                            <div class="col-md-4 mb-3">
                                <label for="daterange" class="form-label">Date Range</label>
                                @php
                                    $startDate = request('start_date');
                                    $endDate = request('end_date');
                                    $dateRangeValue = '';
                                    if ($startDate && $endDate) {
                                        $dateRangeValue = \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
                                    }
                                @endphp
                                <div class="input-group">
                                    <input type="text" name="daterange" id="daterange" class="form-control" 
                                           value="{{ $dateRangeValue }}" placeholder="Select Date Range" readonly>
                                    <input type="hidden" name="start_date" id="start_date" value="{{ $startDate }}">
                                    <input type="hidden" name="end_date" id="end_date" value="{{ $endDate }}">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                    <button type="button" class="btn btn-outline-secondary" id="resetFilter">
                                        <i class="fas fa-undo-alt"></i> Reset
                                    </button>
                                </div>
                            </div>
                            <!-- Select User -->
                            <div class="col-md-4 mb-3">
                                <label for="user" class="form-label">Select User</label>
                                <select class="form-select" id="user" name="user">
                                    <option value="">- Select -</option>
                                    @foreach($users as $user)
                                        @if($user->user_type === 'operator' && (auth()->user()->user_type === 'super_admin' || $user->email === auth()->user()->email))
                                            <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>
                                                {{ $user->user_name }} ({{ $user->pump_name ?? 'N/A' }})
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <!-- Select Nozzle -->
                            <div class="col-md-4 mb-3">
                                <label for="nozzle" class="form-label">Select Nozzle</label>
                                <select class="form-select" id="nozzle" name="nozzle">
                                    <option value="">- Select -</option>
                                    @foreach($nozzles as $nozzle)
                                        @if(auth()->user()->user_type === 'super_admin' || $nozzle->pump && $nozzle->pump->pumpUsers->contains('email', auth()->user()->email))
                                            <option value="{{ $nozzle->id }}" {{ $selectedNozzleId == $nozzle->id ? 'selected' : '' }}>
                                                {{ $nozzle->nozzle_number }} ({{ $nozzle->pump->pump_name ?? 'N/A' }})
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <!-- Sale Date (Hidden for backward compatibility) -->
                            <div class="col-md-3 mb-3" style="display: none;">
                                <input type="hidden" class="form-control" id="sale_date" name="sale_date">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ url('report') }}" class="btn btn-secondary">Reset</a>
                            <button type="submit" class="btn btn-success">Find</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Sale Date</th>
                                    <th>Operator</th>
                                    <th>Pump</th>
                                    <th>Nozzle / Item</th>
                                    <th>Net Sale</th>
                                    <th>Net Sale Rs</th>
                                    <th>Open Meter</th>
                                    <th>Close Meter</th>
                                    <th>Test</th>
                                    <th>Rate</th>
                                    <th>Status</th>
                                    <th>Created On</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $index => $sale)
                                    @if(auth()->user()->user_type === 'super_admin' || $sale->pump && $sale->pump->pumpUsers->contains('email', auth()->user()->email))
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $sale->sale_date->format('Y-m-d') }}</td>
                                            <td>
                                                @if($sale->pump && $sale->nozzle)
                                                    @php
                                                        $activeShift = $sale->pump->shifts()
                                                            ->where('start_date', '<=', $sale->sale_date)
                                                            ->where(function($query) use ($sale) {
                                                                $query->whereNull('end_date')
                                                                      ->orWhere('end_date', '>=', $sale->sale_date);
                                                            })
                                                            ->where('nozzle_id', $sale->nozzle_id)
                                                            ->orderBy('start_date', 'desc')
                                                            ->first();
                                                        
                                                        // Fallback: try to find any shift for this nozzle on the same date
                                                        if (!$activeShift) {
                                                            $activeShift = $sale->pump->shifts()
                                                                ->whereDate('start_date', $sale->sale_date->format('Y-m-d'))
                                                                ->where('nozzle_id', $sale->nozzle_id)
                                                                ->orderBy('start_date', 'desc')
                                                                ->first();
                                                        }
                                                    @endphp
                                                    {{ $activeShift && $activeShift->pumpUser ? $activeShift->pumpUser->user_name : 'N/A' }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $sale->pump->pump_name ?? 'N/A' }}</td>
                                            @php
                                                $itemType = $sale->nozzle && $sale->nozzle->item ? $sale->nozzle->item->item_type : 'Litre';
                                                $itemName = $sale->nozzle && $sale->nozzle->item ? $sale->nozzle->item->name : 'N/A';
                                                $unit = strtoupper($itemType) == 'KG' ? 'KG' : 'L';
                                            @endphp
                                            <td>
                                                <div><strong>Nozzle:</strong> {{ $sale->nozzle->nozzle_number ?? 'N/A' }}</div>
                                                <div class="text-muted small"><strong>Item:</strong> {{ $itemName }} <span class="badge bg-light text-dark">{{ $unit }}</span></div>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ number_format($sale->net_sale_litre, 2) }}</span>
                                                <span class="text-muted small">{{ $unit }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $actualAmount = $sale->paymentEntries->where('amount', '>', 0)->sum('amount');
                                                    $displayAmount = $actualAmount > 0 ? $actualAmount : ($sale->net_sale_litre * $sale->rate);
                                                @endphp
                                                <span class="fw-bold">{{ number_format($displayAmount, 2) }}</span>
                                                <span class="text-muted small">Rs</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ number_format($sale->open_meter, 2) }}</span>
                                                <span class="text-muted small">{{ $unit }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ number_format($sale->close_meter, 2) }}</span>
                                                <span class="text-muted small">{{ $unit }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ number_format($sale->test_litre, 2) }}</span>
                                                <span class="text-muted small">{{ $unit }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold">{{ number_format($sale->rate, 2) }}</span>
                                                <span class="text-muted small">Rs/{{ $unit }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $sale->status === 'Completed' ? 'success' : 'warning' }}">
                                                    {{ $sale->status }}
                                                </span>
                                            </td>
                                            <td>
                                                <div>{{ $sale->created_at->format('d-m-y') }}</div>
                                                <div class="text-muted small">{{ $sale->created_at->setTimezone('Asia/Kolkata')->format('H:i') }}</div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center">No sales data found for the selected criteria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .table th {
            background-color: #f8f9fa;
        }
        .badge {
            font-size: 0.75rem;
        }
        .daterangepicker {
            z-index: 1060 !important;
        }
        #daterange {
            cursor: pointer;
            background-color: white;
        }
        .daterangepicker .ranges li {
            color: #333;
        }
        .daterangepicker .ranges li.active {
            background-color: #007bff;
            color: white;
        }
        .daterangepicker .drp-buttons {
            border-top: 1px solid #ddd;
            padding: 8px;
        }
        .daterangepicker .drp-buttons .btn {
            margin-left: 8px;
        }
    </style>
@endpush
