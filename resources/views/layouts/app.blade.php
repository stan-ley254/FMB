<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'PrintDesk') · {{ config('app.name', 'Print Shop Manager') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('home') }}">
            <span class="brand-mark">P</span>
            <span>Print<span class="brand-accent">Desk</span><small>PRINT SHOP MANAGER</small></span>
        </a>
        <div class="nav-label">WORKSPACE</div>
        <nav aria-label="Main navigation">
            <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}"><span class="nav-icon">⌂</span> Overview</a>
            <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}"><span class="nav-icon">▤</span> Orders</a>
            <a class="nav-link {{ request()->routeIs('quick-sales.*') ? 'active' : '' }}" href="{{ route('quick-sales.create') }}"><span class="nav-icon">＋</span> Quick Sale</a>
            <a class="nav-link {{ request()->routeIs('catalog.*') ? 'active' : '' }}" href="{{ route('catalog.index') }}"><span class="nav-icon">☷</span> Price list</a>
            <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><span class="nav-icon">♧</span> Customers</a>
            <a class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}" href="{{ route('stock.index') }}"><span class="nav-icon">▦</span> Materials &amp; ink</a>
            <a class="nav-link {{ request()->routeIs('stock.history') ? 'active' : '' }}" href="{{ route('stock.history') }}"><span class="nav-icon">◷</span> Stock history</a>
            <a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}"><span class="nav-icon">◫</span> Sales</a>
            <a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><span class="nav-icon">¤</span> Expenses</a>
        </nav>
        <div class="sidebar-footer">
            <span class="status-dot"></span>
            <span>Workspace ready<small>Single-user setup</small></span>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <span>Workspace <span class="breadcrumb-divider">/</span> @yield('title', 'Overview')</span>
            <span class="machine-tag"><span class="status-dot"></span> Print shop</span>
        </header>
        @if (session('success'))
            <div class="mx-auto mt-5 max-w-6xl rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mx-auto mt-5 max-w-6xl rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <p class="font-semibold">Please review the highlighted fields.</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
        <footer class="page-footer">PRINTDESK <span>·</span> PRINT SHOP MANAGEMENT</footer>
    </main>
</div>
@stack('scripts')
</body>
</html>
