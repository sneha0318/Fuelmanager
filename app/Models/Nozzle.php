<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nozzle extends BaseModel
{
    protected $fillable = [
        'pump_id',
        'nozzle_number',
        'item_id',
        'status',
        'created_by',
        'edited_by'
    ];

    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
}