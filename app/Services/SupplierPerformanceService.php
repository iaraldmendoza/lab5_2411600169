<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;

class SupplierPerformanceService
{
    public function forSupplier(Supplier $supplier): array
    {
        return Cache::remember('supplier.performance.'.$supplier->id, 60, function () use ($supplier) {
            $orders = $supplier->purchaseOrders()->with('receipts.items')->get();
            $completed = $orders->where('status', 'received');
            $onTime = $completed->filter(fn ($order) => $order->received_at->startOfDay()->lte($order->expected_delivery_date->endOfDay()))->count();
            $days = $completed->filter(fn ($order) => $order->sent_at)
                ->map(fn ($order) => $order->sent_at->diffInDays($order->received_at, false));
            $receiptItems = $orders->flatMap(fn ($order) => $order->receipts)->flatMap(fn ($receipt) => $receipt->items);
            $delivered = $receiptItems->sum('delivered_quantity');

            return [
                'supplier_id' => $supplier->id, 'supplier_name' => $supplier->company_name,
                'total_orders' => $orders->count(), 'completed_orders' => $completed->count(),
                'on_time_orders' => $onTime,
                'on_time_percentage' => $completed->count() ? round($onTime / $completed->count() * 100, 1) : null,
                'average_delivery_days' => $days->count() ? round($days->avg(), 1) : null,
                'quoted_lead_days' => $supplier->lead_time_days,
                'order_accuracy' => $delivered ? round($receiptItems->sum('accepted_quantity') / $delivered * 100, 1) : null,
                'total_value' => round($orders->whereNotIn('status', ['cancelled', 'rejected'])->sum('total'), 2),
            ];
        });
    }

    public function all(): array
    {
        return Cache::remember('supplier.performance.all', 60, fn () => Supplier::withTrashed()
            ->orderBy('company_name')->get()->map(fn ($supplier) => $this->forSupplier($supplier))->all());
    }
}
