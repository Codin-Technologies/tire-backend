<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sku extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku_code',
        'sku_name',
        'category',
        'unit_of_measure',
        'unit_price',
        'cost_price',
        'status',
        'description',
        'brand',
        'model',
        'size',
        'tire_type',
        'load_index',
        'speed_rating',
        'current_stock',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'metadata',
        'default_supplier_id',
        'default_warehouse_id',
        'retreadable',
        'max_retread_cycles',
        'expected_mileage',
        'min_tread_depth',
        'tire_category',
        'preferred_warehouse_id',
        'lead_time_days',
        'budget_category',
        'max_age_months',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'current_stock' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'reorder_point' => 'integer',
        'load_index' => 'integer',
        'metadata' => 'array',
        'retreadable' => 'boolean',
        'max_retread_cycles' => 'integer',
        'expected_mileage' => 'integer',
        'min_tread_depth' => 'decimal:2',
        'lead_time_days' => 'integer',
        'max_age_months' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['calculated_stock', 'stock_by_warehouse'];

    /**
     * Check if SKU is low on stock
     */
    public function isLowStock(): bool
    {
        if ($this->min_stock_level === null) {
            return false;
        }
        return $this->current_stock <= $this->min_stock_level;
    }

    /**
     * Check if SKU needs reordering
     */
    public function needsReorder(): bool
    {
        if ($this->reorder_point === null) {
            return false;
        }
        return $this->current_stock <= $this->reorder_point;
    }

    /**
     * Check if SKU is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Calculate profit margin
     */
    public function getProfitMarginAttribute(): ?float
    {
        if ($this->cost_price === null || $this->cost_price == 0) {
            return null;
        }
        return (($this->unit_price - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->current_stock == 0) {
            return 'out_of_stock';
        }
        if ($this->isLowStock()) {
            return 'low_stock';
        }
        if ($this->needsReorder()) {
            return 'reorder_needed';
        }
        return 'in_stock';
    }

    /**
     * Scope for active SKUs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for low stock SKUs
     */
    public function scopeLowStock($query)
    {
        return $query->whereNotNull('min_stock_level')
                     ->whereColumn('current_stock', '<=', 'min_stock_level');
    }

    /**
     * Scope for SKUs needing reorder
     */
    public function scopeNeedsReorder($query)
    {
        return $query->whereNotNull('reorder_point')
                     ->whereColumn('current_stock', '<=', 'reorder_point');
    }

    /**
     * Get default supplier for this SKU
     */
    public function defaultSupplier()
    {
        return $this->belongsTo(Supplier::class, 'default_supplier_id');
    }

    /**
     * Get default warehouse for this SKU
     */
    public function defaultWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    /**
     * Get preferred warehouse for this SKU
     */
    public function preferredWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'preferred_warehouse_id');
    }

    /**
     * Get all inventory tires for this SKU
     */
    public function inventoryTires()
    {
        return $this->hasMany(InventoryTire::class);
    }

    /**
     * Get legacy tires for this SKU
     */
    public function tires()
    {
        return $this->hasMany(Tire::class);
    }

    /**
     * Get calculated stock from tire assets
     */
    public function getCalculatedStockAttribute(): int
    {
        return $this->tires()
            ->where('status', 'available')
            ->count();
    }

    /**
     * Get stock breakdown by warehouse
     */
    public function getStockByWarehouseAttribute(): array
    {
        $breakdown = [];
        
        $groups = $this->tires()
            ->where('status', 'available')
            ->select('warehouse_id', \DB::raw('count(*) as count'))
            ->groupBy('warehouse_id')
            ->with('warehouse')
            ->get();
            
        foreach ($groups as $group) {
            $whName = $group->warehouse ? $group->warehouse->name : 'Unknown';
            if (!isset($breakdown[$whName])) {
                $breakdown[$whName] = 0;
            }
            $breakdown[$whName] += $group->count;
        }
        
        return $breakdown;
    }

    /**
     * Get available inventory tires for this SKU
     */
    public function availableInventoryTires()
    {
        return $this->hasMany(InventoryTire::class)->where('status', 'AVAILABLE');
    }
}

