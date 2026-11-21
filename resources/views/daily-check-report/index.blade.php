@extends('layouts.master-without-page-title')

@section('title')
    Daily Check Report
@endsection

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
            const userSelect = document.getElementById('user_id');
            const nozzleSelect = document.getElementById('nozzle_id');

            // Function to load nozzles
            function loadNozzles(userId) {
                if (!userId) {
                    nozzleSelect.innerHTML = '<option value="">- Select -</option>';
                    return;
                }

                // Show loading state
                nozzleSelect.innerHTML = '<option value="">Loading...</option>';

                // Fetch nozzles for the selected user
                fetch(`/api/nozzles/by-user/${userId}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    nozzleSelect.innerHTML = '<option value="">- Select -</option>';
                    data.forEach(nozzle => {
                        const option = document.createElement('option');
                        option.value = nozzle.id;
                        option.textContent = `${nozzle.pump_name} - ${nozzle.item_name || 'N/A'} (Nozzle #${nozzle.nozzle_number})`;
                        nozzleSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching nozzles:', error);
                    nozzleSelect.innerHTML = '<option value="">Error loading nozzles</option>';
                });
            }

            // Load nozzles on page load if a user is already selected
            if (userSelect.value) {
                loadNozzles(userSelect.value);
            }

            // Load nozzles when user selection changes
            userSelect.addEventListener('change', function () {
                loadNozzles(this.value);
            });
        });
    });
    </script>
@endsection

@section('content')

<style>

    /* ==============================
       DAILY CHECK REPORT BACKGROUND
    ============================== */

    .daily-check-report-page {
     min-height: calc(100vh - 70px);

    width: calc(100% + 40px);
    margin-left: -20px;
    margin-right: -20px;
margin-top: -30px;
    padding: 25px 25px 50px 25px;

    background-image: url("{{ asset('build/images/dashboard-bg.png') }}");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;

    box-sizing: border-box;


    }


    /* ==============================
       TITLE - ATTENDANCE STYLE
    ============================== */

    .daily-check-report-page .page-title-box h4 {
        font-size: 30px !important;
        font-weight: 900 !important;
        color: #073b4c !important;
        margin-bottom: 6px !important;
    }


    /* Green part of title */

    .daily-check-report-page .daily-check-green-text {
        color: #20b486 !important;
    }


    /* Subtitle */

    .daily-check-report-page .page-title-box p {
        font-size: 16px !important;
        font-weight: 600 !important;
        color: #477080 !important;
        margin-bottom: 0 !important;
    }


    /* ==============================
       CARDS
    ============================== */

  <style>
.module-table-card {
    position: relative !important;
    overflow: hidden !important;

    background: linear-gradient(
        135deg,
        #ffffff 0%,
        #f3fffb 45%,
        #e5faf3 100%
    ) !important;

    border: 1px solid rgba(32, 180, 134, 0.12) !important;
    border-radius: 20px !important;

    box-shadow:
        0 10px 35px rgba(20, 80, 70, 0.10),
        0 2px 8px rgba(32, 180, 134, 0.05) !important;

    isolation: isolate;
}

.module-table-card::before {
    content: "";
    position: absolute;
    width: 650px;
    height: 650px;
    top: -390px;
    right: -180px;

    background: radial-gradient(
        circle,
        rgba(32, 180, 134, 0.18) 0%,
        rgba(32, 180, 134, 0.08) 35%,
        rgba(32, 180, 134, 0) 70%
    );

    border-radius: 50%;
    z-index: 0;
    pointer-events: none;
}

.module-table-card::after {
    content: "";
    position: absolute;
    width: 750px;
    height: 250px;
    left: -180px;
    bottom: -145px;

    background: radial-gradient(
        ellipse,
        rgba(66, 196, 158, 0.16) 0%,
        rgba(66, 196, 158, 0.08) 35%,
        rgba(66, 196, 158, 0) 72%
    );

    border-radius: 50%;
    transform: rotate(-8deg);

    z-index: 0;
    pointer-events: none;
}

.module-table-card .card-body {
    position: relative !important;
    z-index: 2 !important;
    background: transparent !important;
}

.module-table-card table,
.module-table-card tbody,
.module-table-card tr {
    background: transparent !important;
}

.module-table-card thead {
    background: rgba(255, 255, 255, 0.72) !important;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.module-table-card thead th {
    color: #123f45 !important;
    font-size: 13px;
    font-weight: 700;
    padding: 15px 14px;
    border-bottom: 1px solid rgba(32, 180, 134, 0.16) !important;
}

.module-table-card tbody td {
    background: rgba(255, 255, 255, 0.42) !important;
    color: #294f58 !important;
    font-size: 14px;
    font-weight: 500;
    padding: 15px 14px;
    border-bottom: 1px solid rgba(32, 180, 134, 0.10) !important;
    vertical-align: middle;
    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
}

.module-table-card tbody tr {
    transition: all 0.25s ease;
}

.module-table-card tbody tr:hover {
    transform: translateX(2px);
    background: rgba(255, 255, 255, 0.60) !important;
}

.module-table-card tbody tr:hover td {
    background: rgba(255, 255, 255, 0.58) !important;
}

.module-table-card .table-responsive {
    position: relative;
    z-index: 3;
}

.module-table-card .table-responsive::before {
    content: "";
    position: absolute;
    left: 4%;
    right: 4%;
    top: 0;
    height: 2px;

    background: linear-gradient(
        90deg,
        transparent,
        rgba(32, 180, 134, 0.35),
        transparent
    );

    border-radius: 50%;
    pointer-events: none;
}

.module-table-card .badge {
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.module-table-card .btn {
    border-radius: 8px;
    transition: all 0.2s ease;
}

.module-table-card .btn:hover {
    transform: translateY(-1px);
}

.module-table-card tbody td.text-center {
    background: transparent !important;
    padding: 35px 15px;
}

@media (max-width: 768px) {
    .module-table-card {
        border-radius: 16px !important;
    }

    .module-table-card thead th,
    .module-table-card tbody td {
        padding: 12px 10px;
    }
}
/* =====================================================
   DAILY CHECK REPORT
   SAME DESIGN AS PUMP / ATTENDANCE / SALES / LOAN
===================================================== */

/* Table structure */
.daily-check-report-page .module-table-card table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
}


/* =====================================================
   GREEN ROUNDED TABLE HEADER
===================================================== */

.daily-check-report-page .module-table-card thead {
    background: transparent !important;
}

.daily-check-report-page .module-table-card thead tr {
    background: #2fb79b !important;
}

.daily-check-report-page .module-table-card thead th {
    background: #2fb79b !important;
    color: #ffffff !important;

    font-size: 13px !important;
    font-weight: 700 !important;

    padding: 16px 14px !important;

    border: none !important;
    white-space: nowrap;
}

/* Header rounded corners */
.daily-check-report-page .module-table-card thead th:first-child {
    border-top-left-radius: 15px !important;
}

.daily-check-report-page .module-table-card thead th:last-child {
    border-top-right-radius: 15px !important;
}


/* =====================================================
   TABLE BODY
===================================================== */

.daily-check-report-page .module-table-card tbody {
    background: transparent !important;
}

.daily-check-report-page .module-table-card tbody tr {
    background: transparent !important;
    transition: all 0.25s ease;
}

.daily-check-report-page .module-table-card tbody td {
    background: rgba(255, 255, 255, 0.72) !important;

    color: #294f58 !important;

    font-size: 14px !important;
    font-weight: 500 !important;

    padding: 15px 14px !important;

    border-bottom: 1px solid rgba(47, 183, 155, 0.10) !important;

    vertical-align: middle;

    backdrop-filter: blur(3px);
    -webkit-backdrop-filter: blur(3px);
}


/* =====================================================
   ROUND ROW CORNERS
===================================================== */

.daily-check-report-page .module-table-card tbody tr td:first-child {
    border-top-left-radius: 12px !important;
    border-bottom-left-radius: 12px !important;
}

.daily-check-report-page .module-table-card tbody tr td:last-child {
    border-top-right-radius: 12px !important;
    border-bottom-right-radius: 12px !important;
}


/* =====================================================
   ROW HOVER
===================================================== */

.daily-check-report-page .module-table-card tbody tr:hover {
    transform: translateX(2px);
}

.daily-check-report-page .module-table-card tbody tr:hover td {
    background: #eaf8f3 !important;
}


/* =====================================================
   TOTAL ROW
===================================================== */

.daily-check-report-page .module-table-card tbody tr.table-secondary td {
    background: #e7f6f0 !important;
    color: #073b4c !important;
    font-weight: 700 !important;
}


/* =====================================================
   EMPTY ROW
===================================================== */

.daily-check-report-page .module-table-card tbody td.text-center {
    background: transparent !important;
    padding: 35px 15px !important;
}


/* =====================================================
   BADGES
===================================================== */

.daily-check-report-page .module-table-card .badge {
    padding: 6px 10px !important;
    border-radius: 20px !important;

    font-size: 11px !important;
    font-weight: 700 !important;
}


/* =====================================================
   BUTTONS
===================================================== */

.daily-check-report-page .module-table-card .btn {
    border-radius: 8px !important;
    transition: all 0.2s ease;
}

.daily-check-report-page .module-table-card .btn:hover {
    transform: translateY(-1px);
}


/* =====================================================
   FORM CARD
===================================================== */

.daily-check-report-page .module-table-card .card-title {
    color: #073b4c !important;
    font-weight: 700 !important;
}


/* Form labels */
.daily-check-report-page .module-table-card .form-label {
    color: #073b4c !important;
    font-weight: 600 !important;
}


/* =====================================================
   REMOVE OLD TABLE HEADER OVERRIDE
===================================================== */

.daily-check-report-page table th {
    color: #ffffff !important;
    background: #2fb79b !important;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 768px) {

    .daily-check-report-page .module-table-card {
        border-radius: 16px !important;
    }

    .daily-check-report-page .module-table-card thead th,
    .daily-check-report-page .module-table-card tbody td {
        padding: 12px 10px !important;
    }
}




</style>

<div class="daily-check-report-page">

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                 <h4 class="fs-16 fw-semibold mb-1">
    DAILY CHECK <span class="daily-check-green-text">REPORT :</span>
</h4>

<p class="text-muted mb-0">
    Detail Report
</p>
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
  <div class="card module-table-card">
    <div class="card-body">
        <h5 class="card-title">Find Report</h5>
                    <form method="GET" action="{{ route('daily-check-report.index') }}" id="dateFilterForm">
                        <div class="row">
                            <!-- Date Range Filter -->
                            <div class="col-md-3 mb-3">
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
                            <div class="col-md-3 mb-3">
                                <label for="user_id" class="form-label">Select User</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="">- Select -</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $selectedUserId == $user->id ? 'selected' : '' }}>
                                            {{ $user->user_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Select Payment Item -->
                            <div class="col-md-3 mb-3">
                                <label for="payment_item_id" class="form-label">Select Payment Item</label>
                                <select class="form-select" id="payment_item_id" name="payment_item_id">
                                    <option value="">- Select -</option>
                                    @foreach($paymentItems as $paymentItem)
                                        <option value="{{ $paymentItem->id }}" {{ $selectedPaymentItemId == $paymentItem->id ? 'selected' : '' }}>
                                            {{ $paymentItem->item_name ?? 'Unnamed Item' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Select Nozzle -->
                            <div class="col-md-3 mb-3">
                                <label for="nozzle_id" class="form-label">Select Nozzle</label>
                                <select class="form-select" id="nozzle_id" name="nozzle_id">
                                    <option value="">- Select -</option>
                                    @foreach($nozzles as $nozzle)
                                        <option value="{{ $nozzle->id }}" {{ $selectedNozzleId == $nozzle->id ? 'selected' : '' }}>
                                            {{ $nozzle->name }} ({{ $nozzle->pump->pump_name ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('daily-check-report.index') }}" class="btn btn-secondary">Reset</a>
                            <button type="submit" class="btn btn-success">Find</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
        <div class="card module-table-card">
    <div class="card-body">
        <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Sale Date</th>
                                    <th>Operator</th>
                                    <th>Pump</th>
                                    <th>Nozzle</th>
                                    <th>Payment Item</th>
                                    <th>Bill No</th>
                                    <th>Customer Name</th>
                                    <th>Amount</th>
                                    <th>Sale</th>
                                    <th>Open Meter</th>
                                    <th>Close Meter</th>
                                    <th>Test Litre</th>
                                    <th>Rate</th>
                                    <th>Created On</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $filteredEntries = $paymentEntries->filter(function($entry) {
                                        // Show all sales (positive amounts)
                                        if ($entry->amount > 0) {
                                            return true;
                                        }
                                        // Hide all payments (negative amounts)
                                        return false;
                                    });
                                @endphp
                                @forelse($filteredEntries as $index => $entry)
                                    @php
                                        $latestSale = $entry->nozzle && $entry->nozzle->sales ? $entry->nozzle->sales()->where('status', 'Completed')->latest()->first() : null;
                                        $isCreditSale = $entry->paymentItem && $entry->paymentItem->item_name === 'Credit Sale';
                                        $customer = $isCreditSale ? $entry->customer : null;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $entry->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            @if($entry->nozzle && $entry->nozzle->pump)
                                                @php
                                                    $activeShift = $entry->nozzle->pump->shifts()
                                                        ->where('start_date', '<=', $entry->created_at)
                                                        ->where(function($query) use ($entry) {
                                                            $query->whereNull('end_date')
                                                                  ->orWhere('end_date', '>=', $entry->created_at);
                                                        })
                                                        ->where('nozzle_id', $entry->nozzle_id)
                                                        ->orderBy('start_date', 'desc')
                                                        ->first();
                                                    
                                                    // Fallback: try to find any shift for this nozzle on the same date
                                                    if (!$activeShift) {
                                                        $activeShift = $entry->nozzle->pump->shifts()
                                                            ->whereDate('start_date', $entry->created_at->format('Y-m-d'))
                                                            ->where('nozzle_id', $entry->nozzle_id)
                                                            ->orderBy('start_date', 'desc')
                                                            ->first();
                                                    }
                                                @endphp
                                                {{ $activeShift && $activeShift->pumpUser ? $activeShift->pumpUser->user_name : 'N/A' }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>{{ $entry->nozzle->pump->pump_name ?? 'N/A' }}</td>
                                        <td>
                                            @if($entry->nozzle)
                                                Nozzle #{{ $entry->nozzle->nozzle_number }}
                                                @if($entry->nozzle->item)
                                                            - {{ $entry->nozzle->item->name }} <span class="badge bg-info">{{ ($entry->nozzle->item->item_type ?? 'Litre') == 'Litre' ? 'L' : strtoupper($entry->nozzle->item->item_type ?? 'Litre') }}</span>
                                                @endif
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                            <td>
                                                {{ $entry->paymentItem->item_name ?? 'N/A' }}
                                                @if($entry->paymentItem && $entry->paymentItem->item_type)
                                                    <span class="badge bg-primary">{{ $entry->paymentItem->item_type == 'Litre' ? 'L' : strtoupper($entry->paymentItem->item_type) }}</span>
                                                @endif
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
                                            @if($isCreditSale && $customer)
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#customerModal{{ $customer->id }}">
                                                    {{ $customer->name }}
                                                </a>

                                                <!-- Customer Details Modal -->
                                                <div class="modal fade" id="customerModal{{ $customer->id }}" tabindex="-1" aria-labelledby="customerModalLabel{{ $customer->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title" id="customerModalLabel{{ $customer->id }}">
                                                                    <i class="fas fa-user me-2"></i>Customer Details
                                                                </h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Name</label>
                                                                    <p class="form-control-static">{{ $customer->name }}</p>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Company</label>
                                                                    <p class="form-control-static">{{ $customer->company ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Phone Number</label>
                                                                    <p class="form-control-static">{{ $customer->phone_no ?? 'N/A' }}</p>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold">Address</label>
                                                                    <p class="form-control-static">{{ $customer->address ?? 'N/A' }}</p>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ number_format(abs($entry->amount), 2) }}</td>
                                        <td>
                                            @php
                                                $unit = $entry->paymentItem->item_type ?? ($entry->nozzle->item->item_type ?? 'Litre');
                                                $unitLabel = $unit == 'Litre' ? 'L' : strtoupper($unit);
                                            @endphp
                                            {{ number_format($entry->litre, 2) }} <span class="text-muted">{{ $unitLabel }}</span>
                                        </td>
                                        <td>
                                            {{ $latestSale ? number_format($latestSale->open_meter, 2) : '0.00' }}
                                            <span class="text-muted">{{ $unitLabel }}</span>
                                        </td>
                                        <td>
                                            {{ $latestSale ? number_format($latestSale->close_meter, 2) : '0.00' }}
                                            <span class="text-muted">{{ $unitLabel }}</span>
                                        </td>
                                        <td>
                                            {{ $latestSale ? number_format($latestSale->test_litre, 2) : '0.00' }}
                                            <span class="text-muted">{{ $unitLabel }}</span>
                                        </td>
                                        <td>
                                            {{ number_format($entry->rate, 2) }} <span class="text-muted">Rs/{{ $unitLabel }}</span>
                                        </td>
                                        <td>
                                            <div>{{ $entry->created_at->format('d-m-y') }}</div>
                                            <div class="text-muted small">{{ $entry->created_at->setTimezone('Asia/Kolkata')->format('H:i') }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="15" class="text-center">No payment entries found for the selected criteria.</td>
                                    </tr>
                                @endforelse

                                @if(count($filteredEntries) > 0)
                                    @php
                                        $totalAmount = $filteredEntries->sum(function($entry) {
                                            return abs($entry->amount);
                                        });
                                        $totalLitre = $filteredEntries->sum('litre');
                                    @endphp
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="8" class="text-end">Total:</td>
                                        <td>{{ number_format($totalAmount, 2) }}</td>
                                        <td>
                                            {{ number_format($totalLitre, 2) }}
                                            <span class="text-muted">KG/L</span>
                                        </td>
                                        <td colspan="5"></td>
                                    </tr>
                                @endif
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