<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyList extends Model
{
    protected $fillable = [
        'group_type',
        'ledger_section',
        'expense_id',
        'status',
        'created_by',
        'edited_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created this party list
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last edited this party list
     */
    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /**
     * Get the associated loan (for Credit Sale group_type)
     * Matches based on loan's company name matching the party list's ledger_section
     */
    public function loan()
    {
        return $this->hasOne(Loan::class, 'company', 'ledger_section');
    }

    /**
     * Get the loan instance with fallback methods
     * This method tries multiple approaches to find the associated loan
     */
    public function getLoanInstance()
    {
        if ($this->group_type !== 'Credit Sale') {
            return null;
        }

        // First try the standard relationship
        $loan = $this->loan;
        if ($loan) {
            return $loan;
        }

        // If no loan found, try case-insensitive search
        $loan = Loan::whereRaw('LOWER(company) = LOWER(?)', [$this->ledger_section])->first();
        if ($loan) {
            return $loan;
        }

        // If still no loan, try trimmed comparison (in case of whitespace issues)
        $loan = Loan::whereRaw('TRIM(LOWER(company)) = TRIM(LOWER(?))', [$this->ledger_section])->first();
        
        return $loan;
    }

    /**
     * Get the associated payment (for Payment group_type)
     * Since ledger_section now includes pump name, we need custom logic
     */
    public function getPaymentInstance()
    {
        if ($this->group_type !== 'Payment') {
            return null;
        }
        
        // Extract payment name from ledger_section (format: "PaymentName (PumpName)")
        if (preg_match('/^(.+) \((.+)\)$/', $this->ledger_section, $matches)) {
            $paymentName = $matches[1];
            $pumpName = $matches[2];
            
            // Find the pump first
            $pump = \App\Models\Pump::where('pump_name', $pumpName)->first();
            if ($pump) {
                return Payment::where('item_name', $paymentName)
                    ->where('petrol_pump', $pump->id)
                    ->first();
            }
        }
        
        // Fallback for old format (just item_name) - return first match
        return Payment::where('item_name', $this->ledger_section)->first();
    }
    
    /**
     * Get the payment relationship as an Eloquent relationship for eager loading
     * This is a basic relationship that works for old format
     */
    public function paymentRelation()
    {
        return $this->hasOne(Payment::class, 'item_name', 'ledger_section');
    }

    /**
     * Custom accessor for payment attribute
     * This handles both old and new formats correctly
     */
    public function getPaymentAttribute()
    {
        // Cache the result to avoid repeated DB queries
        if (!isset($this->attributes['_cached_payment'])) {
            $this->attributes['_cached_payment'] = $this->getPaymentInstance();
        }
        
        return $this->attributes['_cached_payment'];
    }

    /**
     * Get the associated expense (for Expense Entry group_type)
     */
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
