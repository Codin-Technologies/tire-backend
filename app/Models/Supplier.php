<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_code',
        'supplier_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get inventory tires from this supplier
     */
    public function inventoryTires()
    {
        return $this->hasMany(InventoryTire::class);
    }

    /**
     * Get SKUs with this as default supplier
     */
    public function skus()
    {
        return $this->hasMany(Sku::class, 'default_supplier_id');
    }

    /**
     * Check if supplier is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope for active suppliers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
