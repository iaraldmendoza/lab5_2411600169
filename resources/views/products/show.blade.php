@extends('layouts.inventory')
@section('title',$product->name)
@section('content')
<div class="page-heading"><div><div class="eyebrow">{{ $product->category }} / {{ $product->sku }}</div><h1>{{ $product->name }}</h1>@include('partials.stock')</div><div class="actions"><a class="button" href="{{ route('products.edit',$product) }}">Edit product</a><a class="button secondary" href="{{ route('products.index') }}">Back to inventory</a>@can('manage-inventory')<form method="POST" action="{{ route('products.destroy',$product) }}" onsubmit="return confirm('Archive this product? Stock history will be retained.');">@csrf @method('DELETE')<button class="button danger">Archive</button></form>@endcan</div></div>
<section class="panel"><h2>Product information</h2><p>{{ $product->description }}</p><dl class="details">
@foreach(['quantity'=>'Available units','reorder_level'=>'Reorder level','supplier'=>'Supplier'] as $field=>$label)<div><dt>{{ $label }}</dt><dd>{{ $product->$field ?? 'Not specified' }}</dd></div>@endforeach
<div><dt>Unit price</dt><dd>₱{{ number_format($product->unit_price,2) }}</dd></div><div><dt>Inventory value</dt><dd>₱{{ number_format($product->quantity*$product->unit_price,2) }}</dd></div></dl></section>
<section class="panel section-gap"><h2>Product transaction history</h2><form class="filters"><div class="field"><label for="from">From</label><input id="from" type="date" name="from" value="{{ request('from') }}"></div><div class="field"><label for="to">To</label><input id="to" type="date" name="to" value="{{ request('to') }}"></div><button class="button">Filter history</button></form>
@include('transactions.table')<div class="pagination">{{ $transactions->links('pagination::simple-bootstrap-5') }}</div></section>
@endsection
