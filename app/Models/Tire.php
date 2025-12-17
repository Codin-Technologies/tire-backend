<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tire extends Model
{
    use HasFactory;
    protected $fillable = [
        'unique_tire_id',
        'serial_number',
        'brand',
        'model',
        'size',
        'cost',
        'vendor',
        'purchase_date',
        'warehouse_id',
        'status',
        'vehicle_id',
        'position',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function operations()
    {
        return $this->hasMany(TireOperation::class);
    }
}
