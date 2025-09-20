<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Attendance::with(['operator', 'shift.pump']);

        // Filter by date range - default to today's data if no filters applied
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // If no date filters are provided, show today's data
        if (!$startDate && !$endDate) {
            $query->whereDate('date', \Carbon\Carbon::now()->format('Y-m-d'));
        } elseif ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        // Filter based on user type and pump assignments
        if ($user->user_type === 'super_admin') {
            // Super admin can see all attendance records
            // No additional filtering needed
        } else {
            // Admin users can only see attendance for their assigned pumps
            $query->whereHas('shift', function($shiftQuery) use ($user) {
                $shiftQuery->whereHas('pump.pumpUsers', function($pumpUserQuery) use ($user) {
                    $pumpUserQuery->where('email', $user->email)
                                  ->where('status', 'active');
                });
            });
        }

        $attendances = $query->paginate(15);

        return view('attendance.index', compact('attendances'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('attendance.index')
            ->with('info', 'Create attendance feature is not yet implemented.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'operator_id' => 'required|exists:users,id',
            'shift_id' => 'required|exists:shifts,id',
            'date' => 'required|date',
            'opening_meter_time' => 'required',
            'closing_meter_time' => 'nullable',
        ]);

        // Check for duplicate attendance
        $existingAttendance = Attendance::where([
            'operator_id' => $request->operator_id,
            'shift_id' => $request->shift_id,
            'date' => $request->date,
        ])->first();

        if ($existingAttendance) {
            return back()->with('error', 'Attendance record already exists for this operator, shift, and date.');
        }

        $timing = null;
        if ($request->filled('opening_meter_time') && $request->filled('closing_meter_time')) {
            // Combine date with time to create proper datetime
            $attendanceDate = $request->date;
            $openingDateTime = $attendanceDate . ' ' . date('H:i:s', strtotime($request->opening_meter_time));
            $closingDateTime = $attendanceDate . ' ' . date('H:i:s', strtotime($request->closing_meter_time));
            
            $opening = \Carbon\Carbon::parse($openingDateTime);
            $closing = \Carbon\Carbon::parse($closingDateTime);
            
            // Handle overnight shifts
            if ($closing->lt($opening)) {
                $closing->addDay();
            }
            
            $timing = $opening->diffInMinutes($closing);
        } elseif ($request->filled('opening_meter_time') && !$request->filled('closing_meter_time')) {
            // If only opening time is provided, timing will be null until closing time is added
            $timing = null;
        }

        $attendance = Attendance::create([
            'operator_id' => $request->operator_id,
            'shift_id' => $request->shift_id,
            'date' => $request->date,
            'timing' => $timing,
            'opening_meter_time' => $request->filled('opening_meter_time') ? 
                $request->date . ' ' . date('H:i:s', strtotime($request->opening_meter_time)) : null,
            'closing_meter_time' => $request->filled('closing_meter_time') ? 
                $request->date . ' ' . date('H:i:s', strtotime($request->closing_meter_time)) : null,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('attendance.index')
            ->with('success', 'Attendance record created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        $user = Auth::user();
        
        // Check if the user has permission to view this attendance record
        if ($user->user_type !== 'super_admin') {
            $hasPermission = $attendance->shift->pump->pumpUsers()
                ->where('email', $user->email)
                ->where('status', 'active')
                ->exists();
                
            if (!$hasPermission) {
                return redirect()->route('attendance.index')
                    ->with('error', 'You do not have permission to view this attendance record.');
            }
        }
        
        $attendance->load(['operator', 'shift']);
        return view('attendance.show', compact('attendance'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance)
    {
        $user = Auth::user();
        
        // Check if the user has permission to edit this attendance record
        if ($user->user_type !== 'super_admin') {
            $hasPermission = $attendance->shift->pump->pumpUsers()
                ->where('email', $user->email)
                ->where('status', 'active')
                ->exists();
                
            if (!$hasPermission) {
                return redirect()->route('attendance.index')
                    ->with('error', 'You do not have permission to edit this attendance record.');
            }
        }
        
        return redirect()->route('attendance.index')
            ->with('info', 'Edit attendance feature is not yet implemented.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        $user = Auth::user();
        
        // Check if the user has permission to update this attendance record
        if ($user->user_type !== 'super_admin') {
            $hasPermission = $attendance->shift->pump->pumpUsers()
                ->where('email', $user->email)
                ->where('status', 'active')
                ->exists();
                
            if (!$hasPermission) {
                return redirect()->route('attendance.index')
                    ->with('error', 'You do not have permission to update this attendance record.');
            }
        }
        
        $request->validate([
            'operator_id' => 'required|exists:users,id',
            'shift_id' => 'required|exists:shifts,id',
            'date' => 'required|date',
            'opening_meter_time' => 'required',
            'closing_meter_time' => 'nullable',
        ]);

        // Check for duplicate attendance excluding current record
        $existingAttendance = Attendance::where([
            'operator_id' => $request->operator_id,
            'shift_id' => $request->shift_id,
            'date' => $request->date,
        ])->where('id', '!=', $attendance->id)->first();

        if ($existingAttendance) {
            return back()->with('error', 'Attendance record already exists for this operator, shift, and date.');
        }

        $timing = null;
        if ($request->filled('opening_meter_time') && $request->filled('closing_meter_time')) {
            // Combine date with time to create proper datetime
            $attendanceDate = $request->date;
            $openingDateTime = $attendanceDate . ' ' . date('H:i:s', strtotime($request->opening_meter_time));
            $closingDateTime = $attendanceDate . ' ' . date('H:i:s', strtotime($request->closing_meter_time));
            
            $opening = \Carbon\Carbon::parse($openingDateTime);
            $closing = \Carbon\Carbon::parse($closingDateTime);
            
            // Handle overnight shifts
            if ($closing->lt($opening)) {
                $closing->addDay();
            }
            
            $timing = $opening->diffInMinutes($closing);
        } elseif ($request->filled('opening_meter_time') && !$request->filled('closing_meter_time')) {
            // If only opening time is provided, timing will be null until closing time is added
            $timing = null;
        }

        $attendance->update([
            'operator_id' => $request->operator_id,
            'shift_id' => $request->shift_id,
            'date' => $request->date,
            'opening_meter_time' => $request->filled('opening_meter_time') ? 
                $request->date . ' ' . date('H:i:s', strtotime($request->opening_meter_time)) : null,
            'closing_meter_time' => $request->filled('closing_meter_time') ? 
                $request->date . ' ' . date('H:i:s', strtotime($request->closing_meter_time)) : null,
            'timing' => $timing,
            'edited_by' => Auth::id(),
        ]);

        return redirect()->route('attendance.index')
            ->with('success', 'Attendance record updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        $user = Auth::user();
        
        // Check if the user has permission to delete this attendance record
        if ($user->user_type !== 'super_admin') {
            $hasPermission = $attendance->shift->pump->pumpUsers()
                ->where('email', $user->email)
                ->where('status', 'active')
                ->exists();
                
            if (!$hasPermission) {
                return redirect()->route('attendance.index')
                    ->with('error', 'You do not have permission to delete this attendance record.');
            }
        }
        
        $attendance->delete();
        return redirect()->route('attendance.index')
            ->with('success', 'Attendance record deleted successfully.');
    }
}
