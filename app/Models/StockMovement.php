<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'tire_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'type',
        'user_id',
        'notes',
    ];

    public function tire()
    {
        return $this->belongsTo(Tire::class);
    }
    
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}
