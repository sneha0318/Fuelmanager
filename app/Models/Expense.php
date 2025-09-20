<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ExpensesCategory;

class Expense extends Model
{
    protected $fillable = [
        'name',
        'date',
        'description',
        'amount',
        'payment_method',
        'category_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ExpensesCategory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
