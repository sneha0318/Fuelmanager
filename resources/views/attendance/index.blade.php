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
});
</script>

@endsection

@section('css')
<style>

/* Attendance page background */
.attendance-management-page {
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


/* Heading */
.attendance-management-page .page-title-box h4 {
    font-size: 30px !important;
    font-weight: 900 !important;
    color: #073b4c !important;
    margin-bottom: 6px !important;
}
.attendance-management-page .attendance-green-text {
    color: #20b486 !important;
}


/* Subtitle */
.attendance-management-page .page-title-box p {
    font-size: 16px !important;
    font-weight: 600 !important;
    color: #477080 !important;
    margin-bottom: 0 !important;
}


/* =========================================
   COMMON MODULE TABLE CARD DESIGN
   ========================================= */

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


/* Table heading */
.attendance-management-page table th {
    color: #073b4c !important;
    font-weight: 600;
}


/* Table text */
.attendance-management-page table td {
    color: #315466 !important;
}


/* Date filter */
.attendance-management-page #daterange {
    background-color: #ffffff;
}


/* Reset button */
.attendance-management-page #resetFilter {
    border-color: #20b486;
    color: #20b486;
}

.attendance-management-page #resetFilter:hover {
    background: #20b486;
    color: #ffffff;
}


/* View button */
.attendance-management-page .edit-item-btn {
    background: #ffd66b !important;
    border-color: #ffd66b !important;
    color: #073b4c !important;
}
/* =========================================
   ATTENDANCE TABLE - SAME AS PUMP MANAGEMENT
========================================= */

/* Table Header */
.attendance-management-page .module-table-card table {
    border-collapse: separate !important;
    border-spacing: 0 0 !important;
}

.attendance-management-page .module-table-card thead {
    background: transparent !important;
}

.attendance-management-page .module-table-card thead tr {
    background: #2fb79b !important;
}

.attendance-management-page .module-table-card thead th {
    background: #2fb79b !important;
    color: #ffffff !important;

    font-size: 13px !important;
    font-weight: 700 !important;

    padding: 16px 14px !important;

    border: none !important;
    white-space: nowrap;
}

/* Left round corner */
.attendance-management-page .module-table-card thead th:first-child {
    border-top-left-radius: 15px !important;
}

/* Right round corner */
.attendance-management-page .module-table-card thead th:last-child {
    border-top-right-radius: 15px !important;
}


/* =========================================
   ATTENDANCE ROW COLOR
========================================= */

.attendance-management-page .module-table-card tbody tr {
    background: transparent !important;
    transition: all 0.25s ease;
}

.attendance-management-page .module-table-card tbody td {
    background: rgba(255, 255, 255, 0.72) !important;

    color: #294f58 !important;
    font-size: 14px !important;
    font-weight: 500 !important;

    border-bottom: 1px solid rgba(47, 183, 155, 0.10) !important;
}

/* First cell round */
.attendance-management-page .module-table-card tbody tr td:first-child {
    border-top-left-radius: 12px !important;
    border-bottom-left-radius: 12px !important;
}

/* Last cell round */
.attendance-management-page .module-table-card tbody tr td:last-child {
    border-top-right-radius: 12px !important;
    border-bottom-right-radius: 12px !important;
}


/* Row hover */
.attendance-management-page .module-table-card tbody tr:hover td {
    background: #eaf8f3 !important;
}


/* =========================================
   REMOVE OLD HEADER COLOR
========================================= */

.attendance-management-page table th {
    color: #ffffff !important;
    background: #2fb79b !important;
    font-weight: 700 !important;
}
/* Attendance table box ko heading ke paas upar lao */
.attendance-management-page > .row:nth-of-type(2) {
    margin-top: 10px !important;
    transform: none !important;
    position: static !important;
    margin-bottom: 0 !important;
}

/* Heading aur table ke beech ka extra gap remove */
.attendance-management-page .page-title-box {
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}
.attendance-management-page .attendance-table-row {
    margin-top: 10px !important;
    padding-top: 0 !important;
}

.attendance-management-page .module-table-card {
    margin-top: 0 !important;
}

</style>
@endsection

@section('content')

    <div class="attendance-management-page">

        <div class="row">

        <div class="col-12">
           
<div class="page-title-box d-flex align-items-center justify-content-between">
    <div>
        <h4 class="fs-16 fw-semibold mb-1">
            ATTENDANCE <span class="attendance-green-text">MANAGEMENT :</span>
        </h4>
        <p class="text-muted mb-0">
            Manage attendance records here
        </p>
    </div>

    <a href="{{ route('attendance.create') }}" class="btn btn-success">
        <i class="fas fa-plus"></i> Add Attendance
    </a>

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
                    {{-- Date Range Filter --}}
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <form method="GET" action="" id="dateFilterForm">
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
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-centered table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Operator</th>
                                    @if(auth()->user()->user_type === 'super_admin')
                                        <th>Pump Name</th>
                                    @endif
                                    <th>Shift</th>
                                    {{-- <th>Date</th> --}}
                                    <th>Shift Start Date</th>
                                    <th>Shift End Date</th>
                                    <th>Opening Time</th>
                                    <th>Closing Time</th>
                                    <th>Total Working Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $key => $attendance)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $attendance->operator->user_name ?? 'N/A' }}</td>
                                        @if(auth()->user()->user_type === 'super_admin')
                                            <td>
                                                <span>
                                                    {{ $attendance->shift->pump->pump_name ?? 'N/A' }}
                                                </span>
                                            </td>
                                        @endif
                                        <td>
                                            {{ $attendance->shift->shift_name }}
                                            <br>
                                            <small class="text-muted">
                                                {{ $attendance->shift->start_time->format('H:i:s') }} -
                                                {{ $attendance->shift->end_time->format('H:i:s') }}
                                            </small>
                                        </td>
                                        {{-- <td>{{ $attendance->date->format('d M Y') }}</td> --}}
                                        <td>
                                            @if($attendance->shift->start_date)
                                                <span>{{ \Carbon\Carbon::parse($attendance->shift->start_date)->format('d M Y') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($attendance->shift->end_date)
                                                <span>{{ \Carbon\Carbon::parse($attendance->shift->end_date)->format('d M Y') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $attendance->opening_time_only }}
                                            <br>
                                            @php
                                                $shiftStart = \Carbon\Carbon::parse($attendance->shift->start_time);
                                                $shiftEnd = \Carbon\Carbon::parse($attendance->shift->end_time);
                                                $opening = \Carbon\Carbon::createFromFormat('H:i:s', $attendance->opening_time_only);
                                                
                                                // Use shift start date for calculations instead of attendance date
                                                $shiftStartDate = $attendance->shift->start_date ? 
                                                    \Carbon\Carbon::parse($attendance->shift->start_date) : 
                                                    \Carbon\Carbon::parse($attendance->date);
                                                    
                                                $attendanceDate = \Carbon\Carbon::parse($attendance->date);
                                                
                                                // Create full datetime objects for proper comparison using shift start date
                                                $shiftStartDateTime = $shiftStartDate->copy()->setTimeFromTimeString($shiftStart->format('H:i:s'));
                                                $openingDateTime = $attendanceDate->copy()->setTimeFromTimeString($opening->format('H:i:s'));
                                                
                                                // Check if shift spans midnight (end time is earlier than start time)
                                                if ($shiftEnd->lt($shiftStart)) {
                                                    // For overnight shifts, we need to handle different scenarios:
                                                    // 1. Opening before shift start (same day) = early
                                                    // 2. Opening after shift start (same day) = late 
                                                    // 3. Opening after midnight (next day) = depends on shift end time
                                                    
                                                    if ($opening->lt($shiftStart)) {
                                                        // Opening is before shift start time (e.g., 18:00 for 19:00 shift)
                                                        // This is EARLY - keep on same day
                                                        // Don't add day to opening
                                                    } else {
                                                        // Opening is after shift start time (e.g., 23:56 for 19:00 shift)
                                                        // This is LATE - keep on same day
                                                        // Don't add day to opening
                                                    }
                                                }
                                                
                                                // Calculate time difference (positive means late, negative means early)
                                                $timeDiff = $shiftStartDateTime->diffInMinutes($openingDateTime, false);
                                            @endphp
                                            @if($timeDiff > 0)
                                                @php
                                                    $lateHour = intdiv($timeDiff, 60);
                                                    $lateMin = str_pad($timeDiff % 60, 2, '0', STR_PAD_LEFT);
                                                @endphp
                                                <span class="badge bg-danger">
                                                    -{{ $lateHour }}:{{ $lateMin }} hours late
                                                </span>
                                            @elseif($timeDiff < 0)
                                                @php
                                                    $earlyHour = intdiv(abs($timeDiff), 60);
                                                    $earlyMin = str_pad(abs($timeDiff) % 60, 2, '0', STR_PAD_LEFT);
                                                @endphp
                                                <span class="badge bg-success">
                                                    +{{ $earlyHour }}:{{ $earlyMin }} hours early
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $attendance->closing_time_only ?: 'Not Closed' }}
                                            @php
                                                $shiftEnd = $attendance->shift ? \Carbon\Carbon::parse($attendance->shift->end_time) : null;
                                                $closing = $attendance->closing_time_only ? \Carbon\Carbon::createFromFormat('H:i:s', $attendance->closing_time_only) : null;
                                                
                                                if ($shiftEnd && $closing) {
                                                    // Create datetime objects for proper comparison
                                                    $shiftStartTime = \Carbon\Carbon::parse($attendance->shift->start_time);
                                                    $attendanceDate = \Carbon\Carbon::parse($attendance->date);
                                                    
                                                    // Use shift end date for calculations instead of attendance date
                                                    $shiftEndDate = $attendance->shift->end_date ? 
                                                        \Carbon\Carbon::parse($attendance->shift->end_date) : 
                                                        \Carbon\Carbon::parse($attendance->date);
                                                    
                                                    // Set full datetime for shift end using shift end date
                                                    $shiftEndDateTime = $shiftEndDate->copy()->setTimeFromTimeString($shiftEnd->format('H:i:s'));
                                                    
                                                    // Set full datetime for closing using attendance date
                                                    $closingDateTime = $attendanceDate->copy()->setTimeFromTimeString($closing->format('H:i:s'));
                                                    
                                                    // Handle multi-day shifts and overnight calculations
                                                    // If shift has different start and end dates, use those dates directly
                                                    if ($attendance->shift->start_date && $attendance->shift->end_date && 
                                                        $attendance->shift->start_date !== $attendance->shift->end_date) {
                                                        // Multi-day shift: shift end is already on the correct date
                                                        // No need to add days
                                                    } else {
                                                        // Single day shift or same-day shift that might span midnight
                                                        // Check if shift spans midnight (end time is earlier than start time)
                                                        if ($shiftEnd->lt($shiftStartTime)) {
                                                            // For attendance on the same day as shift start, closing might be next day
                                                            if ($attendanceDate->isSameDay($shiftEndDate) || 
                                                                ($closing->lt($shiftStartTime) && $attendanceDate->isAfter($shiftEndDate))) {
                                                                $closingDateTime->addDay();
                                                            }
                                                        }
                                                    }
                                                    
                                                    // Now compare the full datetime objects
                                                    if ($closingDateTime->gt($shiftEndDateTime)) {
                                                        $overtimeMinutes = $shiftEndDateTime->diffInMinutes($closingDateTime);
                                                        $overtimeHour = floor($overtimeMinutes / 60);
                                                        $overtimeMin = str_pad($overtimeMinutes % 60, 2, '0', STR_PAD_LEFT);
                                                        echo '<br><span class="badge bg-success">+' . $overtimeHour . ':' . $overtimeMin . ' hours (Overtime)</span>';
                                                    } elseif ($closingDateTime->lt($shiftEndDateTime)) {
                                                        $earlyMinutes = $closingDateTime->diffInMinutes($shiftEndDateTime);
                                                        $earlyHour = floor($earlyMinutes / 60);
                                                        $earlyMin = str_pad($earlyMinutes % 60, 2, '0', STR_PAD_LEFT);
                                                        echo '<br><span class="badge bg-danger">-' . $earlyHour . ':' . $earlyMin . ' hours (Closed Early)</span>';
                                                    }
                                                }
                                            @endphp
                                        </td>
                                        <td>
                                            @if($attendance->total_working_time)
                                                <span class="badge bg-primary">
                                                    {{ $attendance->formatted_working_time }}
                                                </span>
                                                <br>
                                                <small class="text-muted">
                                                    ({{ number_format($attendance->total_working_time, 2) }} minutes)
                                                </small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('attendance.show', $attendance->id) }}"
                                                class="btn btn-sm btn-warning edit-item-btn">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ auth()->user()->user_type === 'super_admin' ? '10' : '9' }}" class="text-center">No attendance records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($attendances->hasPages())
                        <div class="mt-3">
                            {{ $attendances->links() }}
                        </div>
                    @endif
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

