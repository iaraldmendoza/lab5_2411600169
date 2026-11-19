<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

class SearchService
{
    public function suppliers(array $filters)
    {
        $query = Supplier::query()->withCount('products', 'purchaseOrders');

        if (($filters['archived'] ?? '') === '1') {
            $query->onlyTrashed();
        }

        return $query->when($filters['q'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q
            ->where('company_name', 'like', "%{$value}%")->orWhere('contact_person', 'like', "%{$value}%")
            ->orWhere('email', 'like', "%{$value}%")))
            ->when(isset($filters['active']) && $filters['active'] !== '', fn ($q) => $q->where('active', $filters['active']))
            ->orderBy(in_array($filters['sort'] ?? '', ['company_name', 'lead_time_days', 'created_at']) ? $filters['sort'] : 'company_name');
    }

    public function orders(array $filters)
    {
        return PurchaseOrder::with(['supplier', 'createdBy', 'approvedBy'])->withCount('items')
            ->when($filters['q'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q
                ->where('reference', 'like', "%{$value}%")
                ->orWhereHas('supplier', fn ($supplier) => $supplier->where('company_name', 'like', "%{$value}%"))))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->byStatus($value))
            ->when($filters['supplier_id'] ?? null, fn ($q, $value) => $q->where('supplier_id', $value))
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->whereDate('order_date', '>=', $value))
            ->when($filters['to'] ?? null, fn ($q, $value) => $q->whereDate('order_date', '<=', $value))
            ->latest('id');
    }

    public function products(array $filters)
    {
        return Product::with('supplierRecord')
            ->when($filters['q'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$value}%")->orWhere('sku', 'like', "%{$value}%")))
            ->when($filters['category'] ?? null, fn ($q, $value) => $q->inCategory($value))
            ->when($filters['supplier_id'] ?? null, fn ($q, $value) => $q->where('supplier_id', $value))
            ->when(($filters['stock'] ?? '') === 'low', fn ($q) => $q->lowStock())
            ->when(($filters['stock'] ?? '') === 'out', fn ($q) => $q->outOfStock())
            ->orderBy('name');
    }

    public function transactions(array $filters)
    {
        return InventoryTransaction::with(['product', 'user'])
            ->when($filters['product_id'] ?? null, fn ($q, $value) => $q->where('product_id', $value))
            ->when($filters['type'] ?? null, fn ($q, $value) => $q->where('type', $value))
            ->when($filters['user_id'] ?? null, fn ($q, $value) => $q->where('user_id', $value))
            ->when($filters['from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->latest('id');
    }
}
