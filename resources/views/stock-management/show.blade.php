@extends('layouts.master-without-page-title')

@section('css')
    <!-- DataTables -->
    <link href="{{ URL::asset('/assets/libs/datatables/datatables.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Bootstrap datepicker -->
    <link href="{{ URL::asset('build/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    
    <style>
        /* Date picker container */
        .date-picker-container {
            position: relative;
            max-width: 300px;
        }
        
        .date-picker-input {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            width: 100%;
            font-size: 14px;
            cursor: pointer;
            background-color: #fff;
        }
        
        /* Bootstrap datepicker customization if needed */
        
        /* Mobile-specific styles for header buttons */
        @media (max-width: 768px) {
            .page-title-box {
                flex-direction: column !important;
                align-items: flex-start !important;
            }
            
            .page-title-box .header-content {
                width: 100%;
                text-align: left !important;
                order: 1;
                margin-bottom: 15px;
            }
            
            .page-title-box .mt-3.mt-sm-0 {
                margin-top: 0 !important;
                width: 100%;
                display: flex;
                justify-content: flex-end;
                align-items: center;
                flex-wrap: wrap;
                gap: 8px;
                order: 2;
            }
            
            .page-title-box .mt-3.mt-sm-0 .btn {
                font-size: 12px;
                padding: 8px 12px;
                white-space: nowrap;
                flex-shrink: 0;
            }
            
            .page-title-box .mt-3.mt-sm-0 .btn i {
                margin-right: 4px;
            }
        }
        
        /* Extra small mobile devices */
        @media (max-width: 576px) {
            .page-title-box h4 {
                font-size: 18px !important;
            }
            
            .page-title-box p {
                font-size: 13px !important;
            }
            
            .page-title-box .mt-3.mt-sm-0 .btn {
                font-size: 11px;
                padding: 6px 10px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div class="header-content">
                    <h4 class="fs-16 fw-semibold mb-1">Stock Sales Details</h4>
                    <p class="text-muted mb-0">View all sales for {{ $stock->item->name }} at {{ $stock->pump->pump_name }}</p>
                </div>
                <div class="mt-3 mt-sm-0 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary flex-fill flex-md-grow-0" data-bs-toggle="modal" data-bs-target="#addStockModal">
                        <i class="fas fa-plus me-1"></i> Add to Stock
                    </button>
                    <a href="{{ route('stock-management.index') }}" class="btn btn-secondary flex-fill flex-md-grow-0">
                        <i class="fas fa-arrow-left me-1"></i> Back to Stock List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Stock Modal -->
    <div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark" id="addStockModalLabel">
                        <i class="fas fa-plus me-2"></i>Add to Existing Stock
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('stock-management.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <input type="hidden" name="item_id" value="{{ $stock->item_id }}">
                        <input type="hidden" name="pump_id" value="{{ $stock->pump_id }}">
                        <input type="hidden" name="date" value="{{ $stock->date->format('Y-m-d') }}">

                        <div class="row">
                            <div class="col-md-12 mb-3 alert alert-info">
                                <p class="mb-0"><strong>Note:</strong> Adding stock here will increase the quantity of the existing stock entry.</p>
                                <p class="mb-0 small">Current quantity: {{ number_format($stock->quantity, 2) }} L</p>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Quantity -->
                            <div class="col-md-12 mb-3">
                                <label for="quantity" class="form-label">Additional Quantity (L)</label>
                                <input type="number" step="0.01" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" required>
                                @error('quantity')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <small class="text-muted">Enter the quantity you want to add to the current stock</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add to Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Summary
                        @if(request('start_date') && request('end_date'))
                            <small class="text-muted"> 
                                ({{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }} - {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }})
                            </small>
                        @else
                            <small class="text-muted">(All sales from {{ \Carbon\Carbon::parse($stock->date)->format('d/m/Y') }} onwards)</small>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Total Sales</h6>
                                    <h3 class="mb-0">{{ $sales->count() }}</h3>
                                    <small>Transactions counted</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Total Quantity Sold</h6>
                                    <h3 class="mb-0">{{ number_format($sales->sum('litre'), 2) }} L</h3>
                                    <small>Total fuel dispensed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Total Amount</h6>
                                    <h3 class="mb-0">₹{{ number_format($sales->sum('amount'), 2) }}</h3>
                                    <small>Revenue from sales</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Remaining Quantity</h6>
                                    <h3 class="mb-0">{{ number_format($remainingQuantity, 2) }} L</h3>
                                    <small>Total Added: {{ number_format($totalStockAdded, 2) }} L</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Stock Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Item Name:</label>
                                <p>{{ $stock->item->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Pump Name:</label>
                                <p>{{ $stock->pump->pump_name }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Initial Quantity:</label>
                                <p>{{ number_format($stock->quantity, 2) }} L</p>
                                @php
                                    // Net Sale (Ltr): sum of all payment entries' litre for this stock (date, pump, item)
                                    $netSaleLitre = \App\Models\PaymentItemEntry::whereHas('nozzle', function($query) use ($stock) {
                                        $query->where('item_id', $stock->item_id)
                                              ->where('pump_id', $stock->pump_id);
                                    })
                                    ->whereDate('created_at', $stock->date)
                                    ->sum('litre');

                                    // Variation: sum of all variations for sales on this date, pump, item
                                    $variation = \App\Models\Sale::where('pump_id', $stock->pump_id)
                                        ->whereHas('nozzle', function($query) use ($stock) {
                                            $query->where('item_id', $stock->item_id);
                                        })
                                        ->whereDate('sale_date', $stock->date)
                                        ->sum('variation');

                                    $remainingQuantity = $stock->quantity - ($netSaleLitre + $variation);
                                @endphp
                                <span class="text-muted small">Remaining: <strong>{{ number_format($remainingQuantity, 2) }} L</strong> (Initial - (Net Sale + Variation))</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Date:</label>
                                <p>{{ date('d-m-Y', strtotime($stock->date)) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Sales Details
                        @if(request('start_date') && request('end_date'))
                            <small class="text-muted"> 
                                ({{ \Carbon\Carbon::parse(request('start_date'))->format('d/m/Y') }} - {{ \Carbon\Carbon::parse(request('end_date'))->format('d/m/Y') }})
                            </small>
                        @else
                            <small class="text-muted">(All sales from {{ \Carbon\Carbon::parse($stock->date)->format('d/m/Y') }} onwards)</small>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Date Picker for Sales --}}
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <form method="GET" action="{{ route('stock-management.show', $stock->id) }}" id="salesDateFilterForm">
                                @php
                                    $selectedDate = request('sales_date', date('d/m/Y'));
                                @endphp
                                <div class="input-group date">
                                    <input type="text" name="sales_date" id="sales_date_picker" class="form-control" 
                                          value="{{ $selectedDate }}" placeholder="Select date" readonly>
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="salesTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Bill No</th>
                                    <th>Payment Method</th>
                                    <th>Quantity (L)</th>
                                    <th>Rate</th>
                                    <th>Amount</th>
                                    <th>Customer Details</th>
                                    <th>Vehicle Details</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $key => $sale)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            @php
                                                // Extract sequence number from bill_no (format: BILL-YYYYMMDD-XXX)
                                                $billParts = explode('-', $sale->bill_no);
                                                $sequenceNumber = count($billParts) >= 3 ? (int)$billParts[2] : $sale->bill_no;
                                            @endphp
                                            {{ $sequenceNumber }}
                                        </td>
                                        <td>{{ $sale->paymentItem->item_name ?? 'N/A' }}</td>
                                        <td>{{ number_format($sale->litre, 2) }}</td>
                                        <td>₹{{ number_format($sale->rate, 2) }}</td>
                                        <td>₹{{ number_format($sale->amount, 2) }}</td>
                                        <td>
                                            @if($sale->customer_name)
                                                <strong>{{ $sale->customer_name }}</strong>
                                                @if($sale->mobile_number)
                                                    <br><small>{{ $sale->mobile_number }}</small>
                                                @endif
                                                @if($sale->driver_name)
                                                    <br><small>Driver: {{ $sale->driver_name }}</small>
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($sale->vehicle_name || $sale->vehicle_number)
                                                {{ $sale->vehicle_name ?? 'N/A' }}
                                                @if($sale->vehicle_number)
                                                    <br><small>{{ $sale->vehicle_number }}</small>
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            {{ $sale->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y') }}
                                            <br><small>{{ $sale->created_at->setTimezone('Asia/Kolkata')->format('H:i:s') }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No sales found for this stock in the selected date range</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold bg-light">
                                    <td colspan="3">Total</td>
                                    <td>{{ number_format($sales->sum('litre'), 2) }} L</td>
                                    <td>-</td>
                                    <td>₹{{ number_format($sales->sum('amount'), 2) }}</td>
                                    <td colspan="3">-</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>Stock History
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Date Picker for Stock History --}}
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <form method="GET" action="{{ route('stock-management.show', $stock->id) }}" id="historyDateFilterForm">
                                @php
                                    $selectedHistoryDate = request('history_date', date('d/m/Y'));
                                @endphp
                                <div class="input-group date">
                                    <input type="text" name="history_date" id="history_date_picker" class="form-control" 
                                          value="{{ $selectedHistoryDate }}" placeholder="Select date" readonly>
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Previous Quantity</th>
                                    <th>Added Quantity</th>
                                    <th>New Quantity</th>
                                    <th>Added By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockHistory as $history)
                                    <tr>
                                        <td>{{ $history->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i:s') }}</td>
                                        <td>{{ number_format($history->previous_quantity, 2) }} L</td>
                                        <td>{{ number_format($history->added_quantity, 2) }} L</td>
                                        <td>{{ number_format($history->new_quantity, 2) }} L</td>
                                        <td>{{ $history->creator->name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No stock history found for the selected date range</td>
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

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<!-- Required datatable js -->
<script src="{{ URL::asset('/assets/libs/datatables/datatables.min.js') }}"></script>
<script src="{{ URL::asset('/assets/libs/jszip/jszip.min.js') }}"></script>
<script src="{{ URL::asset('/assets/libs/pdfmake/pdfmake.min.js') }}"></script>
<!-- Bootstrap datepicker -->
<script src="{{ URL::asset('build/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>

<script>
$(function() {
    // Initialize bootstrap datepicker for sales filter
    $("#sales_date_picker").datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true,
        todayBtn: "linked",
    }).on('changeDate', function() {
        // Submit the form when a date is selected
        $("#salesDateFilterForm").submit();
    });
    
    // Initialize bootstrap datepicker for stock history filter
    $("#history_date_picker").datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true,
        todayBtn: "linked",
    }).on('changeDate', function() {
        // Submit the form when a date is selected
        $("#historyDateFilterForm").submit();
    });
    
    // Additional customization can be added here if needed
    
    // Initialize DataTable
    $('#salesTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        order: [[8, 'desc']], // Sort by Date & Time column by default
        pageLength: 25,
        responsive: true
    });
});
</script>
@endsection
