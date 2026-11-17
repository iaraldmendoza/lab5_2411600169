@extends('layouts.inventory')
@section('title','Movement '.$transaction->id)
@section('content')
<div class="page-heading"><div><div class="eyebrow">Movement #{{ $transaction->id }}</div><h1>{{ $transaction->type_label }}</h1><p class="muted">{{ $transaction->created_at->format('F d, Y H:i:s') }}</p></div><div class="actions"><a class="button secondary" href="{{ route('transactions.index') }}">Back to movements</a>
@can('manage-inventory')@if(!$transaction->voided_at && !$transaction->reversal_of_id && !$transaction->product->trashed())<form method="POST" action="{{ route('transactions.destroy',$transaction) }}" onsubmit="return confirm('Reverse this stock movement? The original audit record will be retained.');">@csrf @method('DELETE')<button class="button danger">Reverse movement</button></form>@endif
@endcan</div></div>
@if($transaction->voided_at)<div class="notice">This movement was reversed. <a href="{{ route('transactions.show',$transaction->reversal) }}">View the compensating movement</a>.</div>@endif
@if($transaction->reversal_of_id)<div class="notice">Reverses <a href="{{ route('transactions.show',$transaction->reversal_of_id) }}">movement #{{ $transaction->reversal_of_id }}</a>.</div>@endif
<div class="two-col"><section class="panel"><h2>Movement details</h2><dl class="details">
<div><dt>Product</dt><dd>{{ $transaction->product->name }}</dd></div><div><dt>SKU</dt><dd>{{ $transaction->product->sku }}</dd></div>
<div><dt>Quantity change</dt><dd id="transaction-delta">{{ $transaction->delta>0?'+':'' }}{{ $transaction->delta }}</dd></div><div><dt>Resulting balance</dt><dd id="transaction-balance">{{ $transaction->balance_after }}</dd></div>
<div><dt>Previous balance</dt><dd>{{ $transaction->balance_before }}</dd></div><div><dt>Reference</dt><dd>{{ $transaction->reference ?? 'Not specified' }}</dd></div></dl><h3>Notes</h3><p>{{ $transaction->notes ?? 'No additional notes.' }}</p></section>
<section class="panel"><h2>Accountability</h2><p>Recorded by <strong>{{ $transaction->user?->name ?? 'Former employee' }}</strong></p><p class="muted">The signed change and running balance are retained as part of the stock audit trail.</p>@if(!$transaction->product->trashed())<a class="button" href="{{ route('products.show',$transaction->product) }}">View product history</a>@else<p>Product archived. Historical record preserved.</p>@endif</section></div>
@endsection
