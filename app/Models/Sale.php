<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends BaseModel
{
    use HasFactory;

    protected $table = 'sales';

    protected $fillable = [
        'sale_date',
        'pump_id',
        'nozzle_id',
        'open_meter',
        'close_meter',
        'test_litre',
        'rate', 
        'net_sale_litre',
        'status',
        'close_time',
        'close_date',
        'created_by',
        'edited_by',
        'variation'
    ];

    protected $casts = [
        'sale_date' => 'date',
        'open_meter' => 'decimal:2',
        'close_meter' => 'decimal:2',
        'test_litre' => 'decimal:2',
        'rate' => 'decimal:2',
        'net_sale_litre' => 'decimal:2',
        'close_time' => 'string',
        'close_date' => 'date',
        'variation' => 'decimal:2'
    ];

    public static function rules()
    {
        return [
            'sale_date' => 'required|date',
            'pump_id' => 'required|exists:pumps,id',
            'nozzle_id' => 'required|exists:nozzles,id',
            'open_meter' => 'required|numeric|min:0',
            'close_meter' => 'nullable|numeric|min:0',
            'test_litre' => 'nullable|numeric|min:0',
            'rate' => 'required|numeric|min:0',
            'status' => 'required|in:Pending,Completed'
        ];
    }

    /**
     * Get the pump that owns the sale.
     */
    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }

    /**
     * Get the nozzle that owns the sale.
     */
    public function nozzle()
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function paymentItems()
    {
        return $this->belongsToMany(PaymentItem::class, 'sale_payment_items');
    }

    public function paymentEntries()
    {
        return $this->hasMany(PaymentItemEntry::class, 'sale_id');
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
