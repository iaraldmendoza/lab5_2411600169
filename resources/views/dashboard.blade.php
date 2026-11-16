@extends('layouts.inventory')
@section('title','Dashboard')
@section('content')
<div class="hero"><div><div class="eyebrow">Inventory command center</div><h1>Built for a well-stocked store.</h1><p>Track materials, watch stock levels, and keep projects moving.</p></div><a class="button" href="{{ route('products.create') }}">+ Add product</a></div>
<div class="stats">
<div class="stat"><p>Total products</p><strong>{{ $totalProducts }}</strong><small>Across six hardware categories</small></div>
<div class="stat"><p>Low stock alerts</p><strong>{{ $lowStock }}</strong><small>At or below reorder level, including zero</small></div>
<div class="stat"><p>Out of stock</p><strong>{{ $outOfStock }}</strong><small>Products with zero units available</small></div>
<div class="stat"><p>Inventory value</p><strong>₱{{ number_format($inventoryValue,2) }}</strong><small>Quantity × unit price</small></div>
</div>
<div class="two-col">
<section class="panel"><div class="page-heading"><h2>Recently added products</h2><a href="{{ route('products.index') }}">View inventory →</a></div>
<div class="table-wrap"><table><thead><tr><th>Product</th><th>Category</th><th>Units</th><th>Status</th></tr></thead><tbody>
@forelse($recentProducts as $product)<tr><td><a href="{{ route('products.show',$product) }}">{{ $product->name }}</a><br><small class="muted">{{ $product->sku }}</small></td><td>{{ $product->category }}</td><td>{{ $product->quantity }}</td><td>@include('partials.stock')</td></tr>
@empty<tr><td colspan="4">No products yet. Add your first product.</td></tr>@endforelse
</tbody></table></div></section>
<section class="panel"><h2>Stock by category</h2><p class="muted">Available units from the database</p>
@php($maxUnits=max(1,$categories->max('units')??1))
@foreach($categories as $category)<div class="bar-row"><div class="bar-label"><span>{{ $category->category }}</span><strong>{{ $category->units }}</strong></div><div class="bar-track" role="img" aria-label="{{ $category->category }}: {{ $category->units }} units"><div class="bar" style="width:{{ $category->units/$maxUnits*100 }}%"></div></div></div>@endforeach
<a href="{{ route('products.index',['stock'=>'low']) }}">Review items needing reorder →</a>
</section></div>
@endsection
