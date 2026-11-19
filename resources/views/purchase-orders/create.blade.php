@extends('layouts.inventory')
@section('title', 'Create purchase order')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Purchasing</div><h1>Create purchase order</h1><p class="muted">Prepare a supplier order with clear quantities and agreed prices.</p></div><a href="{{ route('purchase-orders.index') }}">Back to purchase orders</a></div>
    @include('purchase-orders.form')
@endsection
