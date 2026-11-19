<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['product_id', 'quantity', 'unit_price', 'received_quantity'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'received_quantity' => 'integer', 'unit_price' => 'decimal:2'];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function receiptItems()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function getLineTotalAttribute(): string
    {
        return number_format($this->quantity * (float) $this->unit_price, 2, '.', '');
    }

    public function getOutstandingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->received_quantity);
    }
}
