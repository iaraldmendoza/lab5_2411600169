<?php

namespace App\Models;

use App\Services\SupplierPerformanceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_name', 'contact_person', 'email', 'phone', 'address',
        'payment_terms', 'lead_time_days', 'minimum_order_amount', 'active',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'minimum_order_amount' => 'decimal:2'];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function catalogProducts()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('unit_cost', 'minimum_order_quantity', 'package_size', 'lead_time_days')
            ->withTimestamps();
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeWithLeadTime($query, int $days)
    {
        return $query->where('lead_time_days', '<=', $days);
    }

    public function getFullContactAttribute(): string
    {
        return "{$this->contact_person} | {$this->email} | {$this->phone}";
    }

    public function getPerformanceRatingAttribute(): ?float
    {
        return app(SupplierPerformanceService::class)->forSupplier($this)['on_time_percentage'];
    }
}
