<?php

namespace Tests\Feature;

use App\Mail\LowStockAlert;
use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\OrderTotals;
use App\Services\PurchaseOrderService;
use App\Services\ReorderService;
use App\Services\SupplierPerformanceService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcurementTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
    }

    private function actor(string $role = 'staff'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function supplier(array $extra = []): Supplier
    {
        return Supplier::create(array_merge([
            'company_name' => 'Central Building Supply', 'contact_person' => 'Ana Cruz',
            'email' => Str::uuid().'@supplier.test', 'phone' => '0917 200 1000',
            'address' => '42 Industrial Road', 'payment_terms' => 'Net 30',
            'lead_time_days' => 3, 'minimum_order_amount' => 0, 'active' => true,
        ], $extra));
    }

    private function product(Supplier $supplier, array $extra = []): Product
    {
        $product = Product::create(array_merge([
            'name' => 'Steel Hex Bolts 10 mm', 'sku' => 'BLT-'.Str::upper(Str::random(8)),
            'category' => 'Hardware', 'quantity' => 10, 'reorder_level' => 5,
            'unit_price' => 20, 'supplier_id' => $supplier->id, 'supplier' => $supplier->company_name,
            'safety_stock' => 5, 'target_stock' => 50, 'daily_usage_estimate' => 2,
        ], $extra));
        $product->suppliers()->attach($supplier->id, [
            'unit_cost' => 10, 'minimum_order_quantity' => 4, 'package_size' => 4, 'lead_time_days' => 3,
        ]);

        return $product;
    }

    private function order(?User $buyer = null, string $status = 'pending', int $quantity = 20): PurchaseOrder
    {
        $supplier = $this->supplier();
        $product = $this->product($supplier);

        return app(PurchaseOrderService::class)->create([
            'supplier_id' => $supplier->id, 'status' => $status,
            'order_date' => today()->toDateString(), 'expected_delivery_date' => today()->addDays(3)->toDateString(),
            'tax_rate' => 0, 'items' => [['product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => 10]],
        ], $buyer ?? $this->actor());
    }

    private function sentOrder(): PurchaseOrder
    {
        $order = $this->order();
        $manager = $this->actor('manager');
        $service = app(PurchaseOrderService::class);
        $service->transition($order, 'approve', $manager);

        return $service->transition($order, 'send', $manager);
    }

    private function receiptData(PurchaseOrder $order, int $quantity = 8, int $damaged = 0): array
    {
        return ['receipt_key' => (string) Str::uuid(), 'items' => [[
            'item_id' => $order->items()->first()->id, 'quantity' => $quantity, 'damaged' => $damaged,
        ]]];
    }

    public function test_only_managers_can_manage_suppliers(): void
    {
        $this->actingAs($this->actor())->get('/suppliers/create')->assertForbidden();
        $this->actingAs($this->actor('warehouse'))->get('/suppliers/create')->assertForbidden();
        $this->actingAs($this->actor('manager'))->get('/suppliers/create')->assertOk();
    }

    public function test_supplier_validation_rejects_bad_contact_data(): void
    {
        $this->actingAs($this->actor('manager'))->postJson('/suppliers', [
            'company_name' => 'Supply Company', 'contact_person' => 'Ana', 'email' => 'bad-email',
            'phone' => 'letters', 'address' => 'Trade Road', 'payment_terms' => 'COD',
            'lead_time_days' => -1, 'minimum_order_amount' => -5, 'active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'phone', 'lead_time_days', 'minimum_order_amount']);
    }

    public function test_supplier_with_an_active_order_cannot_be_archived(): void
    {
        $order = $this->order();
        $this->actingAs($this->actor('admin'))->deleteJson('/suppliers/'.$order->supplier_id)->assertUnprocessable();
        $this->assertFalse($order->supplier->fresh()->trashed());
    }

    public function test_soft_deleted_supplier_remains_in_order_history_and_can_be_restored(): void
    {
        $order = $this->order();
        $manager = $this->actor('manager');
        app(PurchaseOrderService::class)->transition($order, 'cancel', $manager);
        $id = $order->supplier_id;
        $this->actingAs($manager)->delete('/suppliers/'.$id)->assertRedirect();
        $this->assertSoftDeleted('suppliers', ['id' => $id]);
        $this->assertSame('Central Building Supply', $order->fresh()->supplier->company_name);
        $this->assertNull(Supplier::find($id));
        $this->actingAs($this->actor('admin'))->post('/suppliers/'.$id.'/restore')->assertRedirect();
        $this->assertFalse(Supplier::findOrFail($id)->active);
    }

    public function test_supplier_email_stays_reserved_after_soft_delete(): void
    {
        $supplier = $this->supplier();
        $supplier->delete();
        $data = $supplier->getAttributes();
        $this->actingAs($this->actor('admin'))->postJson('/suppliers', $data)
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_many_to_many_catalog_supports_alternative_suppliers(): void
    {
        $first = $this->supplier();
        $second = $this->supplier();
        $product = $this->product($first);
        $second->catalogProducts()->attach($product->id, ['unit_cost' => 11, 'minimum_order_quantity' => 1, 'package_size' => 1]);
        $this->assertCount(2, $product->suppliers);
        $this->assertSame(1, $first->products()->count());
        $this->assertSame(1, $second->catalogProducts()->count());
    }

    public function test_staff_can_create_a_pending_order_but_cannot_approve_it(): void
    {
        $this->actor('admin');
        $buyer = $this->actor();
        $order = $this->order($buyer);
        $this->actingAs($buyer)->postJson('/purchase-orders/'.$order->id.'/action/approve')->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
        Mail::assertQueued(PurchaseOrderCreated::class);
    }

    public function test_even_an_administrator_cannot_approve_their_own_order(): void
    {
        $admin = $this->actor('admin');
        $order = $this->order($admin);
        $this->assertFalse(Gate::forUser($admin)->allows('approve', $order));
    }

    public function test_large_orders_require_two_different_approvers_and_admin_final_approval(): void
    {
        $order = $this->order(quantity: 6000);
        $manager = $this->actor('manager');
        $service = app(PurchaseOrderService::class);
        $service->transition($order, 'approve', $manager);
        $order->refresh();
        $this->assertSame('pending', $order->status);
        $this->assertSame($manager->id, $order->manager_approved_by);
        $this->assertFalse(Gate::forUser($this->actor('manager'))->allows('approve', $order));
        $admin = $this->actor('admin');
        $service->transition($order, 'approve', $admin);
        $this->assertSame('approved', $order->fresh()->status);
        $this->assertSame($admin->id, $order->fresh()->approved_by);
    }

    public function test_same_administrator_cannot_supply_both_large_order_approvals(): void
    {
        $order = $this->order(quantity: 6000);
        $admin = $this->actor('admin');
        app(PurchaseOrderService::class)->transition($order, 'approve', $admin);
        $this->assertFalse(Gate::forUser($admin)->allows('approve', $order->fresh()));
    }

    public function test_rejection_requires_a_reason(): void
    {
        $order = $this->order();
        $this->actingAs($this->actor('manager'))->postJson('/purchase-orders/'.$order->id.'/action/reject')
            ->assertUnprocessable()->assertJsonValidationErrors('reason');
    }

    public function test_only_drafts_can_be_edited(): void
    {
        $buyer = $this->actor();
        $order = $this->order($buyer);
        $this->actingAs($buyer)->get('/purchase-orders/'.$order->id.'/edit')->assertForbidden();
    }

    public function test_price_snapshot_is_not_changed_by_a_product_price_change(): void
    {
        $order = $this->order();
        $order->items->first()->product->update(['unit_price' => 999]);
        $this->assertSame('10.00', $order->fresh()->items->first()->unit_price);
        $this->assertSame('200.00', $order->fresh()->total);
    }

    public function test_integer_cent_totals_keep_tax_rounding_correct(): void
    {
        $totals = OrderTotals::calculate([['quantity' => 3, 'unit_price' => '0.10']], '10');
        $this->assertSame(['subtotal' => '0.30', 'tax_amount' => '0.03', 'total' => '0.33'], $totals);
    }

    public function test_partial_receipt_records_only_accepted_stock_and_is_idempotent(): void
    {
        $order = $this->sentOrder();
        $warehouse = $this->actor('warehouse');
        $data = $this->receiptData($order, 8, 2);
        $service = app(PurchaseOrderService::class);
        $first = $service->receive($order, $data, $warehouse);
        $second = $service->receive($order, $data, $warehouse);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('goods_receipts', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertSame(16, $order->items->first()->product->fresh()->quantity);
        $this->assertSame(6, $order->fresh()->items->first()->received_quantity);
        $this->assertSame('partially_received', $order->fresh()->status);
    }

    public function test_final_partial_receipt_completes_the_order(): void
    {
        $order = $this->sentOrder();
        $warehouse = $this->actor('warehouse');
        $service = app(PurchaseOrderService::class);
        $service->receive($order, $this->receiptData($order, 8), $warehouse);
        $service->receive($order, $this->receiptData($order, 12), $warehouse);
        $this->assertSame('received', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->received_at);
        $this->assertSame(30, $order->items->first()->product->fresh()->quantity);
    }

    public function test_over_receipt_leaves_stock_and_receipts_unchanged(): void
    {
        $order = $this->sentOrder();
        $this->actingAs($this->actor('warehouse'))->postJson('/purchase-orders/'.$order->id.'/receive', $this->receiptData($order, 21))
            ->assertUnprocessable();
        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertSame(10, $order->items->first()->product->fresh()->quantity);
    }

    public function test_invalid_damage_and_negative_quantities_are_rejected(): void
    {
        $order = $this->sentOrder();
        $warehouse = $this->actor('warehouse');
        $this->actingAs($warehouse)->postJson('/purchase-orders/'.$order->id.'/receive', $this->receiptData($order, 5, 6))->assertUnprocessable();
        $this->postJson('/purchase-orders/'.$order->id.'/receive', $this->receiptData($order, -1))->assertUnprocessable();
        $this->assertDatabaseCount('goods_receipts', 0);
    }

    public function test_receiving_is_limited_to_warehouse_staff_and_administrators(): void
    {
        $order = $this->sentOrder();
        $this->actingAs($this->actor())->postJson('/purchase-orders/'.$order->id.'/receive', $this->receiptData($order))->assertForbidden();
        $this->actingAs($this->actor('manager'))->postJson('/purchase-orders/'.$order->id.'/receive', $this->receiptData($order))->assertForbidden();
    }

    public function test_exception_after_stock_observer_rolls_back_the_entire_receipt(): void
    {
        $order = $this->sentOrder();
        $event = 'eloquent.created: '.InventoryTransaction::class;
        Event::listen($event, fn () => throw new \RuntimeException('Injected persistence failure'));

        try {
            app(PurchaseOrderService::class)->receive($order, $this->receiptData($order), $this->actor('warehouse'));
            $this->fail('The injected failure was not raised.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Injected persistence failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }

        $this->assertDatabaseCount('goods_receipts', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertSame(10, $order->items->first()->product->fresh()->quantity);
        $this->assertSame(0, $order->fresh()->items->first()->received_quantity);
    }

    public function test_reorder_formula_uses_usage_lead_time_and_safety_stock(): void
    {
        $supplier = $this->supplier(['lead_time_days' => 7]);
        $product = $this->product($supplier, ['quantity' => 1000, 'safety_stock' => 20]);
        $product->suppliers()->updateExistingPivot($supplier->id, ['lead_time_days' => 7]);
        app(\App\Services\InventoryService::class)->record($product->id, 'out', 300, $this->actor(), 'Sales');
        $this->assertSame(90, app(ReorderService::class)->calculateReorderPoint($product->fresh()));
    }

    public function test_order_quantity_rounds_up_to_supplier_package_size(): void
    {
        $product = $this->product($this->supplier(), ['target_stock' => 51]);
        $this->assertSame(44, app(ReorderService::class)->calculateOptimalOrderQuantity($product));
    }

    public function test_reorder_generation_groups_products_and_does_not_duplicate_outstanding_orders(): void
    {
        $admin = $this->actor('admin');
        $supplier = $this->supplier();
        $this->product($supplier);
        $this->product($supplier);
        $first = app(ReorderService::class)->generatePurchaseOrders($admin);
        $second = app(ReorderService::class)->generatePurchaseOrders($admin);
        $this->assertCount(1, $first);
        $this->assertSame(2, $first->first()->items()->count());
        $this->assertCount(0, $second);
        $this->assertSame('draft', $first->first()->status);
    }

    public function test_automatic_order_meets_supplier_minimum_amount(): void
    {
        $admin = $this->actor('admin');
        $product = $this->product($this->supplier(['minimum_order_amount' => 1000]));
        $order = app(ReorderService::class)->generatePurchaseOrders($admin)->first();
        $this->assertGreaterThanOrEqual(1000, (float) $order->total);
        $this->assertSame(0, $order->items->first()->quantity % 4);
    }

    public function test_inactive_supplier_is_excluded_from_automatic_reordering(): void
    {
        $this->product($this->supplier(['active' => false]));
        $this->assertCount(0, app(ReorderService::class)->getProductsNeedingReorder());
    }

    public function test_purchase_order_requires_active_catalog_products_and_valid_pack_sizes(): void
    {
        $supplier = $this->supplier();
        $product = $this->product($supplier);
        $payload = ['supplier_id' => $supplier->id, 'order_date' => today()->toDateString(), 'expected_delivery_date' => today()->addDays(2)->toDateString(), 'tax_rate' => 0, 'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 10]]];
        $this->actingAs($this->actor())->postJson('/purchase-orders', $payload)->assertUnprocessable();
        $payload['items'][0]['quantity'] = 8;
        $payload['items'][] = $payload['items'][0];
        $this->postJson('/purchase-orders', $payload)->assertUnprocessable();
        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_approval_and_dispatch_queue_the_correct_mailables(): void
    {
        $order = $this->sentOrder();
        Mail::assertQueued(PurchaseOrderApproved::class, fn ($mail) => $mail->order->id === $order->id && $mail->eventStatus === 'approved');
        Mail::assertQueued(PurchaseOrderSent::class, fn ($mail) => $mail->order->id === $order->id && $mail->eventStatus === 'sent');
    }

    public function test_reorder_command_queues_summary_and_returns_success(): void
    {
        $this->actor('admin');
        $this->product($this->supplier());
        $this->artisan('inventory:check-reorders')->assertSuccessful();
        Mail::assertQueued(LowStockAlert::class);
    }

    public function test_custom_kernel_registers_the_daily_schedule_without_overlap(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event) => str_contains($event->command ?? '', 'inventory:check-reorders'));
        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertSame('0 8 * * *', $event->expression);
        $this->assertSame('Asia/Manila', $event->timezone);
        $this->assertTrue($event->withoutOverlapping);
    }

    public function test_dashboard_apis_require_login_and_show_correct_pending_totals(): void
    {
        foreach (['pending-orders','recent-orders','supplier-performance','reorder-alerts'] as $widget) {
            $this->getJson('/api/dashboard/'.$widget)->assertUnauthorized();
        }
        $order = $this->order();
        $this->actingAs($this->actor())->getJson('/api/dashboard/pending-orders')
            ->assertOk()->assertJsonPath('data.count', 1)->assertJsonPath('data.value', 200);
    }

    public function test_receipt_metrics_include_damaged_units_in_accuracy(): void
    {
        $order = $this->sentOrder();
        app(PurchaseOrderService::class)->receive($order, $this->receiptData($order, 8, 2), $this->actor('warehouse'));
        $metrics = app(SupplierPerformanceService::class)->forSupplier($order->supplier);
        $this->assertSame(75.0, $metrics['order_accuracy']);
        $this->assertNull($metrics['on_time_percentage']);
    }

    public function test_csv_exports_escape_spreadsheet_formulas(): void
    {
        $this->product($this->supplier(), ['name' => '=1+1']);
        $response = $this->actingAs($this->actor())->get('/exports/products/csv')->assertOk();
        $this->assertStringContainsString("'=1+1", $response->streamedContent());
    }

    public function test_all_four_export_types_download_real_csv_and_pdf_files(): void
    {
        $this->order();
        $this->actingAs($this->actor());
        foreach (['products', 'purchase-orders', 'transactions', 'supplier-performance'] as $type) {
            $this->get('/exports/'.$type.'/csv')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
            $pdf = $this->get('/exports/'.$type.'/pdf')->assertOk();
            $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        }
    }

    public function test_supplier_and_order_search_filters_apply_together(): void
    {
        $order = $this->order();
        $this->actingAs($this->actor())->get('/purchase-orders?status=pending&supplier_id='.$order->supplier_id.'&q='.urlencode($order->reference))->assertOk()->assertSee($order->reference);
        $this->get('/purchase-orders?status=received&q='.urlencode($order->reference))->assertOk()->assertSee('No purchase orders match your filters.');
        $this->get('/suppliers?q=Central&active=1&sort=lead_time_days')->assertOk()->assertSee('Central Building Supply');
    }
}
