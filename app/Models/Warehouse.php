<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'location', 'description'];

    public function tires()
    {
        return $this->hasMany(Tire::class);
    }

    public function inventoryTires()
    {
        return $this->hasMany(InventoryTire::class);
    }

    public function skus()
    {
        return $this->hasMany(Sku::class, 'default_warehouse_id');
    }
}
