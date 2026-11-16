<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Inventory') | Facinal Hardware</title>
<link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('css/custom.css') }}" rel="stylesheet">
</head>
<body>
<header class="site-nav">
<a class="brand" href="{{ route('dashboard') }}">FACINAL<span> / </span>HARDWARE</a>
<nav aria-label="Main navigation">
<a class="{{ request()->routeIs('dashboard')?'active':'' }}" href="{{ route('dashboard') }}">Dashboard</a>
<a class="{{ request()->routeIs('products.*')?'active':'' }}" href="{{ route('products.index') }}">Inventory</a>
<a class="{{ request()->routeIs('reports.*')?'active':'' }}" href="{{ route('reports.inventory') }}">Reports</a>
</nav>
<div class="account"><span>{{ auth()->user()->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">Log out</button></form></div>
</header>
<main class="container">
@if(session('success'))<div role="status" class="notice">{{ session('success') }}</div>@endif
@yield('content')
<footer class="footer">FACINAL HARDWARE &nbsp; / &nbsp; Laboratory Exercise 5 &nbsp; / &nbsp; Laravel MVC Inventory System</footer>
</main>
</body></html>
