<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'shift_id',
        'date',
        'timing',
        'opening_meter_time',
        'closing_meter_time',
        'created_by',
        'edited_by'
    ];

    protected $casts = [
        'date' => 'date',
        'opening_meter_time' => 'datetime',
        'closing_meter_time' => 'datetime'
    ];

    /**
     * Get opening meter time as time only (H:i:s format)
     */
    public function getOpeningTimeOnlyAttribute()
    {
        return $this->opening_meter_time ? $this->opening_meter_time->format('H:i:s') : null;
    }

    /**
     * Get closing meter time as time only (H:i:s format)
     */
    public function getClosingTimeOnlyAttribute()
    {
        return $this->closing_meter_time ? $this->closing_meter_time->format('H:i:s') : null;
    }

    // Relationship with operator (User)
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    // Relationship with shift
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    // Relationship with creator (User)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship with editor (User)
    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Get the total working time between opening and closing meter times
     */
    public function getTotalWorkingTimeAttribute()
    {
        if ($this->opening_time_only && $this->closing_time_only) {
            $attendanceDate = \Carbon\Carbon::parse($this->date);
            
            // Create datetime objects for opening and closing times using attendance date
            $opening = $attendanceDate->copy()->setTimeFromTimeString($this->opening_time_only);
            $closing = $attendanceDate->copy()->setTimeFromTimeString($this->closing_time_only);
            
            // Handle overnight shifts (when closing time is earlier than opening time)
            if ($this->shift) {
                $shiftStart = \Carbon\Carbon::parse($this->shift->start_time);
                $shiftEnd = \Carbon\Carbon::parse($this->shift->end_time);
                
                // Check if this is an overnight shift (end time < start time)
                if ($shiftEnd->lt($shiftStart)) {
                    // For overnight shifts, if closing time is before opening time, 
                    // it means closing happened the next day
                    if ($closing->format('H:i:s') < $opening->format('H:i:s')) {
                        $closing->addDay();
                    }
                    // Additional check: if closing time is very early (like 2:00 AM)
                    // and opening time is late (like 23:00 PM), closing is next day
                    else if ($closing->format('H:i:s') <= $shiftEnd->format('H:i:s') && 
                             $opening->format('H:i:s') >= $shiftStart->format('H:i:s')) {
                        $closing->addDay();
                    }
                }
            } else {
                // No shift info - basic logic: if closing < opening, add day to closing
                if ($closing->lt($opening)) {
                    $closing->addDay();
                }
            }
            
            return $opening->diffInMinutes($closing);
        }
        return null;
    }

    /**
     * Get formatted total working time (e.g., "8h 30m")
     */
    public function getFormattedWorkingTimeAttribute()
    {
        $totalMinutes = $this->total_working_time;
        if ($totalMinutes === null) {
            return '-';
        }
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return "{$hours}h {$minutes}m";
    }
}
