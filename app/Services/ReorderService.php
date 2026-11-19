<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;

use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReorderService
{
    private array $usage = [];
    private array $incoming = [];
    private bool $loaded = false;

    public function refresh(): void
    {
        $this->usage = InventoryTransaction::where('type', 'out')
            ->whereNull('voided_at')->whereNull('reversal_of_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('product_id, SUM(quantity) as units')->groupBy('product_id')
            ->pluck('units', 'product_id')->all();

        $this->incoming = PurchaseOrderItem::whereHas('purchaseOrder', fn ($query) => $query->active())
            ->selectRaw('product_id, SUM(quantity - received_quantity) as units')
            ->groupBy('product_id')->pluck('units', 'product_id')->all();
        $this->loaded = true;
    }

    public function averageDailyUsage(Product $product): float
    {
        if (!$this->loaded) {
            $this->refresh();
        }

        return isset($this->usage[$product->id])
            ? (float) $this->usage[$product->id] / 30
            : (float) $product->daily_usage_estimate;
    }

    public function leadTime(Product $product): int
    {
        $agreement = $product->suppliers->firstWhere('id', $product->supplier_id)?->pivot;

        return (int) ($agreement?->lead_time_days ?? $product->supplierRecord?->lead_time_days ?? 7);
    }

    public function calculateReorderPoint(Product $product): int
    {
        return (int) ceil($this->averageDailyUsage($product) * $this->leadTime($product) + $product->safety_stock);
    }

    public function inventoryPosition(Product $product): int
    {
        if (!$this->loaded) {
            $this->refresh();
        }

        return $product->quantity + (int) ($this->incoming[$product->id] ?? 0);
    }

    public function calculateOptimalOrderQuantity(Product $product): int
    {
        $agreement = $product->suppliers->firstWhere('id', $product->supplier_id)?->pivot;
        $package = max(1, (int) ($agreement?->package_size ?? 1));
        $minimum = max(1, (int) ($agreement?->minimum_order_quantity ?? 1));
        $target = max($product->target_stock, (int) ceil($this->averageDailyUsage($product) * ($this->leadTime($product) + 14) + $product->safety_stock));
        $needed = max($minimum, $target - $this->inventoryPosition($product));

        return (int) (ceil($needed / $package) * $package);
    }

    public function getProductsNeedingReorder(): Collection
    {
        $this->refresh();

        return Product::with(['supplierRecord', 'suppliers'])
            ->whereHas('supplierRecord', fn ($query) => $query->active()->whereNull('deleted_at'))
            ->orderBy('id')->get()
            ->filter(fn ($product) => $this->inventoryPosition($product) <= $this->calculateReorderPoint($product))
            ->values();
    }

    public function groupProductsBySupplier(Collection $products): Collection
    {
        return $products->groupBy('supplier_id');
    }

    public function generatePurchaseOrders(?User $actor = null): Collection
    {
        $actor ??= User::where('role', 'admin')->orderBy('id')->firstOrFail();
        $result = Cache::lock('procurement.reorder-generation', 120)->get(function () use ($actor) {
            return DB::transaction(function () use ($actor) {
                Product::orderBy('id')->lockForUpdate()->get();
                $orders = collect();

                foreach ($this->groupProductsBySupplier($this->getProductsNeedingReorder()) as $products) {
                    $supplier = $products->first()->supplierRecord;
                    $items = $products->map(function ($product) {
                        $agreement = $product->suppliers->firstWhere('id', $product->supplier_id)?->pivot;

                        return [
                            'product_id' => $product->id,
                            'quantity' => $this->calculateOptimalOrderQuantity($product),
                            'unit_price' => $agreement?->unit_cost ?? $product->unit_price,
                        ];
                    })->all();

                    $subtotal = (float) OrderTotals::calculate($items, 0)['subtotal'];

                    if ($subtotal < (float) $supplier->minimum_order_amount) {
                        $first = $products->first();
                        $package = max(1, (int) ($first->suppliers->firstWhere('id', $supplier->id)?->pivot->package_size ?? 1));
                        $packageCost = (float) $items[0]['unit_price'] * $package;
                        $items[0]['quantity'] += (int) ceil(((float) $supplier->minimum_order_amount - $subtotal) / $packageCost) * $package;
                    }

                    $orders->push(app(PurchaseOrderService::class)->create([
                        'supplier_id' => $supplier->id, 'status' => 'draft',
                        'order_date' => today()->toDateString(),
                        'expected_delivery_date' => today()->addDays($supplier->lead_time_days)->toDateString(),
                        'tax_rate' => 0, 'items' => $items,
                        'notes' => 'Automatic replenishment based on inventory position, 30-day usage, lead time and safety stock.',
                    ], $actor, true));
                }

                return $orders;
            }, 3);
        });

        return $result instanceof Collection ? $result : collect();
    }
}
