<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class PumpUser extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'user_name',
        'email',
        'mobile_number', // Ensure this is included
        'user_type',
        'pump_id',
        'pump_name',
        'password',
        'status',
        'created_by',
        'edited_by'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'pump_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

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

        static::saving(function ($model) {
            if ($model->pump_id) {
                $pump = Pump::find($model->pump_id);
                if ($pump) {
                    $model->pump_name = $pump->pump_name;
                }
            }

            // Hash the password if it's being set or changed
            if ($model->isDirty('password')) {
                $model->password = Hash::make($model->password);
            }
        });
    }
}
