<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pump extends BaseModel
{
    protected $fillable = [
        'pump_name',
        'location',
        'total_nozzles',
        'status',
        'created_by',
        'edited_by'
    ];

    public function pumpUsers()
    {
        return $this->hasMany(PumpUser::class);
    }

    public function nozzles()
    {
        return $this->hasMany(Nozzle::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
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
