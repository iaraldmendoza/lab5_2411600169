@extends('layouts.inventory')
@section('title','Record stock movement')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Stock operations</div><h1>Record stock movement</h1><p class="muted">Receive deliveries or issue stock. The balance updates automatically.</p></div><a href="{{ route('transactions.index') }}">← Back to movements</a></div>
<section class="panel form-panel"><form method="POST" action="{{ route('transactions.store') }}" id="movement-form">@csrf
<div class="form-grid"><div class="field full"><label for="product_id">Product *</label><select id="product_id" name="product_id" required><option value="">Choose a product</option>@foreach($products as $p)<option value="{{ $p->id }}" @selected(old('product_id')==$p->id)>{{ $p->name }} · {{ $p->sku }}</option>@endforeach</select></div>
<div class="stock-readout full">Available stock: <strong id="current-stock" aria-live="polite">Choose a product</strong></div>
<div class="field"><label for="type">Movement type *</label><select id="type" name="type"><option value="in" @selected(old('type')==='in')>Stock In · Delivery or return</option><option value="out" @selected(old('type')==='out')>Stock Out · Sale or issue</option></select></div>
<div class="field"><label for="quantity">Quantity *</label><input id="quantity" type="number" name="quantity" min="1" step="1" value="{{ old('quantity',1) }}" required></div>
<div class="field full"><label for="reference">Reference number</label><input id="reference" name="reference" maxlength="100" value="{{ old('reference') }}" placeholder="PO-2026-0142 or SO-2026-0298"></div>
<div class="field full"><label for="notes">Notes</label><textarea id="notes" name="notes" rows="3" maxlength="2000">{{ old('notes') }}</textarea></div></div>
<p id="stock-feedback" class="muted" role="status">Stock is checked again when you save.</p>
<div class="actions"><button class="button" id="save-movement">Record movement</button><a class="button secondary" href="{{ route('transactions.index') }}">Cancel</a></div></form></section>
@endsection
@push('scripts')<script src="{{ asset('js/stock-form.js') }}" defer></script>@endpush
