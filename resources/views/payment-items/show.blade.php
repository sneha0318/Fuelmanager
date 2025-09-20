@extends('layouts.master-without-page-title')

{{-- @section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    @endsection --}}

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        .btn-delete-hover:hover {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            transform: translateY(-1px);
            box-shadow: 0 0.25rem 0.5rem rgba(220, 53, 69, 0.3);
        }
        
        .table-warning {
            transition: background-color 0.3s ease;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">Payment Items</h4>
                    <p class="text-muted mb-0">Nozzle #{{ $nozzle->nozzle_number }}</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('new-sales.index') }}" class="btn btn-outline-secondary d-flex align-items-center d-none d-md-flex" title="Back to New Sales" style="white-space: nowrap;">
                        <i class="fas fa-arrow-left me-2"></i>Back to New Sales
                    </a>
                    <!-- Mobile Back Button -->
                    <a href="{{ route('new-sales.index') }}" class="btn btn-outline-secondary btn-sm d-md-none" title="Back to New Sales">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <input type="date" class="form-control" id="dateSelector" value="{{ $selectedDate }}" style="min-width: 150px;">
                    @php
                        $isOwner = auth()->user()->user_type === 'super_admin' ||
                                  (auth()->user()->user_type === 'admin' && $nozzle->pump->pumpUsers->contains('email', auth()->user()->email)) ||
                                  (auth()->user()->user_type === 'operator' && $nozzle->pump->shifts()
                                      ->whereHas('pumpUser', function($query) {
                                          $query->where('email', auth()->user()->email);
                                      })
                                      ->where('is_active', true)
                                      ->whereDate('start_date', '<=', now())
                                      ->whereDate('end_date', '>=', now())
                                      ->exists());
                    @endphp
                    @if($isOwner)
                    <!-- Desktop Close Sale Button -->
                    <button type="button" class="btn btn-primary d-flex align-items-center d-none d-md-flex" data-bs-toggle="modal" data-bs-target="#closeSaleModal" title="Close the current sale" style="white-space: nowrap;">
                        <i class="fas fa-times me-2"></i>Close Sale
                    </button>
                    <!-- Mobile Close Sale Button -->
                    <button type="button" class="btn btn-primary btn-sm d-md-none" data-bs-toggle="modal" data-bs-target="#closeSaleModal" title="Close Sale">
                        <i class="fas fa-times"></i>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row">
        <!-- Payment Form Section -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add Payment</h5>
                </div>
                <div class="card-body">
                    @if($sale && $sale->close_meter)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This sale has been closed. You cannot add new payments.
                        </div>
                    @elseif(!$isOwner)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You can only view payments for this nozzle. Only the assigned operator can add or modify payments.
                        </div>
                    @else
                        <form id="paymentForm" action="{{ route('payment-items.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="sale_id" value="{{ $sale->id }}">
                            <input type="hidden" name="nozzle_id" value="{{ $nozzle->id }}">
                            <input type="hidden" name="rate" value="{{ $sale->rate ?? $nozzle->item->current_rate ?? 0 }}">
                            <input type="hidden" name="payment_item_id" id="actual_payment_item_id" value="">

                            <div class="row g-4">
                                <!-- Payment Item Selection -->
                                <div class="col-md-6">
                                    <label for="payment_item_id" class="form-label fw-bold">Payment Item</label>
                                    <select class="form-select" id="payment_item_id" required>
                                        <option value="">Select a payment item</option>
                                        @php
                                            $creditSalePayment = $paymentItems->where('name', 'Credit Sale')->first();
                                            $cashPayment = $paymentItems->where('name', 'Cash')->first();
                                            $regularPayments = $paymentItems->whereNotIn('name', ['Credit Sale', 'Cash']);

                                            // Debug information
                                            if(!$cashPayment) {
                                                \Log::info('Cash payment not found in payment items');
                                                \Log::info('Available payment items:', $paymentItems->pluck('name')->toArray());
                                            }
                                        @endphp                        <!-- Always show Cash option first -->
                        <option value="{{ $cashPayment ? $cashPayment->id : 'cash' }}" selected>Cash</option>

                                        <!-- Show other regular payments -->
                                        @foreach($regularPayments as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach

                                        <!-- Show Credit Sale option last -->
                                        <option value="{{ $creditSalePayment ? $creditSalePayment->id : 'credit_sale' }}" data-is-credit="true">Credit Sale</option>
                                    </select>
                                </div>

                                <!-- Credit Sale Details -->
                                <div id="creditSaleDetails" class="col-md-6" style="display: none;">
                                    <label for="credit_customer_name" class="form-label fw-bold">Customer</label>
                                    <select class="form-select select2" id="credit_customer_name" name="credit_customer_name">
                                        <option value="">Select a customer</option>
                                    </select>
                                    <input type="hidden" name="customer_name" id="customer_name">
                                </div>
                            </div>

                            <div class="row g-4">
                                <!-- Vehicle Details -->
                                <div id="vehicleDetails" class="col-12" style="display: none;">
                                    <div class="card border-secondary">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3 fw-bold">Vehicle Information</h6>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="driver_name" class="form-label">Driver Name</label>
                                                    <input type="text" class="form-control" id="driver_name" name="driver_name" placeholder="Enter driver name">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="mobile_number" class="form-label">Mobile Number</label>
                                                    <input type="text" class="form-control" id="mobile_number" name="mobile_number" 
                                                           placeholder="Enter 10-digit mobile number" 
                                                           pattern="[0-9]{10}" 
                                                           maxlength="10" 
                                                           title="Please enter exactly 10 digits">
                                                    <div class="invalid-feedback" id="mobile_number_error"></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="vehicle_name" class="form-label">Vehicle Name</label>
                                                    <input type="text" class="form-control" id="vehicle_name" name="vehicle_name" placeholder="Enter vehicle name">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="vehicle_number" class="form-label">Vehicle Number</label>
                                                    <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" placeholder="Enter vehicle number">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Details -->
                                <div id="paymentDetails" class="col-12">
                                    <div class="card border-secondary">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3 fw-bold">Payment Details</h6>
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="litre" class="form-label">{{ strtolower($nozzle->item->item_type) == 'kg' ? 'KG' : 'Litre' }}</label>
                                                    <input type="number" step="0.01" class="form-control" id="litre" name="litre" required>
                                                </div>
                                                <div class="col-12">
                                                    <label for="amount" class="form-label">Amount</label>
                                                    <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                                                </div>
                                                <div class="col-12">
                                                    <label for="rate" class="form-label">Rate</label>
                                                    <input type="number" step="0.01" class="form-control" id="rate" name="rate" value="{{ $nozzle->item->rates()->where('pump_id', $nozzle->pump_id)->where('is_active', true)->latest()->first()->rate ?? 0 }}" readonly required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="col-12">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-info" id="addVehicleBtn" style="display: none;">
                                            <i class="fas fa-truck me-1"></i> Add Vehicle
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i> Save Payment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Payment Summary</h6>
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Payment Item</th>
                                    <th>{{ strtolower($nozzle->item->item_type) == 'kg' ? 'KG' : 'Litre' }}</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $creditSalePayment = $paymentItems->where('name', 'Credit Sale')->first();
                                    $creditSaleId = $creditSalePayment ? $creditSalePayment->id : null;
                                    $regularPayments = $paymentEntries->where('payment_item_id', '!=', $creditSaleId);
                                    $creditSales = $paymentEntries->where('payment_item_id', $creditSaleId);
                                @endphp

                                @foreach($regularPayments->groupBy('payment_item_id') as $paymentItemId => $entries)
                                    <tr>
                                        <td>{{ $entries->first()->paymentItem->item_name }}</td>
                                        <td>{{ number_format($entries->sum('litre'), 2) }}</td>
                                        <td>{{ number_format($entries->sum('amount'), 2) }}</td>
                                    </tr>
                                @endforeach

                                @if($creditSales->count() > 0)
                                    <tr>
                                        <td>Credit Sales</td>
                                        <td>{{ number_format($creditSales->sum('litre'), 2) }}</td>
                                        <td>{{ number_format($creditSales->sum('amount'), 2) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th>{{ number_format($paymentEntries->sum('litre'), 2) }}</th>
                                    <th>{{ number_format($paymentEntries->sum('amount'), 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sales Details Section -->
            {{-- <div class="card mt-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Sales Details</h6>
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>{{ strtolower($nozzle->item->item_type) == 'kg' ? 'KG' : 'Litre' }}</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Net Sale {{ strtolower($nozzle->item->item_type) == 'kg' ? 'KG' : 'Litre' }}</td>
                                    <td>
                                        @if($sale && $sale->close_meter)
                                            {{ number_format(abs($sale->close_meter - $sale->open_meter), 2) }}
                                        @else
                                            <span class="text-muted">Not calculated</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($sale && $sale->close_meter && $sale->rate)
                                            @php
                                                $netSaleLitre = $sale->close_meter - $sale->open_meter;
                                                $netSaleAmount = $netSaleLitre * $sale->rate;
                                            @endphp
                                            {{ number_format($netSaleAmount, 2) }}
                                        @else
                                            <span class="text-muted">Not calculated</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Sale Entry {{ strtolower($nozzle->item->item_type) == 'kg' ? 'KG' : 'Litre' }}</td>
                                    <td>{{ number_format($paymentEntries->sum('litre'), 2) }}</td>
                                    <td>{{ number_format($paymentEntries->sum('amount'), 2) }}</td>
                                </tr>
                                @if($sale && $sale->close_meter)
                                @php
                                    $netSaleLitre = $sale->close_meter - $sale->open_meter;
                                    $saleEntryLitre = $paymentEntries->sum('litre');
                                    $literDifference = $netSaleLitre - $saleEntryLitre;
                                    
                                    $netSaleAmount = $sale->rate ? $netSaleLitre * $sale->rate : 0;
                                    $saleEntryAmount = $paymentEntries->sum('amount');
                                    $amountDifference = $netSaleAmount - $saleEntryAmount;
                                @endphp
                                <tr class="{{ ($literDifference != 0 || $amountDifference != 0) ? 'table-warning' : 'table-success' }}">
                                    <td><strong>Difference</strong></td>
                                    <td>
                                        <strong>{{ number_format($literDifference, 2) }}</strong>
                                        @if($literDifference > 0)
                                            <small class="text-warning ms-1">(Excess)</small>
                                        @elseif($literDifference < 0)
                                            <small class="text-danger ms-1">(Short)</small>
                                        @else
                                            <small class="text-success ms-1">(Balanced)</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($amountDifference, 2) }}</strong>
                                        @if($amountDifference > 0)
                                            <small class="text-warning ms-1">(Excess)</small>
                                        @elseif($amountDifference < 0)
                                            <small class="text-danger ms-1">(Short)</small>
                                        @else
                                            <small class="text-success ms-1">(Balanced)</small>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div> --}}
            </div>
        </div>
    </div>

    <!-- Main Page Alerts Container -->
    <div id="mainPageAlerts" class="mb-3"></div>

    <!-- Payment History -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Payment History</h6>
                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Bill No</th>
                                    <th>Payment Item</th>
                                    <th>Rate</th>
                                    <th>{{ strtolower($nozzle->item->item_type) == 'kg' ? 'KG' : 'Litre' }}</th>
                                    <th>Amount</th>
                                    @if($isOwner)
                                    <th>Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentEntries->reverse() as $entry)
                                    <tr data-id="{{ $entry->id }}">
                                        <td>
                                            <div>{{ $entry->created_at->timezone('Asia/Kolkata')->format('d-m-y') }}</div>
                                            <div class="text-muted small">{{ $entry->created_at->timezone('Asia/Kolkata')->format('H:i') }}</div>
                                        </td>
                                        <td>
                                            @php
                                                // Extract sequence number from bill_no (format: BILL-YYYYMMDD-XXX)
                                                $billParts = explode('-', $entry->bill_no);
                                                $sequenceNumber = count($billParts) >= 3 ? (int)$billParts[2] : $entry->bill_no;
                                            @endphp
                                            {{ $sequenceNumber }}
                                        </td>
                                        <td>
                                            @php
                                                $creditSalePayment = $paymentItems->where('name', 'Credit Sale')->first();
                                                $creditSaleId = $creditSalePayment ? $creditSalePayment->id : null;
                                            @endphp
                                            @if($entry->payment_item_id == $creditSaleId)
                                                Credit Sale
                                            @else
                                                {{ $entry->paymentItem->item_name ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>{{ number_format($entry->rate, 2) }}</td>
                                        <td>{{ number_format($entry->litre, 2) }}</td>
                                        <td>{{ number_format($entry->amount, 2) }}</td>
                                        @if($isOwner)
                                        <td>
                                            <div class="d-flex gap-2">
                                                {{-- <button class="btn btn-sm btn-warning edit-payment"
                                                        data-id="{{ $entry->id }}"
                                                        data-litre="{{ $entry->litre }}"
                                                        data-rate="{{ $entry->rate }}"
                                                        data-amount="{{ $entry->amount }}"
                                                        data-customer_name="{{ $entry->customer_name ?? '' }}"
                                                        data-driver_name="{{ $entry->driver_name ?? '' }}"
                                                        data-mobile_number="{{ $entry->mobile_number ?? '' }}"
                                                        data-vehicle_name="{{ $entry->vehicle_name ?? '' }}"
                                                        data-vehicle_number="{{ $entry->vehicle_number ?? '' }}">
                                                    <i class="fas fa-edit"></i>
                                                </button> --}}
                                                <button class="btn btn-sm btn-danger delete-payment" 
                                                        data-id="{{ $entry->id }}"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deletePaymentModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isOwner ? '7' : '6' }}" class="text-center">No payment entries found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Total:</th>
                                    <th>{{ number_format($paymentEntries->sum('litre'), 2) }}</th>
                                    <th>{{ number_format($paymentEntries->sum('amount'), 2) }}</th>
                                    @if($isOwner)
                                    <th></th>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPaymentModalLabel">Edit Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editPaymentForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_litre" class="form-label">{{ strtolower($nozzle->item->item_type) == 'kg' ? 'KG' : 'Litre' }}</label>
                            <input type="number" step="0.01" class="form-control" id="edit_litre" name="litre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_rate" class="form-label">Rate</label>
                            <input type="number" step="0.01" class="form-control" id="edit_rate" name="rate" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="updatePaymentBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Close Sale Modal -->
<div class="modal fade" id="closeSaleModal" tabindex="-1" aria-labelledby="closeSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark" id="closeSaleModalLabel"><i class="fas fa-times-circle me-2"></i>Close Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="closeSaleForm" action="{{ route('sales.close') }}" method="POST">
                    @csrf
                    <input type="hidden" name="nozzle_id" value="{{ $nozzle->id }}">
                    <input type="hidden" name="redirect_to" value="sales.index">

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror"
                                id="date" name="date" value="{{ date('Y-m-d') }}" readonly>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nozzle" class="form-label">Nozzle</label>
                            <input type="text" class="form-control @error('nozzle') is-invalid @enderror"
                                id="nozzle" name="nozzle" value="Nozzle #{{ $nozzle->nozzle_number }}" readonly>
                            @error('nozzle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="open_meter" class="form-label">Opening {{ strtolower($nozzle->item->item_type) == 'kg' ? 'KG' : 'Litre' }}</label>
                            <input type="number" step="0.01" class="form-control @error('open_meter') is-invalid @enderror"
                                id="open_meter" name="open_meter" value="{{ $sale->open_meter ?? $nozzle->current_meter ?? 0 }}" readonly>
                            @error('open_meter')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="rate" class="form-label">Rate</label>
                            <input type="number" step="0.01" class="form-control @error('rate') is-invalid @enderror"
                                id="rate" name="rate" value="{{ $sale->rate ?? $nozzle->item->current_rate ?? 0 }}" readonly>
                            @error('rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="close_meter" class="form-label">Closing {{ strtolower($nozzle->item->item_type) == 'kg' ? 'KG' : 'Litre' }}</label>
                            {{-- <div class="alert alert-info mb-2">
                                <strong>Calculated Closing Litre:</strong> <span id="calculated_closing_litre">0.00</span>
                            </div> --}}
                            <input type="number" step="0.01" class="form-control @error('close_meter') is-invalid @enderror"
                                id="close_meter" name="close_meter" required>
                            @error('close_meter')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Enter the final meter reading to close the sale</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="close_time" class="form-label">Close Time</label>
                            <input type="text" class="form-control" id="close_time" name="close_time" readonly>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Close Sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Loan Modal -->
<div class="modal fade" id="viewLoanModal" tabindex="-1" aria-labelledby="viewLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark" id="viewLoanModalLabel"><i class="fas fa-eye me-2"></i>View Loan Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Name</label>
                        <p id="viewName"></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Company</label>
                        <p id="viewCompany"></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <p id="viewAddress"></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold">Phone No.</label>
                        <p id="viewPhoneNo"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Loan Modal -->
<div class="modal fade" id="editLoanModal" tabindex="-1" aria-labelledby="editLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark" id="editLoanModalLabel"><i class="fas fa-edit me-2"></i>Edit Loan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editLoanForm" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="edit_loan_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_company" class="form-label">Company</label>
                        <input type="text" class="form-control" id="edit_company" name="company" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Address</label>
                        <input type="text" class="form-control" id="edit_address" name="address" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone_no" class="form-label">Phone No.</label>
                        <input type="text" class="form-control" id="edit_phone_no" name="phone_no" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Modal -->
<div class="modal fade" id="vehicleModal" tabindex="-1" aria-labelledby="vehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark" id="vehicleModalLabel"><i class="fas fa-truck me-2"></i>Add Vehicle Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addPaymentForm">
                    @csrf
                    <input type="hidden" name="nozzle_id" value="{{ $nozzle->id }}">
                    <input type="hidden" name="payment_item_id" value="credit_sale">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="driver_name" class="form-label">Driver Name</label>
                            <input type="text" class="form-control" id="driver_name" name="driver_name" placeholder="Enter driver name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="mobile_number" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" id="mobile_number" name="mobile_number" 
                                   placeholder="Enter 10-digit mobile number" 
                                   pattern="[0-9]{10}" 
                                   maxlength="10" 
                                   title="Please enter exactly 10 digits">
                            <div class="invalid-feedback" id="modal_mobile_number_error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="vehicle_name" class="form-label">Vehicle Name</label>
                            <input type="text" class="form-control" id="vehicle_name" name="vehicle_name" placeholder="Enter vehicle name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="vehicle_number" class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" placeholder="Enter vehicle number">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePaymentBtn"><i class="fas fa-save me-2"></i>Save Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Vehicle Modal -->
<div class="modal fade" id="editVehicleModal" tabindex="-1" aria-labelledby="editVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editVehicleModalLabel">Edit Vehicle Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editVehicleForm">
                    <input type="hidden" id="edit_vehicle_id" name="id">
                    <div class="mb-3">
                        <label for="edit_driver_name" class="form-label">Driver Name</label>
                        <input type="text" class="form-control" id="edit_driver_name" name="driver_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_mobile_number" class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" id="edit_mobile_number" name="mobile_number" 
                               pattern="[0-9]{10}" 
                               maxlength="10" 
                               title="Please enter exactly 10 digits" 
                               required>
                        <div class="invalid-feedback" id="edit_mobile_number_error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_vehicle_name" class="form-label">Vehicle Name</label>
                        <input type="text" class="form-control" id="edit_vehicle_name" name="vehicle_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_vehicle_number" class="form-label">Vehicle Number</label>
                        <input type="text" class="form-control" id="edit_vehicle_number" name="vehicle_number" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateVehicleBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Payment Modal -->
<div class="modal fade" id="deletePaymentModal" tabindex="-1" aria-labelledby="deletePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePaymentModalLabel">Delete Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                    </div>
                    <div>
                        <p class="mb-0">Are you sure you want to delete this payment entry?</p>
                        <p class="text-muted small mb-0">This action cannot be undone.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <form id="deletePaymentForm" method="POST" class="d-inline m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-delete-hover">Delete</button>
                </form>
            </div>            
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Add CSRF token to all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Mobile number validation function
        function validateMobileNumber(input, errorElementId) {
            const mobilePattern = /^[0-9]{10}$/;
            const value = input.value.trim();
            const errorElement = document.getElementById(errorElementId);
            
            if (value && !mobilePattern.test(value)) {
                input.classList.add('is-invalid');
                if (errorElement) {
                    errorElement.textContent = 'Mobile number must be exactly 10 digits';
                }
                return false;
            } else {
                input.classList.remove('is-invalid');
                if (errorElement) {
                    errorElement.textContent = '';
                }
                return true;
            }
        }

        // Add real-time validation to mobile number inputs
        function setupMobileValidation() {
            // Main form mobile number
            const mobileInput = document.getElementById('mobile_number');
            if (mobileInput) {
                mobileInput.addEventListener('input', function() {
                    // Only allow numeric input
                    this.value = this.value.replace(/[^0-9]/g, '');
                    validateMobileNumber(this, 'mobile_number_error');
                });
                
                mobileInput.addEventListener('blur', function() {
                    validateMobileNumber(this, 'mobile_number_error');
                });
            }

            // Edit modal mobile number
            const editMobileInput = document.getElementById('edit_mobile_number');
            if (editMobileInput) {
                editMobileInput.addEventListener('input', function() {
                    // Only allow numeric input
                    this.value = this.value.replace(/[^0-9]/g, '');
                    validateMobileNumber(this, 'edit_mobile_number_error');
                });
                
                editMobileInput.addEventListener('blur', function() {
                    validateMobileNumber(this, 'edit_mobile_number_error');
                });
            }

            // All mobile number inputs (including modal forms)
            const allMobileInputs = document.querySelectorAll('input[name="mobile_number"]');
            allMobileInputs.forEach(function(input) {
                if (input.id !== 'mobile_number' && input.id !== 'edit_mobile_number') {
                    input.addEventListener('input', function() {
                        // Only allow numeric input
                        this.value = this.value.replace(/[^0-9]/g, '');
                        if (this.value.length > 10) {
                            this.value = this.value.substring(0, 10);
                        }
                        // Add basic validation feedback
                        if (this.value.length === 10) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                        } else if (this.value.length > 0) {
                            this.classList.add('is-invalid');
                            this.classList.remove('is-valid');
                        } else {
                            this.classList.remove('is-invalid', 'is-valid');
                        }
                    });
                }
            });
        }

        $(document).ready(function() {
            // Main Page Alert Function (similar to party list page)
            function showMainPageAlert(message, type = 'success') {
                const alertTypes = {
                    success: { class: 'alert-success', icon: 'fas fa-check-circle' },
                    warning: { class: 'alert-warning', icon: 'fas fa-exclamation-triangle' },
                    error: { class: 'alert-danger', icon: 'fas fa-exclamation-circle' }
                };

                const config = alertTypes[type] || alertTypes.success;
                
                const alertHtml = `
                    <div class="alert ${config.class} alert-dismissible fade show" role="alert">
                        <i class="${config.icon} me-2"></i>
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                
                const alertContainer = document.getElementById('mainPageAlerts');
                alertContainer.innerHTML = alertHtml;
                
                // Auto dismiss after 5 seconds
                setTimeout(() => {
                    const alert = alertContainer.querySelector('.alert');
                    if (alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            }

            // Function to update table totals dynamically
            function updateTableTotals() {
                let totalLitre = 0;
                let totalAmount = 0;
                
                $('tbody tr[data-id]').each(function() {
                    const litre = parseFloat($(this).find('td:nth-child(5)').text().replace(/,/g, '')) || 0;
                    const amount = parseFloat($(this).find('td:nth-child(6)').text().replace(/,/g, '')) || 0;
                    totalLitre += litre;
                    totalAmount += amount;
                });
                
                // Update footer totals (litre is 5th column, amount is 6th column in footer)
                $('tfoot th:nth-child(5)').text(totalLitre.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('tfoot th:nth-child(6)').text(totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            }

            // Function to force clean modal backdrops
            function cleanupModals() {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css({
                    'padding-right': '',
                    'overflow': ''
                });
            }

            // Setup mobile number validation
            setupMobileValidation();
            
            // Add modal cleanup event listeners to prevent gray overlay issues
            $('#deletePaymentModal, #editPaymentModal').on('hidden.bs.modal', function () {
                // Force remove any remaining modal backdrops
                cleanupModals();
            });
            
            // Additional cleanup on window click (in case of stuck backdrops)
            $(window).on('click', function(e) {
                if ($(e.target).hasClass('modal-backdrop')) {
                    cleanupModals();
                }
            });
            
            // Check if sale is closed
            const isSaleClosed = {{ $sale && $sale->close_meter ? 'true' : 'false' }};

            // Handle main form submission
            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();

                if (isSaleClosed) {
                    Swal.fire('Error', 'This sale has been closed. You cannot add new payments.', 'error');
                    return;
                }

                const formData = new FormData(this);
                const paymentItemId = $('#payment_item_id').val();
                const selectedOption = $('#payment_item_id option:selected');
                const isCreditSale = selectedOption.data('is-credit');

                // Set the actual payment item ID
                $('#actual_payment_item_id').val(paymentItemId);
                formData.set('payment_item_id', paymentItemId);

                if (isCreditSale) {
                    const selectedCustomer = $('#credit_customer_name').val();
                    if (!selectedCustomer) {
                        Swal.fire('Error', 'Please select a customer for credit sale', 'error');
                        return;
                    }

                    const selectedOption = $('#credit_customer_name option:selected');
                    const customerName = selectedOption.text().split(' - ')[0];
                    formData.append('customer_name', customerName);
                    formData.append('loan_id', selectedCustomer);

                    // Validate mobile number if provided
                    const mobileNumber = $('#mobile_number').val();
                    if (mobileNumber && !/^[0-9]{10}$/.test(mobileNumber)) {
                        Swal.fire('Error', 'Mobile number must be exactly 10 digits', 'error');
                        return;
                    }
                }

                // Show loading indicator
                // Swal.fire({
                //     title: 'Processing...',
                //     text: 'Please wait while we save the payment',
                //     allowOutsideClick: false,
                //     didOpen: () => {
                //         Swal.showLoading();
                //     }
                // }); 

                // Submit form via AJAX
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Directly reload the page without showing success popup
                            location.reload();
                        } else {
                            Swal.fire('Error', response.message || 'Failed to save payment', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error saving payment:', xhr);
                        let errorMessage = 'Failed to save payment';

                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                let errorHtml = '<ul>';
                                Object.keys(xhr.responseJSON.errors).forEach(key => {
                                    errorHtml += `<li>${xhr.responseJSON.errors[key][0]}</li>`;
                                });
                                errorHtml += '</ul>';
                                Swal.fire('Validation Error', errorHtml, 'error');
                                return;
                            }
                            if (xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                        }

                        Swal.fire('Error', errorMessage, 'error');
                    }
                });
            });

            // Initialize Select2 for customer name dropdown with AJAX search
            $('#credit_customer_name').select2({
                placeholder: 'Search for a customer...',
                allowClear: true,
                ajax: {
                    url: '/api/customers/search',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            nozzle_id: {{ $nozzle->id }}
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
                                    id: item.id,
                                    text: item.name + ' - ' + item.company + ' - ' + item.phone_no,
                                    loan_id: item.id
                                };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });

            // Handle customer selection change
            $('#credit_customer_name').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const customerName = selectedOption.text().split(' - ')[0];
                const loanId = selectedOption.val();

                $('#customer_name').val(customerName);

                // Clear all vehicle details
                $('#driver_name').val('');
                $('#mobile_number').val('');
                $('#vehicle_name').val('');
                $('#vehicle_number').val('');

                if (loanId) {
                    // Fetch vehicle data for the selected customer
                    $.ajax({
                        url: `/vehicles/loan/${loanId}`,
                        method: 'GET',
                        success: function(response) {
                            if (response.success && response.vehicles && response.vehicles.length > 0) {
                                // Display the most recent vehicle
                                const latestVehicle = response.vehicles[response.vehicles.length - 1];
                                updateVehicleDetails(latestVehicle);
                            } else {
                                $('#vehicleDetails').show();
                            }
                        },
                        error: function(error) {
                            console.error('Error fetching vehicle data:', error);
                            $('#vehicleDetails').show();
                        }
                    });
                } else {
                    $('#vehicleDetails').show();
                }
            });

            // Function to update vehicle details
            function updateVehicleDetails(vehicle) {
                if (vehicle) {
                    $('#driver_name').val(vehicle.driver_name || '');
                    $('#mobile_number').val(vehicle.mobile_number || '');
                    $('#vehicle_name').val(vehicle.vehicle_name || '');
                    $('#vehicle_number').val(vehicle.vehicle_number || '');
                }
            }

            // Function to check if all required fields are filled and credit sale is selected
            function checkRequiredFields() {
                const paymentItemId = $('#payment_item_id').val();
                const litre = $('#litre').val();
                const rate = $('#rate').val();
                const amount = $('#amount').val();

                if (paymentItemId === '{{ $creditSalePayment ? $creditSalePayment->id : 'credit_sale' }}' && litre && rate && amount) {
                    $('#vehicleButtonContainer').show();
                } else {
                    $('#vehicleButtonContainer').hide();
                }
            }

            // Check fields on input change and payment item selection (removed bill_no from the check)
            $('#payment_item_id, #litre, #rate, #amount').on('change input', checkRequiredFields);

            // Handle payment items select change
            $('#payment_item_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const isCreditSale = selectedOption.data('is-credit');

                if (isCreditSale) {
                    $('#creditSaleDetails').show();
                    $('#paymentDetails').show();
                    $('#vehicleDetails').show();
                } else {
                    $('#creditSaleDetails').hide();
                    $('#paymentDetails').show();
                    $('#vehicleDetails').hide();
                }
            });

            // Handle Save Vehicle button click
            $('#saveVehicleBtn').on('click', function() {
                const vehicleData = {
                    driver_name: $('#driver_name').val(),
                    mobile_number: $('#mobile_number').val(),
                    vehicle_name: $('#vehicle_name').val(),
                    vehicle_number: $('#vehicle_number').val(),
                    loan_id: $('#credit_customer_name').val(),
                    nozzle_id: {{ $nozzle->id }},
                    litre: $('#litre').val(),
                    rate: $('#rate').val(),
                    amount: $('#amount').val()
                };

                // Validate vehicle data
                if (!vehicleData.driver_name || !vehicleData.mobile_number || !vehicleData.vehicle_name || !vehicleData.vehicle_number) {
                    Swal.fire('Error', 'Please fill all vehicle details', 'error');
                    return;
                }

                // Validate mobile number format
                const mobilePattern = /^[0-9]{10}$/;
                if (vehicleData.mobile_number && !mobilePattern.test(vehicleData.mobile_number)) {
                    Swal.fire('Error', 'Mobile number must be exactly 10 digits', 'error');
                    return;
                }

                // Show loading indicator
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we save the vehicle information',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Save vehicle data
                $.ajax({
                    url: '/vehicles',
                    method: 'POST',
                    data: vehicleData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success',
                                text: 'Vehicle information saved successfully',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                // Reload the page to show updated data
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to save vehicle information', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error saving vehicle:', xhr);
                        let errorMessage = 'Failed to save vehicle information';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire('Error', errorMessage, 'error');
                    }
                });
            });

            // Handle Edit Payment button click (using event delegation for dynamic buttons)
            $(document).on('click', '.edit-payment', function() {
                const id = $(this).data('id');
                const litre = $(this).data('litre');
                const rate = $(this).data('rate');
                const amount = $(this).data('amount');

                // Populate the edit form
                $('#edit_id').val(id);
                $('#edit_litre').val(litre);
                $('#edit_rate').val(rate);
                $('#edit_amount').val(amount);

                // Show the edit modal
                $('#editPaymentModal').modal('show');
            });

            // Calculate Amount dynamically in edit modal based on Litre and Rate
            $('#edit_litre, #edit_rate').on('input', function() {
                const litre = parseFloat($('#edit_litre').val()) || 0;
                const rate = parseFloat($('#edit_rate').val()) || 0;
                const amount = litre * rate;
                $('#edit_amount').val(amount.toFixed(2));
            });

            // Calculate Litre dynamically in edit modal based on Amount and Rate
            $('#edit_amount').on('input', function() {
                const amount = parseFloat($(this).val()) || 0;
                const rate = parseFloat($('#edit_rate').val()) || 0;
                const litre = rate > 0 ? amount / rate : 0;
                $('#edit_litre').val(litre.toFixed(2));
            });

            // Handle Delete Payment button click (using event delegation for dynamic buttons)
            $(document).on('click', '.delete-payment', function() {
                const id = $(this).data('id');
                
                // Set up the delete modal
                $('#deletePaymentForm').attr('action', `/payment-items/${id}`);
            });

            // Handle Delete Payment form submission
            $('#deletePaymentForm').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const deleteUrl = $(this).attr('action');
                const paymentId = deleteUrl.split('/').pop(); // Extract ID from URL

                $.ajax({
                    url: deleteUrl,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Close the modal properly
                            const modal = bootstrap.Modal.getInstance(document.getElementById('deletePaymentModal'));
                            if (modal) {
                                modal.hide();
                            }
                            
                            // Force remove any remaining modal backdrops
                            setTimeout(() => {
                                cleanupModals();
                            }, 100);

                            // Remove the row from the table dynamically
                            const rowToDelete = $(`tr[data-id="${paymentId}"]`);
                            if (rowToDelete.length) {
                                rowToDelete.fadeOut(300, function() {
                                    $(this).remove();
                                    // Update table totals after row removal
                                    updateTableTotals();
                                });
                            }

                            // Show success message in red color (error type for delete)
                            showMainPageAlert(response.message || 'Payment entry deleted successfully!', 'error');
                        } else {
                            // Close modal on error too
                            const modal = bootstrap.Modal.getInstance(document.getElementById('deletePaymentModal'));
                            if (modal) {
                                modal.hide();
                            }
                            setTimeout(() => {
                                cleanupModals();
                            }, 100);
                            
                            // Show error message
                            showMainPageAlert(`Error deleting payment: ${response.message || 'Unknown error'}`, 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error deleting payment:', xhr);

                        // Close modal on error
                        const modal = bootstrap.Modal.getInstance(document.getElementById('deletePaymentModal'));
                        if (modal) {
                            modal.hide();
                        }
                        setTimeout(() => {
                            cleanupModals();
                        }, 100);

                        let errorMessage = 'Error deleting payment. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        // Show error message
                        showMainPageAlert(errorMessage, 'error');
                    }
                });
            });

            // Handle Edit Vehicle button click
            $(document).on('click', '.edit-vehicle-btn', function() {
                const vehicleId = $('#vehicleDetails input[name="vehicle_id"]').val();

                if (!vehicleId) {
                    Swal.fire('Error', 'Vehicle ID not found', 'error');
                    return;
                }

                // Fetch vehicle data
                $.ajax({
                    url: `/vehicles/${vehicleId}`,
                    method: 'GET',
                    success: function(response) {
                        if (response.vehicle) {
                            // Populate the edit form
                            $('#edit_vehicle_id').val(response.vehicle.id);
                            $('#edit_driver_name').val(response.vehicle.driver_name);
                            $('#edit_mobile_number').val(response.vehicle.mobile_number);
                            $('#edit_vehicle_name').val(response.vehicle.vehicle_name);
                            $('#edit_vehicle_number').val(response.vehicle.vehicle_number);

                            // Show the edit modal
                            $('#editVehicleModal').modal('show');
                        } else {
                            Swal.fire('Error', 'Failed to fetch vehicle data', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error fetching vehicle data:', xhr);
                        Swal.fire('Error', 'Failed to fetch vehicle data', 'error');
                    }
                });
            });

            // Handle Delete Vehicle button click
            $(document).on('click', '.delete-vehicle-btn', function() {
                const vehicleId = $('#vehicleDetails input[name="vehicle_id"]').val();

                if (!vehicleId) {
                    Swal.fire('Error', 'Vehicle ID not found', 'error');
                    return;
                }

                // Show confirmation dialog
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading indicator
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while we delete the vehicle',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Send delete request
                        $.ajax({
                            url: `/vehicles/${vehicleId}`,
                            method: 'DELETE',
                            success: function(response) {
                                if (response.success) {
                                    // Show success message
                                    Swal.fire('Deleted!', 'Vehicle has been deleted.', 'success');

                                    // Hide the vehicle details
                                    $('#vehicleDetails').hide();

                                    // Clear the customer selection
                                    $('#credit_customer_name').val('').trigger('change');
                                } else {
                                    Swal.fire('Error', response.message || 'Failed to delete vehicle', 'error');
                                }
                            },
                            error: function(xhr) {
                                console.error('Error deleting vehicle:', xhr);
                                let errorMessage = 'Failed to delete vehicle';

                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }

                                Swal.fire('Error', errorMessage, 'error');
                            }
                        });
                    }
                });
            });

            // Handle Update Vehicle button click
            $('#updateVehicleBtn').on('click', function() {
                const formData = {
                    id: $('#edit_vehicle_id').val(),
                    driver_name: $('#edit_driver_name').val(),
                    mobile_number: $('#edit_mobile_number').val(),
                    vehicle_name: $('#edit_vehicle_name').val(),
                    vehicle_number: $('#edit_vehicle_number').val()
                };

                // Validate form
                if (!formData.driver_name || !formData.mobile_number || !formData.vehicle_name || !formData.vehicle_number) {
                    Swal.fire('Error', 'Please fill all fields', 'error');
                    return;
                }

                // Validate mobile number format
                const mobilePattern = /^[0-9]{10}$/;
                if (formData.mobile_number && !mobilePattern.test(formData.mobile_number)) {
                    Swal.fire('Error', 'Mobile number must be exactly 10 digits', 'error');
                    return;
                }

                // Show loading indicator
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we update the vehicle',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send update request
                $.ajax({
                    url: `/vehicles/${formData.id}`,
                    method: 'PUT',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            Swal.fire('Success', 'Vehicle updated successfully', 'success');

                            // Reload the page to show updated data
                            location.reload();
                        } else {
                            Swal.fire('Error', response.message || 'Failed to update vehicle', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error updating vehicle:', xhr);
                        let errorMessage = 'Failed to update vehicle';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire('Error', errorMessage, 'error');
                    }
                });
            });

            // Handle form submission
            $('#closeSaleForm').on('submit', function(e) {
                e.preventDefault();

                const openingLitre = parseFloat($('#open_meter').val()) || 0;
                const totalLitre = parseFloat('{{ $paymentEntries->sum('litre') }}') || 0;
                const testLitre = parseFloat('{{ $sale->test_litre ?? 0 }}') || 0;
                const expectedClosingLitre = openingLitre - totalLitre;
                const enteredClosingLitre = parseFloat($('#close_meter').val()) || 0;

                // Validate that closing meter is less than opening meter when fuel is sold
                if (enteredClosingLitre >= openingLitre) {
                    Swal.fire('Error', 'Closing meter must be less than opening meter when fuel is sold', 'error');
                    return;
                }

                // Check if there's a variation
                if (enteredClosingLitre !== expectedClosingLitre) {
                    const variation = enteredClosingLitre - expectedClosingLitre;
                    const variationText = variation > 0 ? 'more' : 'less';
                    
                    Swal.fire({
                        title: 'Variation Detected',
                        html: `The entered closing meter (${enteredClosingLitre.toFixed(2)}) is ${Math.abs(variation).toFixed(2)} litres ${variationText} than the expected value (${expectedClosingLitre.toFixed(2)}).<br><br>Do you want to proceed with this variation?`,
                        icon: 'warning',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonText: 'Yes, Save with Variation',
                        denyButtonText: 'Change It',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Add variation to form data
                            const formData = new FormData(this);
                            formData.append('variation', variation);
                            submitCloseSaleForm(formData);
                        } else if (result.isDenied) {
                            // Focus back on the close meter input
                            $('#close_meter').focus();
                        }
                    });
                } else {
                    // No variation, proceed normally
                    const formData = new FormData(this);
                    formData.append('variation', 0);
                    submitCloseSaleForm(formData);
                }
            });

            // Function to submit the close sale form
            function submitCloseSaleForm(formData) {
                // Show loading indicator
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we close the sale',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Get current time in case it's not set
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour12: false,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });

                // Ensure close_time is set
                if (!formData.get('close_time')) {
                    formData.set('close_time', timeString);
                }

                // Submit form via AJAX
                $.ajax({
                    url: $('#closeSaleForm').attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            Swal.fire({
                                title: 'Success',
                                text: response.message,
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                // Redirect to new sales index page
                                window.location.href = "{{ route('new-sales.index') }}";
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to close sale', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error closing sale:', xhr);
                        let errorMessage = 'Failed to close sale';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.status === 422) {
                            // Handle validation errors
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('<br>');
                        }

                        Swal.fire('Error', errorMessage, 'error');
                    }
                });
            }

            // Set current time when modal opens
            $('#closeSaleModal').on('show.bs.modal', function() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour12: false,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                $('#close_time').val(timeString);
            });

            // Calculate and set Closing Litre when Close Sale modal is opened
            $('#closeSaleModal').on('show.bs.modal', function() {
                const openingLitre = parseFloat($('#open_meter').val()) || 0;
                const totalLitre = parseFloat('{{ $paymentEntries->sum('litre') }}') || 0;
                const testLitre = parseFloat('{{ $sale->test_litre ?? 0 }}') || 0;
                const closingLitre = openingLitre - totalLitre ;

                // Display the calculated value
                $('#calculated_closing_litre').text(closingLitre.toFixed(2));

                // Clear the input field value
                $('#close_meter').val('');
            });

            // Handle date change
            document.getElementById('dateSelector').addEventListener('change', function() {
                const selectedDate = this.value;
                const currentUrl = window.location.href;
                const baseUrl = currentUrl.split('?')[0];
                window.location.href = `${baseUrl}?date=${selectedDate}`;
            });

            // Handle update payment
            const updatePaymentBtn = document.getElementById('updatePaymentBtn');
            if (updatePaymentBtn) {
                updatePaymentBtn.addEventListener('click', function() {
                    const form = document.getElementById('editPaymentForm');
                    const formData = new FormData(form);
                    const id = document.getElementById('edit_id').value;
                    const litre = document.getElementById('edit_litre').value;
                    const rate = document.getElementById('edit_rate').value;
                    const amount = document.getElementById('edit_amount').value;

                    fetch(`/payment-items/${id}`, {
                        method: 'PUT',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close the modal properly
                            const editModal = bootstrap.Modal.getInstance(document.getElementById('editPaymentModal'));
                            if (editModal) {
                                editModal.hide();
                            }
                            
                            // Force remove any remaining modal backdrops
                            setTimeout(() => {
                                cleanupModals();
                            }, 100);

                            // Update the specific row in the table dynamically
                            const rowToUpdate = $(`tr[data-id="${id}"]`);
                            if (rowToUpdate.length) {
                                // Update the cells with new values
                                rowToUpdate.find('td:nth-child(4)').text(parseFloat(rate).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                rowToUpdate.find('td:nth-child(5)').text(parseFloat(litre).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                rowToUpdate.find('td:nth-child(6)').text(parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                
                                // Update the edit button data attributes
                                const editBtn = rowToUpdate.find('.edit-payment');
                                editBtn.attr('data-litre', litre);
                                editBtn.attr('data-rate', rate);
                                editBtn.attr('data-amount', amount);

                                // Add a brief highlight effect
                                rowToUpdate.addClass('table-warning');
                                setTimeout(() => {
                                    rowToUpdate.removeClass('table-warning');
                                }, 1000);

                                // Update table totals
                                updateTableTotals();

                                // Show success message
                                showMainPageAlert(data.message || 'Payment updated successfully!', 'success');
                            }
                        } else {
                            // Close modal on error too
                            const editModal = bootstrap.Modal.getInstance(document.getElementById('editPaymentModal'));
                            if (editModal) {
                                editModal.hide();
                            }
                            setTimeout(() => {
                                cleanupModals();
                            }, 100);
                            
                            showMainPageAlert('Failed to update payment: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Close modal on error
                        const editModal = bootstrap.Modal.getInstance(document.getElementById('editPaymentModal'));
                        if (editModal) {
                            editModal.hide();
                        }
                        setTimeout(() => {
                            cleanupModals();
                        }, 100);
                        
                        showMainPageAlert('An error occurred while updating the payment', 'error');
                    });
                });
            }

            // Calculate Amount dynamically based on Litre and Rate
            $('#litre').on('input', function() {
                const litre = parseFloat($(this).val()) || 0;
                const rate = parseFloat($('#rate').val()) || 0;
                const amount = litre * rate;
                $('#amount').val(amount.toFixed(2));
            });

            // Calculate Litre dynamically based on Amount and Rate
            $('#amount').on('input', function() {
                const amount = parseFloat($(this).val()) || 0;
                const rate = parseFloat($('#rate').val()) || 0;
                const litre = rate > 0 ? amount / rate : 0;
                $('#litre').val(litre.toFixed(2));
            });
        });
    </script>
@endsection
