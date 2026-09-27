@extends('layouts.app')

@section('title', 'Overview')

@section('content')
<section class="welcome-panel">
    <div class="eyebrow">YOUR PRINT SHOP, IN ONE PLACE</div>
    <h1>Welcome to <span>PrintDesk</span></h1>
    <p>Your workspace is ready for large-format and DTF printing operations.</p>
    <div class="machine-cards">
        <a class="machine-card" href="{{ route('stock.index') }}">
            <div class="machine-icon blue-icon">LF</div>
            <div><div class="card-label">LARGE FORMAT</div><h2>I3200 Printer</h2><p>Banners, sertine &amp; stickers</p></div>
            <span class="card-arrow">→</span>
        </a>
        <a class="machine-card" href="{{ route('stock.index') }}">
            <div class="machine-icon pink-icon">DTF</div>
            <div><div class="card-label">GARMENT PRINTING</div><h2>DTF Printer</h2><p>Film, powder &amp; heat-pressed garments</p></div>
            <span class="card-arrow">→</span>
        </a>
    </div>
    <div class="mt-8 flex flex-wrap gap-3">
        <a class="rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700" href="{{ route('orders.create') }}">Create an order</a>
        <a class="rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:border-blue-300" href="{{ route('stock.index') }}">Manage stock</a>
        <a class="rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:border-blue-300" href="{{ route('customers.index') }}">View customers</a>
    </div>
</section>
@endsection
