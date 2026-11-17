@extends('layouts.inventory')
@section('title','Add product')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Product catalog</div><h1>Add product</h1><p class="muted">Enter product details and opening stock.</p></div><a href="{{ route('products.index') }}">← Back to inventory</a></div>
@include('products.form')
@endsection
