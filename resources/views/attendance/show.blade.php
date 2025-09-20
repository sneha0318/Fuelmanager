@extends('layouts.master-without-page-title')

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fs-16 fw-semibold mb-1">Attendance Details</h4>
                    <p class="text-muted mb-0">View detailed attendance information</p>
                </div>
                <div>
                    <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="table table-centered table-nowrap mb-0">
                                    <tbody>
                                        <tr>
                                            <th style="width: 200px;">Operator</th>
                                            <td>{{ $attendance->operator->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Shift</th>
                                            <td>
                                                {{ $attendance->shift->shift_name }}
                                                <br>
                                                <small class="text-muted">
                                                    {{ $attendance->shift->start_time->format('H:i:s') }} -
                                                    {{ $attendance->shift->end_time->format('H:i:s') }}
                                                </small>
                                            </td>
                                        </tr>
                                        <tr>
                                            {{-- <th>Date</th>
                                            <td>{{ $attendance->date->format('d M Y') }}</td> --}}
                                        </tr>
                                        <tr>
                                            <th>Shift Start Date</th>
                                            <td>
                                                @if($attendance->shift->start_date)
                                                    <span>{{ \Carbon\Carbon::parse($attendance->shift->start_date)->format('d M Y') }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Shift End Date</th>
                                            <td>
                                                @if($attendance->shift->end_date)
                                                    <span>{{ \Carbon\Carbon::parse($attendance->shift->end_date)->format('d M Y') }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Opening Time</th>
                                            <td>
                                                {{ $attendance->opening_time_only }}
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
                                                    <br>
                                                    <span class="badge bg-danger">
                                                        -{{ $lateHour }}:{{ $lateMin }} hours late
                                                    </span>
                                                @elseif($timeDiff < 0)
                                                    @php
                                                        $earlyHour = intdiv(abs($timeDiff), 60);
                                                        $earlyMin = str_pad(abs($timeDiff) % 60, 2, '0', STR_PAD_LEFT);
                                                    @endphp
                                                    <br>
                                                    <span class="badge bg-success">
                                                        +{{ $earlyHour }}:{{ $earlyMin }} hours early
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Closing Time</th>
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
                                        </tr>
                                        <tr>
                                            <th>Total Working Time</th>
                                            <td>
                                                @if($attendance->total_working_time)
                                                    <span class="badge bg-primary fs-6">
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
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Duration Summary</h5>
                                </div>
                                <div class="card-body">
                                    @if($attendance->total_working_time)
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-clock fa-2x text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Total Working Time</h6>
                                                <h4 class="mb-0">{{ $attendance->formatted_working_time }}</h4>
                                                <small class="text-muted">({{ number_format($attendance->total_working_time, 2) }} minutes)</small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-calendar-check fa-2x text-success"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Status</h6>
                                                <h4 class="mb-0 text-success">Completed</h4>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-clock fa-2x text-warning"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1">Status</h6>
                                                <h4 class="mb-0 text-warning">In Progress</h4>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
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
            font-weight: 600;
        }
        .badge {
            font-size: 0.75rem;
        }
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,.125);
        }
    </style>
@endpush
