<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\ProcurementCache;
use App\Services\PurchaseOrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProcurementSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['Timberline Supply', 'Ramon Cruz', 'orders@timberline.test', '0917 200 1001', '18 Warehouse Road, Davao City', 7, 3000],
            ['Metro Hardware', 'Ana Reyes', 'sales@metrohardware.test', '0917 200 1002', '42 Industrial Avenue, Davao City', 3, 1000],
            ['Builder Tools', 'Marco Santos', 'orders@buildertools.test', '0917 200 1003', '9 Trade Center, Davao City', 5, 1500],
            ['Bright Electrical', 'Liza Ramos', 'sales@brightelectrical.test', '0917 200 1004', '25 Supply Street, Davao City', 7, 2500],
            ['Flow Plumbing', 'Joel Garcia', 'orders@flowplumbing.test', '0917 200 1005', '31 Riverside Road, Davao City', 4, 1000],
            ['Colorworks', 'Mia Flores', 'sales@colorworks.test', '0917 200 1006', '12 Commercial Avenue, Davao City', 4, 2000],
        ];

        foreach ($suppliers as [$company, $contact, $email, $phone, $address, $lead, $minimum]) {
            $supplier = Supplier::firstOrCreate(['email' => $email], [
                'company_name' => $company, 'contact_person' => $contact, 'phone' => $phone,
                'address' => $address, 'lead_time_days' => $lead, 'minimum_order_amount' => $minimum,
                'payment_terms' => 'Net 30', 'active' => true,
            ]);

            foreach (Product::where('supplier', $company)->get() as $product) {
                $package = match ($product->sku) {
                    'LUM-001', 'LUM-002', 'LUM-003', 'PNT-001' => 2,
                    'HDW-001', 'HDW-002', 'PLB-001', 'PLB-002' => 5,
                    'ELE-002' => 10,
                    'PNT-002' => 6,
                    default => 1,
                };
                $product->forceFill([
                    'supplier_id' => $supplier->id,
                    'safety_stock' => max(3, $product->reorder_level),
                    'target_stock' => max(50, $product->reorder_level * 4),
                    'daily_usage_estimate' => 1,
                ])->save();
                $supplier->catalogProducts()->syncWithoutDetaching([$product->id => [
                    'unit_cost' => round((float) $product->unit_price * .75, 2),
                    'minimum_order_quantity' => $package, 'package_size' => $package,
                    'lead_time_days' => $lead,
                ]]);
            }
        }

        if (PurchaseOrder::where('notes', 'like', 'Opening purchasing record%')->exists()) {
            return;
        }

        $buyer = User::where('email', 'buyer@facinalhardware.test')->firstOrFail();
        $manager = User::where('role', 'manager')->firstOrFail();
        $warehouse = User::where('role', 'warehouse')->firstOrFail();
        $service = app(PurchaseOrderService::class);
        $clock = now()->copy();

        try {
            foreach ([
                ['LUM-002', 20, 'received', 24, 6, 0],
                ['HDW-001', 100, 'received', 16, 6, 0],
                ['TLS-002', 5, 'received', 10, 4, 1],
                ['PNT-001', 30, 'pending', 1, 0, 0],
                ['PLB-001', 40, 'draft', 0, 0, 0],
                ['ELE-002', 100, 'approved', 1, 0, 0],
                ['LUM-001', 100, 'sent', 2, 0, 0],
                ['TLS-001', 20, 'partially_received', 3, 2, 0],
                ['ELE-001', 4, 'rejected', 1, 0, 0],
                ['PLB-002', 50, 'cancelled', 1, 0, 0],
                ['LUM-002', 100, 'pending', 0, 0, 0],
            ] as [$sku, $quantity, $status, $daysAgo, $deliveryDays, $damaged]) {
                $product = Product::with('supplierRecord', 'suppliers')->where('sku', $sku)->firstOrFail();
                $supplier = $product->supplierRecord;
                $placed = $clock->copy()->subDays($daysAgo)->startOfDay()->addHours(9);
                Carbon::setTestNow($placed);
                $order = $service->create([
                    'supplier_id' => $supplier->id, 'order_date' => today()->toDateString(),
                    'expected_delivery_date' => today()->addDays($supplier->lead_time_days)->toDateString(),
                    'status' => $status === 'draft' ? 'draft' : 'pending', 'tax_rate' => 0,
                    'items' => [['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $product->suppliers->firstWhere('id', $supplier->id)->pivot->unit_cost]],
                    'notes' => 'Opening purchasing record for '.$product->name.'.',
                ], $buyer);

                if ($status === 'rejected') {
                    $service->transition($order, 'reject', $manager, 'Delivery timing does not meet the current purchasing plan.');
                    continue;
                }

                if ($status === 'cancelled') {
                    $service->transition($order, 'cancel', $manager);
                    continue;
                }

                if (in_array($status, ['approved', 'sent', 'received', 'partially_received'], true)
                    || ($status === 'pending' && $order->requiresSecondApproval())) {
                    $order = $service->transition($order, 'approve', $manager);
                }

                if (in_array($status, ['sent', 'received', 'partially_received'], true)) {
                    $order = $service->transition($order, 'send', $manager);
                }

                if (in_array($status, ['received', 'partially_received'], true)) {
                    Carbon::setTestNow($placed->copy()->addDays($deliveryDays)->addHour());
                    $line = $order->items()->firstOrFail();
                    $service->receive($order, [
                        'receipt_key' => (string) Str::uuid(), 'notes' => 'Delivery counted and inspected at receiving.',
                        'items' => [['item_id' => $line->id, 'quantity' => $status === 'partially_received' ? 5 : $quantity, 'damaged' => $damaged]],
                    ], $warehouse);

                    if ($damaged) {
                        Carbon::setTestNow($placed->copy()->addDays($deliveryDays + 1)->addHour());
                        $service->receive($order, [
                            'receipt_key' => (string) Str::uuid(), 'notes' => 'Replacement for damaged units received.',
                            'items' => [['item_id' => $line->id, 'quantity' => $damaged, 'damaged' => 0]],
                        ], $warehouse);
                    }
                }
            }

            Carbon::setTestNow($clock->copy()->subDays(5));

            foreach (['LUM-001' => 30, 'HDW-001' => 30, 'PNT-001' => 8, 'TLS-001' => 10, 'TLS-002' => 2, 'LUM-002' => 15] as $sku => $quantity) {
                $product = Product::where('sku', $sku)->firstOrFail();
                app(InventoryService::class)->record($product->id, 'out', $quantity, $warehouse, 'SO-2026-'.$product->sku, 'Materials issued against store sales.');
            }
        } finally {
            Carbon::setTestNow();
            ProcurementCache::clear();
        }
    }
}
