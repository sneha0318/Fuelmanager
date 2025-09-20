<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Item extends BaseModel
{
    protected $fillable = [
        'name',
        'status',
        'item_type',
        'edited_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

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

    public function rates()
    {
        return $this->hasMany(Rate::class);
    }

    public function pumps()
    {
        return $this->belongsToMany(Pump::class, 'rates')
                    ->withPivot('rate', 'is_active')
                    ->withTimestamps();
    }

    public function activePumps()
    {
        return $this->belongsToMany(Pump::class, 'rates')
                    ->wherePivot('is_active', true)
                    ->withPivot('rate')
                    ->withTimestamps();
    }

    public function getAssignedPumpsAttribute()
    {
        return $this->activePumps()->get();
    }

    public function nozzles()
    {
        return $this->hasMany(Nozzle::class)->where('status', 'active');
    }

    public function allNozzles()
    {
        return $this->hasMany(Nozzle::class);
    }
} 