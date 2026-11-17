<?php
namespace App\Http\Controllers;
use App\Models\{Product,InventoryTransaction};
use Illuminate\Http\Request;
class InventoryReportController extends Controller {
 private function movements(Request $r){return InventoryTransaction::with(['product','user'])->when($r->from,fn($q,$v)=>$q->whereDate('created_at','>=',$v))->when($r->to,fn($q,$v)=>$q->whereDate('created_at','<=',$v))->recent();}
 private function dates(Request $r):void{$r->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']);}
 public function index(Request $r){
  $this->dates($r);$products=Product::orderBy('name')->get();$lowProducts=Product::lowStock()->orderBy('quantity')->get();
  $inventoryValue=$products->sum(fn($p)=>$p->quantity*$p->unit_price);$movements=$this->movements($r)->paginate(20)->withQueryString();
  return view('reports.inventory',compact('products','lowProducts','inventoryValue','movements'));
 }
 public function csv(Request $r){
  $this->dates($r);$r->validate(['report'=>'nullable|in:valuation,low,movements']);$type=$r->input('report','valuation');
  return response()->streamDownload(function()use($r,$type){
   $f=fopen('php://output','w');$write=fn($row)=>fputcsv($f,array_map(fn($v)=>is_string($v)&&preg_match('/^[=+@\-\t\r]/',$v)?"'".$v:$v,$row));
   if($type==='movements'){
    $write(['Date','Product','Type','Change','Balance','Reference','User','Reversed']);
    foreach($this->movements($r)->cursor() as $t)$write([$t->created_at,$t->product->name,$t->type_label,$t->delta,$t->balance_after,$t->reference,$t->user?->name,$t->voided_at?'Yes':'No']);
   }else{
    $write(['Product','SKU','Category','Quantity','Reorder level','Unit price','Value']);$q=Product::orderBy('name');if($type==='low')$q->lowStock();
    foreach($q->cursor() as $p)$write([$p->name,$p->sku,$p->category,$p->quantity,$p->reorder_level,$p->unit_price,number_format($p->quantity*$p->unit_price,2,'.','')]);
   }fclose($f);
  },'inventory-'.$type.'.csv',['Content-Type'=>'text/csv']);
 }
}
