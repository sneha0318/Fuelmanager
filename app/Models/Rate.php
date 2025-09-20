<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rate extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'pump_id',
        'item_id',
        'rate',
        'rate_date',
        'is_active',
        'created_by',
        'edited_by'
    ];

    protected $casts = [
        'rate_date' => 'date',
        'is_active' => 'boolean'
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
}
