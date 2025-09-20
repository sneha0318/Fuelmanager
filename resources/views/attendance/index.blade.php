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
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Attendance Management</h4>
                    <p class="text-muted mb-0">Manage attendance records here</p>
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
                                        <td>{{ $attendance->operator->name }}</td>
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
