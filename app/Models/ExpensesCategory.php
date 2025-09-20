<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpensesCategory extends Model
{
    protected $fillable = [
        'category_name',
        'pump_id',
        'created_by',
        'updated_by',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
