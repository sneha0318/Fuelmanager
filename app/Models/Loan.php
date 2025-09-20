<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'address',
        'phone_no',
        'total_amount',
        'pump_id',
        'created_by',
        'edited_by'
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function paymentEntries()
    {
        return $this->hasMany(PaymentItemEntry::class, 'customer_name', 'name');
    }

    public function pump()
    {
        return $this->belongsTo(Pump::class);
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