<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'pump_id',
        'pump_user_id',
        'nozzle_id',
        'shift_name',
        'start_time',
        'end_time',
        'is_active',
        'created_date',
        'start_date',
        'end_date',
        'created_by',
        'edited_by'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'created_date' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }

    public function pumpUser()
    {
        return $this->belongsTo(PumpUser::class);
    }

    public function nozzle()
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}