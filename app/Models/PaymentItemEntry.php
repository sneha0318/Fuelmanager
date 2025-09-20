<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentItemEntry extends BaseModel
{
    use HasFactory;

    protected $table = 'payment_items_entries';

    protected $fillable = [
        'nozzle_id',
        'sale_id',
        'payment_item_id',
        'bill_no',
        'litre',
        'rate',
        'amount',
        'status',
        'customer_name',
        'driver_name',
        'mobile_number',
        'vehicle_name',
        'vehicle_number',
        'created_by',
        'edited_by'
    ];

    protected $casts = [
        'litre' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2'
    ];

    public $timestamps = true;

    public function nozzle()
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function paymentItem()
    {
        return $this->belongsTo(Payment::class, 'payment_item_id');
    }

    /**
     * Get the vehicle associated with the payment entry.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * Get the customer associated with the payment entry.
     */
    public function customer()
    {
        return $this->belongsTo(Loan::class, 'customer_name', 'name');
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