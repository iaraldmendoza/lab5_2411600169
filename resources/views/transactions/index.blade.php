@extends('layouts.inventory')
@section('title','Stock movements')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Inventory audit trail</div><h1>Stock movements</h1><p class="muted">Every receipt, issue, adjustment, and reversal stays accountable.</p></div><div class="actions">@can('manage-inventory')<a class="button secondary" href="{{ route('adjustments.create') }}">Reconcile count</a>@endcan<a class="button" href="{{ route('transactions.create') }}">+ Record movement</a></div></div>
<div class="actions section-gap"><a class="button secondary" href="{{ route('exports.download', ['type'=>'transactions','format'=>'csv']).'?'.http_build_query(request()->query()) }}">Export CSV</a><a class="button secondary" href="{{ route('exports.download', ['type'=>'transactions','format'=>'pdf']).'?'.http_build_query(request()->query()) }}">Download PDF</a></div><section class="panel">@include('transactions.filters')<p class="muted">{{ $transactions->total() }} movements found</p>@include('transactions.table')<div class="pagination">{{ $transactions->links('pagination::simple-bootstrap-5') }}</div></section>
@endsection
