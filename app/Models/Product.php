<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model,SoftDeletes};

class Product extends Model
{
    use SoftDeletes;
    public const CATEGORIES = ['Lumber','Hardware','Tools','Electrical','Plumbing','Paint'];
    protected $fillable = ['name','sku','description','category','quantity','reorder_level','unit_price','supplier','supplier_id','safety_stock','target_stock','daily_usage_estimate'];
    protected function casts(): array
    {
        return ['quantity' => 'integer','reorder_level' => 'integer','unit_price' => 'decimal:2','low_stock_alerted' => 'boolean'];
    }
    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
    public function latestTransaction()
    {
        return $this->hasOne(InventoryTransaction::class)->latestOfMany();
    }
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->reorder_level;
    }
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }
    public function getStockStatusAttribute(): string
    {
        return $this->isOutOfStock() ? 'Out of Stock' : ($this->isLowStock() ? 'Low Stock' : 'In Stock');
    }
    public function getStockStatusColorAttribute(): string
    {
        return $this->isOutOfStock() ? 'danger' : ($this->isLowStock() ? 'warning' : 'success');
    }
    public function scopeLowStock($q)
    {
        return $q->whereColumn('quantity', '<=', 'reorder_level');
    }
    public function scopeOutOfStock($q)
    {
        return $q->where('quantity', '<=', 0);
    }
    public function scopeInCategory($q, string $category)
    {
        return $q->where('category', $category);
    }


    public function supplierRecord()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot('unit_cost', 'minimum_order_quantity', 'package_size', 'lead_time_days')
            ->withTimestamps();
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
