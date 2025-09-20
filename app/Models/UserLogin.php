<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLogin extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'username',
        'password',
        'mobile', // Add mobile to fillable fields
    ];

    protected $hidden = [
        'password',
    ];
}