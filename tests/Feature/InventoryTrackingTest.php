<?php

namespace Tests\Feature;

use App\Models\{Product,User,InventoryTransaction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTrackingTest extends TestCase
{
    use RefreshDatabase;
    private function product(): Product
    {
        return Product::create(['name' => 'Claw Hammer 16 oz','sku' => 'TLS-001','category' => 'Tools','quantity' => 12,'reorder_level' => 5,'unit_price' => 350]);
    }
    public function test_movements_update_stock_and_running_balances(): void
    {
        $u = User::factory()->create();
        $p = $this->product();
        $this->actingAs($u)->post('/transactions', ['product_id' => $p->id,'type' => 'in','quantity' => 5])->assertRedirect();
        $this->assertSame(17, $p->fresh()->quantity);
        $this->assertDatabaseHas('inventory_transactions', ['balance_before' => 12,'balance_after' => 17,'user_id' => $u->id]);
        $this->post('/transactions', ['product_id' => $p->id,'type' => 'out','quantity' => 4])->assertRedirect();
        $this->assertSame(13, $p->fresh()->quantity);
    }
    public function test_overselling_leaves_stock_and_audit_unchanged(): void
    {
        $u = User::factory()->create();
        $p = $this->product();
        $this->actingAs($u)->postJson('/transactions', ['product_id' => $p->id,'type' => 'out','quantity' => 13])->assertUnprocessable()->assertJsonValidationErrors('quantity');
        $this->assertSame(12, $p->fresh()->quantity);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }
    public function test_staff_cannot_adjust_or_spoof_the_actor(): void
    {
        $staff = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $p = $this->product();
        $this->actingAs($staff)->postJson('/stock-adjustments', ['product_id' => $p->id,'physical_count' => 2,'reason' => 'Physical count'])->assertForbidden();
        $this->post('/transactions', ['product_id' => $p->id,'type' => 'in','quantity' => 1,'user_id' => $admin->id])->assertRedirect();
        $this->assertDatabaseHas('inventory_transactions', ['user_id' => $staff->id]);
    }
    public function test_reversals_preserve_original_records_and_cannot_repeat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $p = $this->product();
        $this->actingAs($admin)->post('/transactions', ['product_id' => $p->id,'type' => 'out','quantity' => 3]);
        $t = InventoryTransaction::firstOrFail();
        $this->delete('/transactions/'.$t->id)->assertRedirect();
        $this->assertSame(12, $p->fresh()->quantity);
        $this->assertNotNull($t->fresh()->voided_at);
        $this->assertDatabaseCount('inventory_transactions', 2);
        $this->deleteJson('/transactions/'.$t->id)->assertUnprocessable();
    }
    public function test_archiving_keeps_transaction_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $p = $this->product();
        $this->actingAs($admin)->post('/transactions', ['product_id' => $p->id,'type' => 'in','quantity' => 1]);
        $t = InventoryTransaction::firstOrFail();
        $this->delete('/products/'.$p->id)->assertRedirect();
        $this->assertSoftDeleted('products', ['id' => $p->id]);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->get('/transactions/'.$t->id)->assertOk();
    }
    public function test_json_endpoints_require_authentication(): void
    {
        $this->getJson('/api/dashboard/stats')->assertUnauthorized();
    }
}
