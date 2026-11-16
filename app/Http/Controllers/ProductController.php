<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Http\Requests\ProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProductController extends Controller {
    public function index(Request $request) {
        $filters=$request->validate(['q'=>'nullable|string|max:100','category'=>'nullable|string|max:255','stock'=>'nullable|in:low,out']);
        $products=Product::query()
            ->when($filters['q']??null,fn($query,$q)=>$query->where(fn($query)=>$query->where('name','like',"%{$q}%")->orWhere('sku','like',"%{$q}%")))
            ->when($filters['category']??null,fn($query,$category)=>$query->where('category',$category))
            ->when(($filters['stock']??'')==='low',fn($query)=>$query->whereColumn('quantity','<=','reorder_level'))
            ->when(($filters['stock']??'')==='out',fn($query)=>$query->where('quantity',0))
            ->orderBy('name')->paginate(15)->withQueryString();
        return view('products.index',compact('products'));
    }
    public function create() { return view('products.create',['product'=>new Product]); }
    public function store(ProductRequest $request) {
        DB::transaction(function() use($request) {
            $product=Product::create($request->validated());
            if($product->quantity>0) $product->transactions()->create(['type'=>'in','quantity'=>$product->quantity,'reference'=>'Opening stock','user_id'=>$request->user()->id]);
        });
        return to_route('products.index')->with('success','Product added successfully.');
    }
    public function show(Product $product) {
        $product->load(['transactions'=>fn($q)=>$q->with('user')->latest()->limit(20)]);
        return view('products.show',compact('product'));
    }
    public function edit(Product $product) { return view('products.edit',compact('product')); }
    public function update(ProductRequest $request,Product $product) {
        DB::transaction(function() use($request,$product) {
            $locked=Product::lockForUpdate()->findOrFail($product->id);
            $change=(int)$request->validated('quantity')-$locked->quantity;
            $locked->update($request->validated());
            if($change!==0) $locked->transactions()->create(['type'=>$change>0?'in':'out','quantity'=>abs($change),'reference'=>'Product edit adjustment','user_id'=>$request->user()->id]);
        });
        return to_route('products.index')->with('success','Product updated successfully.');
    }
    public function destroy(Product $product) {
        $product->delete();
        return to_route('products.index')->with('success','Product deleted successfully.');
    }
}
