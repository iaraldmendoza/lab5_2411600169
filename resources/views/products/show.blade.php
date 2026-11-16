@extends('layouts.inventory')
@section('title',$product->name)
@section('content')
<div class="page-heading"><div><div class="eyebrow">{{ $product->category }} / {{ $product->sku }}</div><h1>{{ $product->name }}</h1>@include('partials.stock')</div><div class="actions"><a class="button" href="{{ route('products.edit',$product) }}">Edit product</a><a class="button secondary" href="{{ route('products.index') }}">Back to inventory</a><form method="POST" action="{{ route('products.destroy',$product) }}" onsubmit="return confirm('Delete this product and its stock history?');">@csrf @method('DELETE')<button class="button danger">Delete</button></form></div></div>
<div class="two-col"><section class="panel"><h2>Product information</h2><p>{{ $product->description ?: 'No description provided.' }}</p><dl class="details">
@foreach(['sku'=>'SKU','category'=>'Category','quantity'=>'Quantity','reorder_level'=>'Reorder level','supplier'=>'Supplier'] as $field=>$label)<div><dt>{{ $label }}</dt><dd>{{ $product->$field ?? 'Not specified' }}</dd></div>@endforeach
<div><dt>Unit price</dt><dd>₱{{ number_format($product->unit_price,2) }}</dd></div><div><dt>Stock value</dt><dd>₱{{ number_format($product->quantity*$product->unit_price,2) }}</dd></div><div><dt>Last updated</dt><dd>{{ $product->updated_at->format('M d, Y H:i') }}</dd></div></dl></section>
<section class="panel"><h2>Recent stock movements</h2><p class="muted">Latest 20 records. Quantity edits are recorded automatically.</p><div class="table-wrap"><table><thead><tr><th>Date</th><th>Movement</th><th>Reference / User</th></tr></thead><tbody>
@forelse($product->transactions as $transaction)<tr><td>{{ $transaction->created_at->format('M d H:i') }}</td><td>{{ $transaction->type==='in'?'+':'−' }}{{ $transaction->quantity }}</td><td>{{ $transaction->reference }}<br><small>{{ $transaction->user?->name ?? 'Seeder' }}</small></td></tr>@empty<tr><td colspan="3">No stock movements.</td></tr>@endforelse
</tbody></table></div></section></div>
@endsection
