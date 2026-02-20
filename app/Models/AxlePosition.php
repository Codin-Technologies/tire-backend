<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AxlePosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'position_code',
        'axle_number',
        'side',
        'tire_type_requirement',
        'tire_id',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tire()
    {
        return $this->belongsTo(Tire::class);
    }
}
