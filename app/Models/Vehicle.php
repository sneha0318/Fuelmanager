<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_id',
        'driver_name',
        'mobile_number',
        'vehicle_name',
        'vehicle_number',
        'bill_no',
        'rate',
        'litre',
        'amount'
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    /**
     * Get the loan associated with the vehicle.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function fuelTransactions()
    {
        return $this->hasMany(FuelTransaction::class);
    }

    public function paymentItems()
    {
        return $this->hasMany(PaymentItemEntry::class);
    }
}
