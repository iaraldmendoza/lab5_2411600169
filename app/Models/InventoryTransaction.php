<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = ['product_id','user_id','type','quantity','reference','notes','reversal_of_id'];
    protected function casts(): array
    {
        return ['quantity' => 'integer','balance_before' => 'integer','balance_after' => 'integer','voided_at' => 'datetime'];
    }
    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }
    public function reversal()
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }
    public function scopeStockIn($q)
    {
        return $q->where('type', 'in');
    }
    public function scopeStockOut($q)
    {
        return $q->where('type', 'out');
    }
    public function scopeRecent($q)
    {
        return $q->orderByDesc('id');
    }
    public function getDeltaAttribute(): int
    {
        return $this->type === 'out' ? -$this->quantity : $this->quantity;
    }
    public function getTypeLabelAttribute(): string
    {
        return $this->reversal_of_id ? 'Reversal' : match($this->type) {
            'in' => 'Stock In','out' => 'Stock Out',default => 'Adjustment'
        };
    }
}
