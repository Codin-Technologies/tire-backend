<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tire extends Model
{
    use HasFactory;
    protected $fillable = [
        'sku_id',
        'unique_tire_id',
        'serial_number',
        'dot_code',
        'manufacture_week',
        'manufacture_year',
        'condition',
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
        'manufacture_week' => 'integer',
        'manufacture_year' => 'integer',
    ];

    public function getAgeInWeeks()
    {
        if (!$this->manufacture_year || !$this->manufacture_week) {
            return null;
        }
        
        $now = \Carbon\Carbon::now();
        // Create date from week/year. "Monday" of that week.
        $manufactureDate = \Carbon\Carbon::now()->setISODate($this->manufacture_year, $this->manufacture_week);
        
        return $manufactureDate->diffInWeeks($now);
    }

    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

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
