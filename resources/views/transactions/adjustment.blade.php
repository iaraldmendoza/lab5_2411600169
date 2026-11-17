@extends('layouts.inventory')
@section('title','Reconcile physical count')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Administrator operation</div><h1>Reconcile physical count</h1><p class="muted">Enter the counted quantity. The system records only the difference.</p></div><a href="{{ route('transactions.index') }}">← Back to movements</a></div>
<section class="panel form-panel"><form method="POST" action="{{ route('adjustments.store') }}" id="movement-form">@csrf
<div class="form-grid"><div class="field full"><label for="product_id">Product *</label><select id="product_id" name="product_id" required><option value="">Choose a product</option>@foreach($products as $p)<option value="{{ $p->id }}" @selected(old('product_id')==$p->id)>{{ $p->name }} · {{ $p->sku }}</option>@endforeach</select></div>
<div class="stock-readout full">System quantity: <strong id="current-stock" aria-live="polite">Choose a product</strong></div>
<div class="field"><label for="physical_count">Physical count *</label><input id="physical_count" name="physical_count" type="number" min="0" step="1" value="{{ old('physical_count') }}" required></div>
<div class="field"><label for="reason">Reason *</label><select id="reason" name="reason" required>@foreach(['Physical count','Damaged goods','Loss','Correction'] as $reason)<option @selected(old('reason')===$reason)>{{ $reason }}</option>@endforeach</select></div>
<div class="field full"><label for="notes">Notes</label><textarea id="notes" name="notes" maxlength="2000" rows="3">{{ old('notes') }}</textarea></div></div>
<p id="stock-feedback" role="status" class="muted">Choose a product and enter the physical count.</p><div class="actions"><button class="button" id="save-movement">Save adjustment</button><a class="button secondary" href="{{ route('transactions.index') }}">Cancel</a></div></form></section>
@endsection
@push('scripts')<script src="{{ asset('js/stock-form.js') }}" defer></script>@endpush
