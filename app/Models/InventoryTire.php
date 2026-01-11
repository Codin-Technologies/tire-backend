<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InventoryTire extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku_id',
        'warehouse_id',
        'supplier_id',
        'dot_code',
        'manufacture_week',
        'manufacture_year',
        'condition',
        'status',
        'qr_code',
        'received_date',
        'sold_date',
        'purchase_price',
        'selling_price',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'manufacture_week' => 'integer',
        'manufacture_year' => 'integer',
        'received_date' => 'date',
        'sold_date' => 'date',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate QR code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tire) {
            if (empty($tire->qr_code)) {
                $tire->qr_code = 'TIRE-' . strtoupper(Str::random(10));
            }
        });
    }

    /**
     * Relationships
     */
    public function sku()
    {
        return $this->belongsTo(Sku::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Calculate tire age in weeks
     */
    public function getAgeInWeeks(): int
    {
        $manufactureDate = Carbon::createFromDate($this->manufacture_year)
            ->addWeeks($this->manufacture_week - 1);
        
        return (int) $manufactureDate->diffInWeeks(Carbon::now());
    }

    /**
     * Calculate tire age in months
     */
    public function getAgeInMonths(): int
    {
        $manufactureDate = Carbon::createFromDate($this->manufacture_year)
            ->addWeeks($this->manufacture_week - 1);
        
        return (int) $manufactureDate->diffInMonths(Carbon::now());
    }

    /**
     * Check if tire is expired (older than 6 years)
     */
    public function isExpired(): bool
    {
        $ageInMonths = $this->getAgeInMonths();
        return $ageInMonths > 72; // 6 years = 72 months
    }

    /**
     * Check if tire is available
     */
    public function isAvailable(): bool
    {
        return $this->status === 'AVAILABLE';
    }

    /**
     * Check if tire is new
     */
    public function isNew(): bool
    {
        return $this->condition === 'NEW';
    }

    /**
     * Calculate profit if sold
     */
    public function getPotentialProfitAttribute(): ?float
    {
        if ($this->purchase_price === null || $this->selling_price === null) {
            return null;
        }
        return $this->selling_price - $this->purchase_price;
    }

    /**
     * Format DOT code for display
     */
    public function getFormattedDotCodeAttribute(): string
    {
        return strtoupper($this->dot_code);
    }

    /**
     * Scope for available tires
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'AVAILABLE');
    }

    /**
     * Scope for new tires
     */
    public function scopeNew($query)
    {
        return $query->where('condition', 'NEW');
    }

    /**
     * Scope for expired tires
     */
    public function scopeExpired($query)
    {
        $sixYearsAgo = Carbon::now()->subYears(6);
        
        return $query->where(function ($q) use ($sixYearsAgo) {
            $q->where('manufacture_year', '<', $sixYearsAgo->year)
              ->orWhere(function ($subQ) use ($sixYearsAgo) {
                  $subQ->where('manufacture_year', $sixYearsAgo->year)
                       ->where('manufacture_week', '<', $sixYearsAgo->weekOfYear);
              });
        });
    }

    /**
     * Scope by SKU
     */
    public function scopeBySku($query, $skuId)
    {
        return $query->where('sku_id', $skuId);
    }

    /**
     * Scope by warehouse
     */
    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }
}
