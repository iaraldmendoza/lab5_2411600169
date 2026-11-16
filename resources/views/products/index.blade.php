@extends('layouts.inventory')
@section('title','Inventory')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Product catalog</div><h1>Hardware inventory</h1><p class="muted">Monitor materials, tools, and supplies in one place.</p></div><a class="button" href="{{ route('products.create') }}">+ Add product</a></div>
<section class="panel">
<form class="filters" method="GET">
<div class="field"><label for="q">Search products</label><input id="q" name="q" placeholder="Name or SKU" value="{{ request('q') }}"></div>
<div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option>@foreach(\App\Models\Product::CATEGORIES as $category)<option @selected(request('category')===$category)>{{ $category }}</option>@endforeach</select></div>
<div class="field"><label for="stock">Stock status</label><select id="stock" name="stock"><option value="">All stock</option><option value="low" @selected(request('stock')==='low')>Low stock including zero</option><option value="out" @selected(request('stock')==='out')>Out of stock</option></select></div>
<button class="button">Filter</button><a class="button secondary" href="{{ route('products.index') }}">Reset</a>
</form>
<p class="muted">{{ $products->total() }} products found</p>
<div class="table-wrap"><table><thead><tr><th>Product / SKU</th><th>Category</th><th>Quantity</th><th>Reorder</th><th>Unit price</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($products as $product)<tr><td><a href="{{ route('products.show',$product) }}">{{ $product->name }}</a><br><small class="muted">{{ $product->sku }}</small></td><td>{{ $product->category }}</td><td>{{ $product->quantity }}</td><td>{{ $product->reorder_level }}</td><td>₱{{ number_format($product->unit_price,2) }}</td><td>@include('partials.stock')</td><td><div class="actions"><a href="{{ route('products.show',$product) }}">View</a><a href="{{ route('products.edit',$product) }}">Edit</a><form method="POST" action="{{ route('products.destroy',$product) }}" onsubmit="return confirm('Delete this product and its stock history? This cannot be undone.');">@csrf @method('DELETE')<button class="text-button">Delete</button></form></div></td></tr>
@empty<tr><td colspan="7">No products match your filters.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $products->links('pagination::simple-bootstrap-5') }}</div>
</section>
@endsection
