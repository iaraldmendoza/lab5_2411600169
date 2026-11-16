@extends('layouts.inventory')
@section('title','Edit product')
@section('content')
<div class="page-heading"><div><div class="eyebrow">Product catalog</div><h1>Edit product</h1><p class="muted">Update details and record changes to stock automatically.</p></div><a href="{{ route('products.index') }}">← Back to inventory</a></div>
@include('products.form')
@endsection
