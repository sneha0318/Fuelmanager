@extends('layouts.master-without-page-title')

@section('css')
<style>
    /* Mobile-specific styles for action buttons */
    @media (max-width: 768px) {
        /* Action buttons spacing for mobile */
        .table tbody td .btn {
            margin: 1px !important;
            flex-shrink: 0;
            font-size: 12px !important;
            padding: 6px 8px !important;
        }

        .table tbody td .btn-sm {
            font-size: 11px !important;
            padding: 4px 6px !important;
        }

        /* Ensure action buttons container has proper spacing */
        .table tbody td:last-child {
            white-space: nowrap;
        }

        .table tbody td:last-child .btn {
            margin-right: 1px !important;
        }

        .table tbody td:last-child .btn:last-child {
            margin-right: 0 !important;
        }

        /* Table responsive adjustments for mobile */
        .table-responsive {
            overflow-x: auto;
        }

        /* Additional mobile text adjustments */
        .table tbody td {
            font-size: 13px !important;
            padding: 8px 6px !important;
        }

        .table thead th {
            font-size: 12px !important;
            padding: 8px 6px !important;
        }

        /* Header buttons alignment for mobile */
        .d-flex.justify-content-between.align-items-center {
            flex-direction: column !important;
            align-items: flex-start !important;
        }

        .d-flex.justify-content-between.align-items-center .btn {
            margin-top: 10px;
            font-size: 12px;
            padding: 8px 12px;
        }

        /* Make Payment button alignment */
        .text-end {
            text-align: left !important;
        }

        .text-end .btn {
            width: 100%;
            margin-top: 15px;
        }
    }

    /* Extra small mobile devices */
    @media (max-width: 576px) {
        .table tbody td {
            font-size: 12px !important;
            padding: 6px 4px !important;
        }

        .table thead th {
            font-size: 11px !important;
            padding: 6px 4px !important;
        }

        .table tbody td .btn {
            font-size: 10px !important;
            padding: 4px 6px !important;
        }

        .table tbody td .btn-sm {
            font-size: 9px !important;
            padding: 3px 5px !important;
        }
    }
</style>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        function calculateRemainingAmount(loanId, totalAmount) {
            const payableAmount = parseFloat(document.querySelector('#amount').value) || 0;
            const remainingAmount = totalAmount - payableAmount;
            document.querySelector('#remaining_amount').value = remainingAmount.toFixed(2);
        }
    </script>
@endsection

@section('title')
    @if(isset($fromPartyList) && $fromPartyList)
        {{ $loan->name }} - Payment Details
    @else
        {{ $loan->name }} - Loan Details
    @endif
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Breadcrumb Navigation -->
        {{-- @if(request()->headers->get('referer') && str_contains(request()->headers->get('referer'), 'party-list'))
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('party-list.index') }}" class="text-decoration-none">
                            <i class="fas fa-users me-1"></i>Party List
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        @if(isset($fromPartyList) && $fromPartyList)
                            <i class="fas fa-user me-1"></i>{{ $loan->name }} - Payment Details
                        @else
                            <i class="fas fa-user me-1"></i>{{ $loan->name }} - Loan Details
                        @endif
                    </li>
                </ol>
            </nav>
        @endif --}}

        <!-- Back to Loans button when NOT coming from Party List -->
        @if(!isset($fromPartyList) || !$fromPartyList)
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">{{ $loan->name }} - Loan Details</h4>
            <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Loans
            </a>
        </div>
        @endif

        @if(auth()->user()->user_type === 'super_admin' || ($loan->pump && $loan->pump->pumpUsers->contains('email', auth()->user()->email)))
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Customer Details -->
            @if(!isset($fromPartyList) || !$fromPartyList)
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Loan Details</h4>
                    @if(request()->headers->get('referer') && str_contains(request()->headers->get('referer'), 'party-list'))
                        <a href="{{ route('party-list.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to Party List
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Name</label>
                                <p class="form-control-static">{{ $loan->name }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Company</label>
                                <p class="form-control-static">{{ $loan->company }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Address</label>
                                <p class="form-control-static">{{ $loan->address }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Phone</label>
                                <p class="form-control-static">{{ $loan->phone_no }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Pump</label>
                                <p class="form-control-static">{{ $loan->pump->pump_name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Back to Party List button when coming from Party List -->
            @if(isset($fromPartyList) && $fromPartyList)
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ $loan->name }} - Payments</h4>
                <a href="{{ route('party-list.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Party List
                </a>
            </div>
            @endif

            <!-- Make Payment Button -->
            @if(!isset($fromPartyList) || !$fromPartyList)
            <div class="text-end mt-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#makePaymentModal">
                    <i class="fas fa-money-bill-wave me-1"></i> Make Payment
                </button>
            </div>
            @endif

            <!-- Make Payment Modal -->
            @if(!isset($fromPartyList) || !$fromPartyList)
            <div class="modal fade" id="makePaymentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title text-dark">
                                <i class="fas fa-money-bill-wave me-2"></i>Make Payment
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('loans.payment', $loan) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Customer Name</label>
                                        <input type="text" class="form-control" value="{{ $loan->name }}" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Total Amount</label>
                                        @php
                                            $totalAmount = $loan->paymentEntries->where('amount', '>', 0)->sum('amount') - abs($loan->paymentEntries->where('amount', '<', 0)->sum('amount'));
                                        @endphp
                                        <input type="text" class="form-control" id="total_amount" value="{{ number_format($totalAmount, 2) }}" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="amount" class="form-label">Payable Amount</label>
                                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required
                                               oninput="calculateRemainingAmount({{ $loan->id }}, {{ $totalAmount }})">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="remaining_amount" class="form-label">Remaining Amount</label>
                                        <input type="text" class="form-control" id="remaining_amount" 
                                               value="{{ number_format($totalAmount, 2) }}" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="payment_method" class="form-label">Payment Method</label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="">Select Payment Method</option>
                                            @foreach($paymentItems as $paymentItem)
                                                <option value="{{ $paymentItem->name }}">{{ $paymentItem->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check me-1"></i>Make Payment
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Payments Table -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="card-title">Payments</h4>
                </div>
                <div class="card-body">
                    @if($loan->paymentEntries->where('amount', '<', 0)->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Payment Method</th>
                                        <th>Bill No</th>
                                        <th>Amount</th>
                                        @if(!isset($fromPartyList) || !$fromPartyList)
                                        <th>Actions</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php 
                                        $billNumber = 1; 
                                        $payments = $loan->paymentEntries->where('amount', '<', 0)->sortBy('created_at');
                                    @endphp
                                    @foreach($payments as $entry)
                                        <tr>
                                            <td>{{ $entry->created_at->timezone('Asia/Kolkata')->format('Y-m-d H:i') }}</td>
                                            <td>{{ $entry->paymentItem->item_name ?? 'N/A' }}</td>
                                            <td>{{ $billNumber++ }}</td>
                                            <td>{{ number_format(abs($entry->amount), 2) }}</td>
                                            @if(!isset($fromPartyList) || !$fromPartyList)
                                            <td>
                                                @if(auth()->user()->user_type !== 'operator')
                                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editPaymentModal{{ $entry->id }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deletePaymentModal{{ $entry->id }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted">Not authorized</span>
                                                @endif
                                            </td>
                                            @endif
                                        </tr>

                                        <!-- Edit Payment Modal -->
                                        @if(!isset($fromPartyList) || !$fromPartyList)
                                        <div class="modal fade" id="editPaymentModal{{ $entry->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-light">
                                                        <h5 class="modal-title text-dark">
                                                            <i class="fas fa-edit me-2"></i>Edit Payment
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('payment-entries.update', $entry->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Amount</label>
                                                                <input type="number" step="0.01" class="form-control" name="amount" value="{{ abs($entry->amount) }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Payment Method</label>
                                                                <select class="form-select" name="payment_method" required>
                                                                    @foreach($paymentItems as $paymentItem)
                                                                        <option value="{{ $paymentItem->name }}" {{ $entry->paymentItem->item_name == $paymentItem->name ? 'selected' : '' }}>
                                                                            {{ $paymentItem->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="fas fa-edit me-2"></i>Update Payment
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Payment Modal -->
                                        <div class="modal fade" id="deletePaymentModal{{ $entry->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Payment</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-3">
                                                                <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                                                            </div>
                                                            <div>
                                                                <p class="mb-0">Are you sure you want to delete this payment of <strong>₹{{ number_format(abs($entry->amount), 2) }}</strong>?</p>
                                                                <p class="text-muted small mb-0">This action cannot be undone.</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="{{ route('payment-entries.destroy', $entry->id) }}" method="POST" class="d-inline m-0">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-delete-hover">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            No payments available for this customer.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Loans Table -->
            @if(!isset($fromPartyList) || !$fromPartyList)
            <div class="card mt-4">
                <div class="card-header">
                    <h4 class="card-title">Loans</h4>
                </div>
                <div class="card-body">
                    @if($loan->paymentEntries->where('amount', '>', 0)->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Nozzle</th>
                                        <th>Bill No</th>
                                        <th>Litre</th>
                                        <th>Rate</th>
                                        <th>Amount</th>
                                        <th>Driver Name</th>
                                        <th>Vehicle Number</th>
                                        <th>Vehicle Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($loan->paymentEntries->where('amount', '>', 0)->reverse() as $entry)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($entry->created_at)->timezone('Asia/Kolkata')->format('Y-m-d H:i') }}</td>
                                            <td>{{ $entry->nozzle->nozzle_number ?? 'N/A' }}</td>
                                            <td>
                                                @php
                                                    // Extract sequence number from bill_no (format: BILL-YYYYMMDD-XXX)
                                                    $billParts = explode('-', $entry->bill_no);
                                                    $sequenceNumber = count($billParts) >= 3 ? (int)$billParts[2] : $entry->bill_no;
                                                @endphp
                                                {{ $sequenceNumber }}
                                            </td>
                                            <td>{{ number_format($entry->litre, 2) }}</td>
                                            <td>{{ number_format($entry->rate, 2) }}</td>
                                            <td>{{ number_format($entry->amount, 2) }}</td>
                                            <td>{{ $entry->driver_name }}</td>
                                            <td>{{ $entry->vehicle_number }}</td>
                                            <td>{{ $entry->vehicle_name ?? 'N/A' }}</td>
                                            <td>
                                                @if(auth()->user()->user_type !== 'operator')
                                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editLoadModal{{ $entry->id }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteLoanModal{{ $entry->id }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted">Not authorized</span>
                                                @endif
                                            </td>
                                        </tr>

                                        <!-- Edit Loan Modal -->
                                        <div class="modal fade" id="editLoadModal{{ $entry->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-md">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-light">
                                                        <h5 class="modal-title text-dark">
                                                            <i class="fas fa-edit me-2"></i>Edit Loan
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="{{ route('payment-entries.update', $entry->id) }}" method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Litre</label>
                                                                <input type="number" step="0.01" class="form-control" name="litre" value="{{ $entry->litre }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Rate</label>
                                                                <input type="number" step="0.01" class="form-control" name="rate" value="{{ $entry->rate }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Driver Name</label>
                                                                <input type="text" class="form-control" name="driver_name" value="{{ $entry->driver_name }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Vehicle Number</label>
                                                                <input type="text" class="form-control" name="vehicle_number" value="{{ $entry->vehicle_number }}">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Vehicle Name</label>
                                                                <input type="text" class="form-control" name="vehicle_name" value="{{ $entry->vehicle_name }}">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                                                <i class="fas fa-times me-1"></i>Cancel
                                                            </button>
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="fas fa-edit me-2"></i>Update Loan
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Loan Modal -->
                                        <div class="modal fade" id="deleteLoanModal{{ $entry->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Loan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-3">
                                                                <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                                                            </div>
                                                            <div>
                                                                <p class="mb-0">Are you sure you want to delete this loan of <strong>₹{{ number_format($entry->amount, 2) }}</strong>?</p>
                                                                <p class="text-muted small mb-0">This action cannot be undone.</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="{{ route('payment-entries.destroy', $entry->id) }}" method="POST" class="d-inline m-0">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger btn-delete-hover">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            No loans available for this customer.
                        </div>
                    @endif
                </div>
            </div>
            @endif
        @else
            <div class="alert alert-danger">
                You do not have permission to view this loan.
            </div>
        @endif
    </div>
</div>
@endsection