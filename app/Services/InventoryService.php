<?php
namespace App\Services;
use App\Models\{Product,User,InventoryTransaction};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class InventoryService {
 public function record(int $id,string $type,int $quantity,User $actor,?string $reference=null,?string $notes=null):InventoryTransaction {
  return DB::transaction(function()use($id,$type,$quantity,$actor,$reference,$notes){
   $p=Product::lockForUpdate()->findOrFail($id);
   if(!in_array($type,['in','out'],true)||$quantity<1)throw ValidationException::withMessages(['quantity'=>'Enter a positive whole number.']);
   if($type==='out'&&$quantity>$p->quantity)throw ValidationException::withMessages(['quantity'=>"Only {$p->quantity} units are available."]);
   return InventoryTransaction::create(['product_id'=>$p->id,'user_id'=>$actor->id,'type'=>$type,'quantity'=>$quantity,'reference'=>$reference,'notes'=>$notes]);
  },3);
 }
 public function adjust(int $id,int $physical,User $actor,string $reason,?string $notes=null):InventoryTransaction {
  return DB::transaction(function()use($id,$physical,$actor,$reason,$notes){
   $p=Product::lockForUpdate()->findOrFail($id);$difference=$physical-$p->quantity;
   if($physical<0||$physical>2147483647||$difference===0)throw ValidationException::withMessages(['physical_count'=>'Enter a different nonnegative physical count.']);
   return InventoryTransaction::create(['product_id'=>$p->id,'user_id'=>$actor->id,'type'=>'adjustment','quantity'=>$difference,'reference'=>'ADJ-'.now()->format('Ymd-His'),'notes'=>$reason.($notes?': '.$notes:'')]);
  },3);
 }
 public function reverse(InventoryTransaction $transaction,User $actor):InventoryTransaction {
  return DB::transaction(function()use($transaction,$actor){
   $original=InventoryTransaction::lockForUpdate()->findOrFail($transaction->id);
   if($original->voided_at||$original->reversal_of_id)throw ValidationException::withMessages(['transaction'=>'This movement cannot be reversed again.']);
   Product::lockForUpdate()->findOrFail($original->product_id);
   $reversal=InventoryTransaction::create(['product_id'=>$original->product_id,'user_id'=>$actor->id,'type'=>'adjustment','quantity'=>-$original->delta,'reference'=>'REV-'.$original->id,'notes'=>'Reversal of movement #'.$original->id.'. Original record retained for audit.','reversal_of_id'=>$original->id]);
   $original->forceFill(['voided_at'=>now()])->saveQuietly();return $reversal;
  },3);
 }
}
