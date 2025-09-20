<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Payment extends Model
{
    protected $table = 'payments'; // Table name

    protected $fillable = [
        'item_name',
        'petrol_pump',
        'status',
        'created_on',
        'edited_on',
        'created_by',
        'edited_by'
    ];

    protected $dates = [
        'created_on',
        'edited_on'
    ];

    public $timestamps = False; // Disable default timestamps

    protected static function boot()
    {
        parent::boot();

        // When creating a new record
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->edited_by = Auth::id();
            }
        });

        // When updating an existing record
        static::updating(function ($model) {
            if (Auth::check()) {
                $model->edited_by = Auth::id();
            }
        });
    }

    /**
     * Get the pump associated with the payment.
     */
    public function pump()
    {
        return $this->belongsTo(Pump::class, 'petrol_pump');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Get the party list associated with this payment.
     */
    public function partyList()
    {
        return $this->hasOne(PartyList::class, 'ledger_section', 'item_name');
    }
}
