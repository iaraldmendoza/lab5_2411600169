<?php
namespace App\Observers;
use App\Events\InventoryChanged;
use App\Models\{InventoryTransaction,Product};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class InventoryTransactionObserver {
 public function creating(InventoryTransaction $t):void{
  if(DB::transactionLevel()<1)throw new \LogicException('Use InventoryService to record movements atomically.');
 }
 public function created(InventoryTransaction $t):void{
  $p=Product::lockForUpdate()->findOrFail($t->product_id);$before=$p->quantity;$after=$before+$t->delta;
  if($after<0||$after>2147483647)throw ValidationException::withMessages(['quantity'=>'The movement would create an invalid stock balance. No changes were saved.']);
  $low=$after<=$p->reorder_level;$alert=$low&&!$p->low_stock_alerted;
  $p->forceFill(['quantity'=>$after,'low_stock_alerted'=>$low])->save();
  $t->forceFill(['balance_before'=>$before,'balance_after'=>$after])->saveQuietly();
  InventoryChanged::dispatch(['product_id'=>$p->id,'name'=>$p->name,'sku'=>$p->sku,'quantity'=>$after,'reorder_level'=>$p->reorder_level],$alert);
 }
 public function updating(InventoryTransaction $t):void{throw new \LogicException('Stock movements are immutable. Record a reversal instead.');}
 public function deleting(InventoryTransaction $t):void{throw new \LogicException('Audit records cannot be deleted. Record a reversal instead.');}
}
