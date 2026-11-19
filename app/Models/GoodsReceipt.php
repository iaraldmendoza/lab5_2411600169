<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    protected $fillable = ['reference', 'receipt_key', 'purchase_order_id', 'received_by', 'received_at', 'notes'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class)->withTrashed();
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }
}
