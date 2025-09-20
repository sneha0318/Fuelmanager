<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentItem extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
        'edited_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
