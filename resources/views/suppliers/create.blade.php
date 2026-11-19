@extends('layouts.inventory')
@section('title', 'Add supplier')
@section('content')
    <div class="page-heading"><div><div class="eyebrow">Supplier management</div><h1>Add supplier</h1><p class="muted">Keep supplier contact details and trading terms up to date.</p></div><a href="{{ route('suppliers.index') }}">Back to suppliers</a></div>
    @include('suppliers.form')
@endsection
