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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
}
