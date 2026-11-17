@extends('layouts.inventory')
@section('title','Live dashboard')
@section('content')
<div class="hero"><div><div class="eyebrow">Live inventory tracking</div><h1>Every movement accounted for.</h1><p>Receive stock, record issues, and monitor replenishment needs.</p></div><a class="button" href="{{ route('transactions.create') }}">+ Record movement</a></div>
<div class="live-status"><span><span class="live-dot"></span> Refreshes every 30 seconds · Last updated <time id="last-updated">{{ now()->format('H:i:s') }}</time></span><button id="refresh-inventory" class="small-button">Refresh now</button><span id="poll-status" role="status" aria-live="polite">Connected</span></div>
<div class="stats">
@foreach(['total-products'=>['Total products',$totalProducts],'low-stock-count'=>['Low stock alerts',$lowStock],'out-of-stock-count'=>['Out of stock',$outOfStock],'inventory-value'=>['Inventory value','₱'.number_format($inventoryValue,2)]] as $id=>$stat)
<div class="stat"><p>{{ $stat[0] }}</p><strong id="{{ $id }}">{{ $stat[1] }}</strong><small>{{ $id==='low-stock-count'?'Includes zero stock':($id==='inventory-value'?'Current quantity × unit price':'Current inventory') }}</small></div>
@endforeach
</div>
<div class="two-col"><section class="panel"><div class="page-heading"><h2>Recent stock movements</h2><a href="{{ route('transactions.index') }}">All movements →</a></div>
<div class="table-wrap"><table><thead><tr><th>Product</th><th>Movement</th><th>Change</th><th>Balance</th><th>Recorded by</th></tr></thead><tbody id="recent-movements">
@forelse($recentTransactions as $t)<tr><td><a href="{{ route('transactions.show',$t) }}">{{ $t->product->name }}</a></td><td>{{ $t->type_label }}</td><td>{{ $t->delta>0?'+':'' }}{{ $t->delta }}</td><td>{{ $t->balance_after }}</td><td>{{ $t->user?->name ?? 'Former employee' }}</td></tr>@empty<tr><td colspan="5">No movements recorded.</td></tr>@endforelse
</tbody></table></div></section>
<section class="panel"><h2>Reorder attention</h2><p class="muted">Products at or below their reorder level.</p><div id="low-stock-list">
@forelse($lowProducts as $p)<a class="stock-alert" href="{{ route('products.show',$p) }}"><strong>{{ $p->name }}</strong><span>{{ $p->quantity }} left · Reorder at {{ $p->reorder_level }}</span></a>@empty<p>No products need replenishment.</p>@endforelse
</div></section></div>
@endsection
@push('scripts')<script src="{{ asset('js/dashboard.js') }}" defer></script>@endpush
