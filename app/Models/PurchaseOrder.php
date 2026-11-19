<?php

namespace App\Models;

use App\Services\OrderTotals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'draft', 'pending', 'approved', 'sent', 'partially_received',
        'received', 'rejected', 'cancelled',
    ];

    public const ACTIVE_STATUSES = ['draft', 'pending', 'approved', 'sent', 'partially_received'];

    protected $fillable = [
        'reference', 'supplier_id', 'status', 'order_date', 'expected_delivery_date',
        'subtotal', 'tax_rate', 'tax_amount', 'total', 'notes', 'terms', 'created_by',
        'approved_by', 'approved_at', 'manager_approved_by', 'manager_approved_at',
        'sent_at', 'received_at', 'auto_generated', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date', 'expected_delivery_date' => 'date',
            'approved_at' => 'datetime', 'manager_approved_at' => 'datetime',
            'sent_at' => 'datetime', 'received_at' => 'datetime',
            'subtotal' => 'decimal:2', 'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2', 'total' => 'decimal:2',
            'auto_generated' => 'boolean',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function managerApprovedBy()
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    public function receipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function calculateTotal(): void
    {
        $this->forceFill(OrderTotals::calculate($this->items()->get(), $this->tax_rate))->save();
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeReceived(): bool
    {
        return in_array($this->status, ['sent', 'partially_received'], true);
    }

    public function requiresSecondApproval(): bool
    {
        return (float) $this->total >= 50000;
    }

    public static function generateReferenceNumber(): string
    {
        return 'PO-'.now()->format('Ymd').'-'.Str::upper(substr((string) Str::ulid(), -10));
    }
}
