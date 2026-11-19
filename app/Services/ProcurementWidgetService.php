<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Cache;

class ProcurementWidgetService
{
    public function data(string $name): array
    {
        return Cache::remember('procurement.widget.'.$name, 30, function () use ($name) {
            return match ($name) {
                'pending-orders' => [
                    'count' => PurchaseOrder::pending()->count(),
                    'open_orders' => PurchaseOrder::active()->count(),
                    'active_suppliers' => \App\Models\Supplier::active()->count(),
                    'product_count' => \App\Models\Product::count(),
                    'value' => (float) PurchaseOrder::pending()->sum('total'),
                    'awaiting_admin' => PurchaseOrder::pending()->whereNotNull('manager_approved_at')->count(),
                ],
                'recent-orders' => [
                    'orders' => PurchaseOrder::with('supplier')->latest('id')->take(5)->get()
                        ->map(fn ($order) => [
                            'id' => $order->id, 'reference' => $order->reference,
                            'supplier' => $order->supplier->company_name,
                            'status' => $order->status, 'total' => (float) $order->total,
                        ])->all(),
                ],
                'supplier-performance' => [
                    'suppliers' => collect(app(SupplierPerformanceService::class)->all())
                        ->whereNotNull('on_time_percentage')->sortByDesc('on_time_percentage')->take(5)->values()->all(),
                ],
                'reorder-alerts' => $this->reorders(),
                default => abort(404),
            };
        });
    }

    private function reorders(): array
    {
        $service = app(ReorderService::class);
        $products = $service->getProductsNeedingReorder();

        return [
            'count' => $products->count(),
            'products' => $products->take(8)->map(fn ($product) => [
                'id' => $product->id, 'name' => $product->name, 'sku' => $product->sku,
                'quantity' => $product->quantity,
                'position' => $service->inventoryPosition($product),
                'reorder_point' => $service->calculateReorderPoint($product),
                'suggested_quantity' => $service->calculateOptimalOrderQuantity($product),
                'supplier' => $product->supplierRecord->company_name,
            ])->all(),
        ];
    }
}
