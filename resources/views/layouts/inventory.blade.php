<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Inventory') | Facinal Hardware</title>
<link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}"><link rel="stylesheet" href="{{ asset('css/custom.css') }}">
@stack('head')</head><body>
<header class="site-nav">
<a class="brand" href="{{ route('dashboard') }}">FACINAL<span> / </span>HARDWARE</a>
<nav aria-label="Main navigation">
@foreach(['dashboard'=>'Dashboard','products.index'=>'Inventory','transactions.index'=>'Transactions','reports.inventory'=>'Reports'] as $route=>$label)
<a href="{{ route($route) }}" class="{{ request()->routeIs(explode('.',$route)[0].'*')?'active':'' }}">{{ $label }}</a>
@endforeach
</nav>
<div class="account">
<details class="notification-menu"><summary aria-label="Low stock notifications">🔔 Alerts <span id="notification-count">{{ $unreadCount }}</span></summary>
<div class="notification-popover"><h3>Recent alerts</h3>
@forelse($recentNotifications as $notice)<article>
<a href="{{ route('products.show',$notice->data['product_id']) }}">{{ $notice->data['name'] }}</a>
<p>{{ $notice->data['quantity'] }} available · Reorder at {{ $notice->data['reorder_level'] }}</p>
@if(!$notice->read_at)<form method="POST" action="{{ route('notifications.read',$notice->id) }}">@csrf<button class="small-button">Mark as read</button></form>@else<small>Read</small>@endif
</article>@empty<p>No stock alerts yet.</p>@endforelse
<p class="muted">Alerts arrive through the queue worker. Reload to see the latest messages.</p>
</div></details>
<details class="user-menu"><summary>{{ auth()->user()->name }}</summary><div class="notification-popover">
<p>{{ auth()->user()->isAdmin()?'Administrator':'Warehouse staff' }}</p><form method="POST" action="{{ route('logout') }}">@csrf<button class="button">Log out</button></form></div></details>
</div></header>
<main class="container">
@if(session('success'))<div class="notice" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>Please review the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
<footer class="footer">FACINAL HARDWARE / Laboratory Exercise 6 / Real Time Inventory Tracking</footer>
</main>@stack('scripts')</body></html>
