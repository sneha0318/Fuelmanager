<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BaseModel extends Model
{
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
} 