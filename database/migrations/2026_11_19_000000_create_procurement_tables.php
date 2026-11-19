<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->index();
            $table->string('contact_person');
            $table->string('email')->unique();
            $table->string('phone', 40);
            $table->text('address');
            $table->string('payment_terms', 60)->default('Net 30');
            $table->unsignedSmallInteger('lead_time_days')->default(7);
            $table->decimal('minimum_order_amount', 12, 2)->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('safety_stock')->default(5);
            $table->unsignedInteger('target_stock')->default(50);
            $table->decimal('daily_usage_estimate', 10, 2)->default(1);
        });

        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->decimal('unit_cost', 12, 2);
            $table->unsignedInteger('minimum_order_quantity')->default(1);
            $table->unsignedInteger('package_size')->default(1);
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'supplier_id']);
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->date('order_date')->index();
            $table->date('expected_delivery_date');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('manager_approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('manager_approved_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->boolean('auto_generated')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('received_quantity')->default(0);
            $table->timestamps();
            $table->unique(['purchase_order_id', 'product_id']);
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->uuid('receipt_key')->unique();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_transaction_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('delivered_quantity');
            $table->unsignedInteger('accepted_quantity');
            $table->unsignedInteger('damaged_quantity')->default(0);
            $table->timestamps();
            $table->unique(['goods_receipt_id', 'purchase_order_item_id'], 'receipt_item_unique');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('loggable');
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('event', 80);
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('product_supplier');
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['safety_stock', 'target_stock', 'daily_usage_estimate']);
        });
        Schema::dropIfExists('suppliers');
    }
};
