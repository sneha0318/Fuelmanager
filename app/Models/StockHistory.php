<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    protected $fillable = [
        'stock_id',
        'previous_quantity',
        'added_quantity',
        'new_quantity',
        'date',
        'created_by'
    ];

    protected $casts = [
        'date' => 'datetime'
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 