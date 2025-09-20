<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'pump_id',
        'quantity',
        'date',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the item that owns the stock.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the pump that owns the stock.
     */
    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }

    /**
     * Get the user who created the stock.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the stock.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
