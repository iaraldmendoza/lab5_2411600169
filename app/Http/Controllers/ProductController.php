<?php
namespace App\Http\Controllers;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Gate};
class ProductController extends Controller {
 public function index(Request $r){
  $f=$r->validate(['q'=>'nullable|string|max:100','category'=>'nullable|string|max:255','stock'=>'nullable|in:low,out']);
  $products=Product::query()->when($f['q']??null,fn($q,$v)=>$q->where(fn($q)=>$q->where('name','like',"%{$v}%")->orWhere('sku','like',"%{$v}%")))
   ->when($f['category']??null,fn($q,$v)=>$q->inCategory($v))
   ->when(($f['stock']??'')==='low',fn($q)=>$q->lowStock())->when(($f['stock']??'')==='out',fn($q)=>$q->outOfStock())
   ->orderBy('name')->paginate(15)->withQueryString();
  return view('products.index',compact('products'));
 }
 public function create(){return view('products.create',['product'=>new Product]);}
 public function store(ProductRequest $r,InventoryService $s){
  DB::transaction(function()use($r,$s){$d=$r->validated();$opening=(int)$d['quantity'];$d['quantity']=0;$p=Product::create($d);
   if($opening>0)$s->record($p->id,'in',$opening,$r->user(),'Opening inventory');
  });return to_route('products.index')->with('success','Product added successfully.');
 }
 public function show(Product $product,Request $r){
  $r->validate(['from'=>'nullable|date','to'=>'nullable|date|after_or_equal:from']);
  $transactions=$product->transactions()->with('user')->when($r->from,fn($q,$v)=>$q->whereDate('created_at','>=',$v))->when($r->to,fn($q,$v)=>$q->whereDate('created_at','<=',$v))->recent()->paginate(20)->withQueryString();
  return view('products.show',compact('product','transactions'));
 }
 public function edit(Product $product){return view('products.edit',compact('product'));}
 public function update(ProductRequest $r,Product $product,InventoryService $s){
  DB::transaction(function()use($r,$product,$s){
   $p=Product::lockForUpdate()->findOrFail($product->id);$d=$r->validated();$target=(int)$d['quantity'];unset($d['quantity']);
   if($target!==$p->quantity)Gate::authorize('manage-inventory');
   $p->update($d);if($target!==$p->quantity)$s->adjust($p->id,$target,$r->user(),'Product edit correction');
  });return to_route('products.index')->with('success','Product updated successfully.');
 }
 public function destroy(Product $product){Gate::authorize('manage-inventory');$product->delete();return to_route('products.index')->with('success','Product archived. Its stock history is preserved.');}
}
