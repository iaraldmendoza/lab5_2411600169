<?php
namespace App\Http\Controllers;
use App\Models\{InventoryTransaction,Product};
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
class InventoryTransactionController extends Controller {
 public function index(Request $r){
  $r->validate(['product_id'=>'nullable|integer','type'=>'nullable|in:in,out,adjustment','from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']);
  $transactions=InventoryTransaction::with(['product','user'])
   ->when($r->product_id,fn($q,$v)=>$q->where('product_id',$v))->when($r->type,fn($q,$v)=>$q->where('type',$v))
   ->when($r->from,fn($q,$v)=>$q->whereDate('created_at','>=',$v))->when($r->to,fn($q,$v)=>$q->whereDate('created_at','<=',$v))
   ->recent()->paginate(20)->withQueryString();
  return view('transactions.index',['transactions'=>$transactions,'products'=>Product::orderBy('name')->get()]);
 }
 public function create(){return view('transactions.create',['products'=>Product::orderBy('name')->get()]);}
 public function store(Request $r,InventoryService $s){
  $d=$r->validate(['product_id'=>'required|integer|exists:products,id,deleted_at,NULL','type'=>'required|in:in,out','quantity'=>'required|integer|min:1|max:2147483647','reference'=>'nullable|string|max:100','notes'=>'nullable|string|max:2000']);
  $t=$s->record($d['product_id'],$d['type'],$d['quantity'],$r->user(),$d['reference']??null,$d['notes']??null);
  return to_route('transactions.show',$t)->with('success','Stock movement recorded. Inventory updated.');
 }
 public function show(InventoryTransaction $transaction){return view('transactions.show',['transaction'=>$transaction->load(['product','user','reversal','reversalOf'])]);}
 public function destroy(InventoryTransaction $transaction,Request $r,InventoryService $s){
  Gate::authorize('manage-inventory');$t=$s->reverse($transaction,$r->user());
  return to_route('transactions.show',$t)->with('success','Movement reversed. The original remains in the audit trail.');
 }
 public function adjustment(){Gate::authorize('manage-inventory');return view('transactions.adjustment',['products'=>Product::orderBy('name')->get()]);}
 public function adjust(Request $r,InventoryService $s){
  Gate::authorize('manage-inventory');
  $d=$r->validate(['product_id'=>'required|integer|exists:products,id,deleted_at,NULL','physical_count'=>'required|integer|min:0|max:2147483647','reason'=>'required|in:Physical count,Damaged goods,Loss,Correction','notes'=>'nullable|string|max:2000']);
  $t=$s->adjust($d['product_id'],$d['physical_count'],$r->user(),$d['reason'],$d['notes']??null);
  return to_route('transactions.show',$t)->with('success','Physical count reconciled and adjustment recorded.');
 }
}
