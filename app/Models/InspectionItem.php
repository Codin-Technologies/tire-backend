<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionItem extends Model
{
    protected $fillable = [
        'inspection_id',
        'tire_id',
        'position',
        'pressure_psi',
        'tread_depth_mm',
        'condition',
        'issues',
        'images',
    ];

    protected $casts = [
        'issues' => 'array',
        'images' => 'array',
    ];

    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    public function tire()
    {
        return $this->belongsTo(Tire::class);
    }
}
