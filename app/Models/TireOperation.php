<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TireOperation extends Model
{
    protected $fillable = [
        'tire_id',
        'vehicle_id',
        'user_id',
        'type',
        'odometer',
        'position',
        'previous_position',
        'notes',
        'cost',
        'vendor',
    ];

    public function tire()
    {
        return $this->belongsTo(Tire::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function photos()
    {
        return $this->hasMany(TireOperationPhoto::class);
    }
}
