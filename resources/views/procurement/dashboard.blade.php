@extends('layouts.inventory')
@section('title', 'Procurement dashboard')
@section('content')
    <section class="hero"><div><div class="eyebrow">Procurement overview</div><h1>Keep the next delivery on track.</h1><p>Plan replenishment, manage approvals and follow supplier performance.</p></div>@can('create', \App\Models\PurchaseOrder::class)<a class="button" href="{{ route('purchase-orders.create') }}">Create purchase order</a>@endcan</section>
    <div class="page-heading"><p class="muted"><span id="active-suppliers">{{ $activeSuppliers }}</span> active suppliers · <span id="open-orders">{{ $openOrders }}</span> open orders · <span id="product-count">{{ $productCount }}</span> products</p><p class="muted">Updates every 30 seconds · <span id="procurement-poll-status" role="status">Connecting</span></p></div>
    <div class="procurement-grid">@include('widgets.pending-orders') @include('widgets.recent-orders') @include('widgets.supplier-performance') @include('widgets.reorder-alerts')</div>
@endsection
@push('scripts')<script src="{{ asset('js/procurement-dashboard.js') }}" defer></script>@endpush
