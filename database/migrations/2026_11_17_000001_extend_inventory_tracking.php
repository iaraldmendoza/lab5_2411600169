<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up(): void {
  Schema::table('users',fn(Blueprint $t)=>$t->string('role',20)->default('staff'));
  Schema::table('products',function(Blueprint $t){$t->softDeletes();$t->boolean('low_stock_alerted')->default(false);});
  Schema::table('inventory_transactions',function(Blueprint $t){
   $t->string('type',20)->change();$t->integer('quantity')->change();
   $t->text('notes')->nullable();$t->integer('balance_before')->nullable();$t->integer('balance_after')->nullable();
   $t->foreignId('reversal_of_id')->nullable()->unique()->constrained('inventory_transactions')->restrictOnDelete();
   $t->timestamp('voided_at')->nullable();$t->index(['product_id','created_at','id'],'product_history_index');
   $t->index(['type','created_at'],'movement_type_date_index');
  });
  foreach(DB::table('products')->get() as $p){
   $rows=DB::table('inventory_transactions')->where('product_id',$p->id)->orderBy('id')->get();
   $delta=fn($row)=>$row->type==='out'?-$row->quantity:$row->quantity;
   $balance=$p->quantity-$rows->sum($delta);
   foreach($rows as $row){$next=$balance+$delta($row);DB::table('inventory_transactions')->where('id',$row->id)->update(['balance_before'=>$balance,'balance_after'=>$next]);$balance=$next;}
  }
  Schema::create('notifications',function(Blueprint $t){$t->uuid('id')->primary();$t->string('type');$t->morphs('notifiable');$t->text('data');$t->timestamp('read_at')->nullable();$t->timestamps();});
 }
 public function down(): void {
  Schema::dropIfExists('notifications');
  Schema::table('inventory_transactions',function(Blueprint $t){$t->dropForeign(['reversal_of_id']);$t->dropIndex('product_history_index');$t->dropIndex('movement_type_date_index');$t->dropColumn(['notes','balance_before','balance_after','reversal_of_id','voided_at']);});
  Schema::table('products',fn(Blueprint $t)=>$t->dropColumn(['deleted_at','low_stock_alerted']));
  Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('role'));
 }
};
