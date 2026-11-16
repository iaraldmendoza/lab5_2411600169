@extends('layouts.inventory')
@section('title','Inventory report')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Inventory report</div><h1>Stock and valuation</h1><p class="muted">Generated {{ now()->format('M d, Y H:i') }} · {{ $products->count() }} products · Value ₱{{ number_format($inventoryValue,2) }}</p></div><div class="actions no-print"><a class="button" href="{{ route('reports.csv') }}">Download CSV</a><button class="button secondary" onclick="window.print()">Print report</button></div></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Qty</th><th>Reorder</th><th>Unit price</th><th>Stock value</th><th>Status</th></tr></thead><tbody>
@foreach($products as $product)<tr><td>{{ $product->name }}</td><td>{{ $product->sku }}</td><td>{{ $product->category }}</td><td>{{ $product->quantity }}</td><td>{{ $product->reorder_level }}</td><td>₱{{ number_format($product->unit_price,2) }}</td><td>₱{{ number_format($product->quantity*$product->unit_price,2) }}</td><td>@include('partials.stock')</td></tr>@endforeach
</tbody><tfoot><tr><td colspan="6"><strong>Total inventory value</strong></td><td colspan="2"><strong>₱{{ number_format($inventoryValue,2) }}</strong></td></tr></tfoot></table></div></section>
@endsection
