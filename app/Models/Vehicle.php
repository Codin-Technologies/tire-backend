<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory;
    protected $fillable = [
        'registration_number',
        'fleet_number',
        'model',
        'type',
        'status',
        'axle_config',
    ];

    public function tires()
    {
        return $this->hasMany(Tire::class);
    }

    public function axlePositions()
    {
        return $this->hasMany(AxlePosition::class);
    }
}
