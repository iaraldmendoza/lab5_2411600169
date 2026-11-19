<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function create(array $data, User $actor, bool $automatic = false): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $actor, $automatic) {
            $supplier = Supplier::active()->lockForUpdate()->findOrFail($data['supplier_id']);
            $this->validateItems($supplier, $data['items']);
            $totals = OrderTotals::calculate($data['items'], $data['tax_rate'] ?? 0);

            if ((float) $totals['subtotal'] < (float) $supplier->minimum_order_amount) {
                throw ValidationException::withMessages(['items' => 'The order subtotal is below the supplier minimum order amount.']);
            }

            $order = PurchaseOrder::create([
                'reference' => PurchaseOrder::generateReferenceNumber(),
                'supplier_id' => $supplier->id,
                'created_by' => $actor->id,
                'status' => $data['status'] ?? 'draft',
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'],
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? $supplier->payment_terms,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'auto_generated' => $automatic,
                ...$totals,
            ]);

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            $this->audit($order, $actor, $automatic ? 'automatic_order_created' : 'order_created');

            return $order->load('supplier', 'items.product', 'createdBy');
        }, 3);
    }

    public function update(PurchaseOrder $order, array $data, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $data, $actor) {
            $order = PurchaseOrder::lockForUpdate()->findOrFail($order->id);
            Gate::forUser($actor)->authorize('update', $order);
            $supplier = Supplier::active()->lockForUpdate()->findOrFail($data['supplier_id']);
            $this->validateItems($supplier, $data['items']);
            $totals = OrderTotals::calculate($data['items'], $data['tax_rate'] ?? 0);

            if ((float) $totals['subtotal'] < (float) $supplier->minimum_order_amount) {
                throw ValidationException::withMessages(['items' => 'The order is below the supplier minimum.']);
            }

            $order->items()->delete();

            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'], 'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            $order->update([
                'supplier_id' => $supplier->id, 'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'],
                'tax_rate' => $data['tax_rate'] ?? 0, 'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? $supplier->payment_terms, ...$totals,
            ]);
            $this->audit($order, $actor, 'draft_updated');

            return $order;
        }, 3);
    }

    public function transition(PurchaseOrder $order, string $action, User $actor, ?string $reason = null): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $action, $actor, $reason) {
            $order = PurchaseOrder::lockForUpdate()->findOrFail($order->id);
            Gate::forUser($actor)->authorize($action, $order);
            $before = $order->status;

            if ($action === 'submit') {
                $order->status = 'pending';
            } elseif ($action === 'approve') {
                if ($order->requiresSecondApproval() && !$order->manager_approved_at) {
                    $order->manager_approved_by = $actor->id;
                    $order->manager_approved_at = now();
                    $order->save();
                    $this->audit($order, $actor, 'manager_approved', ['next' => 'A different administrator must give final approval.']);

                    return $order;
                }

                $order->status = 'approved';
                $order->approved_by = $actor->id;
                $order->approved_at = now();
            } elseif ($action === 'reject') {
                if (!trim((string) $reason)) {
                    throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
                }

                $order->status = 'rejected';
                $order->rejection_reason = $reason;
            } elseif ($action === 'send') {
                if (!$order->supplier || !$order->supplier->active || $order->supplier->trashed()) {
                    throw ValidationException::withMessages(['supplier' => 'Activate the supplier before sending this order.']);
                }

                $order->status = 'sent';
                $order->sent_at = now();
            } elseif ($action === 'cancel') {
                $order->status = 'cancelled';
            } else {
                abort(422, 'Unsupported order action.');
            }

            $order->save();
            $this->audit($order, $actor, 'order_'.$action, ['from' => $before, 'to' => $order->status, 'reason' => $reason]);

            return $order;
        }, 3);
    }

    public function receive(PurchaseOrder $order, array $data, User $actor): GoodsReceipt
    {
        return DB::transaction(function () use ($order, $data, $actor) {
            $order = PurchaseOrder::lockForUpdate()->findOrFail($order->id);
            $existing = GoodsReceipt::where('receipt_key', $data['receipt_key'])->first();

            if ($existing) {
                abort_unless($existing->purchase_order_id === $order->id && $existing->received_by === $actor->id, 409);

                return $existing;
            }

            Gate::forUser($actor)->authorize('receive', $order);
            $lines = $order->items()->orderBy('product_id')->lockForUpdate()->get()->keyBy('id');
            $delivered = collect($data['items'])->filter(fn ($line) => (int) $line['quantity'] > 0);

            if ($delivered->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Enter at least one delivered quantity.']);
            }

            foreach ($delivered as $row) {
                $line = $lines->get((int) $row['item_id']);

                if (!$line || (int) $row['quantity'] > $line->outstanding_quantity) {
                    throw ValidationException::withMessages(['items' => 'A quantity exceeds the outstanding amount or belongs to a different order.']);
                }

                if ((int) ($row['damaged'] ?? 0) > (int) $row['quantity']) {
                    throw ValidationException::withMessages(['items' => 'Damaged quantity cannot exceed delivered quantity.']);
                }

                if ($line->product->trashed()) {
                    throw ValidationException::withMessages(['items' => 'Restore the archived product before receiving it.']);
                }
            }

            Product::whereIn('id', $lines->pluck('product_id'))->orderBy('id')->lockForUpdate()->get();
            $receipt = $order->receipts()->create([
                'reference' => 'GRN-'.now()->format('Ymd').'-'.Str::upper(substr((string) Str::ulid(), -8)),
                'receipt_key' => $data['receipt_key'], 'received_by' => $actor->id,
                'received_at' => now(), 'notes' => $data['notes'] ?? null,
            ]);

            foreach ($delivered as $row) {
                $line = $lines[(int) $row['item_id']];
                $damaged = (int) ($row['damaged'] ?? 0);
                $accepted = (int) $row['quantity'] - $damaged;
                $movement = $accepted > 0
                    ? app(InventoryService::class)->record($line->product_id, 'in', $accepted, $actor, $receipt->reference, 'Receipt for '.$order->reference)
                    : null;

                $receipt->items()->create([
                    'purchase_order_item_id' => $line->id,
                    'inventory_transaction_id' => $movement?->id,
                    'delivered_quantity' => $row['quantity'], 'accepted_quantity' => $accepted,
                    'damaged_quantity' => $damaged,
                ]);
                $line->increment('received_quantity', $accepted);
            }

            $complete = !$order->items()->whereColumn('received_quantity', '<', 'quantity')->exists();
            $order->update([
                'status' => $complete ? 'received' : 'partially_received',
                'received_at' => $complete ? now() : null,
            ]);
            $this->audit($order, $actor, 'goods_received', ['receipt' => $receipt->reference]);

            return $receipt->load('items');
        }, 3);
    }

    public function audit(PurchaseOrder $order, User $actor, string $event, array $details = []): void
    {
        $order->activityLogs()->create(['user_id' => $actor->id, 'event' => $event, 'details' => $details]);
    }

    private function validateItems(Supplier $supplier, array $items): void
    {
        $ids = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id);
        $products = Product::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
        $catalog = $supplier->catalogProducts()->whereIn('products.id', $ids)->get()->keyBy('id');

        if ($ids->unique()->count() !== $ids->count() || $products->count() !== $ids->count() || $catalog->count() !== $ids->count()) {
            throw ValidationException::withMessages(['items' => 'Choose distinct, active products from this supplier catalog.']);
        }

        foreach ($items as $item) {
            $agreement = $catalog[(int) $item['product_id']]->pivot;

            if ($item['quantity'] < $agreement->minimum_order_quantity || $item['quantity'] % $agreement->package_size !== 0) {
                throw ValidationException::withMessages(['items' => 'Follow the supplier minimum quantities and package sizes.']);
            }
        }
    }
}
