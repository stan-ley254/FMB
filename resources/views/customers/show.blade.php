@extends('layouts.app')

@section('title', $customer->name)

@section('content')
<section class="mx-auto max-w-6xl px-1 py-8 sm:px-0">
    <div class="mb-7 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('customers.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Customers</a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $customer->name }}</h1>
                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $customer->is_walk_in ? 'bg-pink-50 text-pink-700' : 'bg-blue-50 text-blue-700' }}">{{ $customer->is_walk_in ? 'Walk-in' : 'Customer' }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-500">{{ $customer->phone ?: 'No phone' }}@if ($customer->email) · {{ $customer->email }}@endif</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('customers.edit', $customer) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
            <a href="{{ route('orders.create', ['customer_id' => $customer->id]) }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">New order</a>
        </div>
    </div>

    @if ($customer->notes)
        <div class="mb-6 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">{{ $customer->notes }}</div>
    @endif

    <div class="mb-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($itemTotals as $itemTotal)
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $itemTotal['label'] }} ordered</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($itemTotal['quantity'], $itemTotal['unit'] === 'piece' ? 0 : 3) }} <span class="text-base font-medium text-slate-500">{{ $itemTotal['unit'] === 'piece' ? 'pieces' : 'meters' }}</span></p>
            </article>
        @endforeach
    </div>

    <section class="mb-7 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <header class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-800">Order history</h2></header>
        <div class="divide-y divide-slate-100">
            @forelse ($customer->orders as $order)
                <a href="{{ route('orders.show', $order) }}" class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-slate-50">
                    <div><p class="font-medium text-slate-800">Order #{{ $order->id }} <span class="ml-2 rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-600">{{ str($order->status)->replace('_', ' ')->title() }}</span></p>
                        <p class="mt-1 text-xs text-slate-500">{{ $order->created_at->format('M j, Y') }} · {{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('item', $order->items->count()) }}</p></div>
                    <p class="font-semibold text-slate-800">{{ number_format((float) $order->total_amount, 2) }}</p>
                </a>
            @empty
                <p class="px-5 py-8 text-sm text-slate-500">No orders for this customer yet.</p>
            @endforelse
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <header class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-800">Designs &amp; production proofs</h2></header>
        <div class="divide-y divide-slate-100">
            @php($artworkCount = 0)
            @foreach ($customer->orders as $order)
                @foreach ($order->items as $item)
                    @foreach ($item->artworks as $artwork)
                        @php($artworkCount++)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div><p class="font-medium text-slate-800">{{ $item->catalog_item_name ?? $item->material?->name ?? str($item->item_type)->replace('_', ' ')->title() }} · {{ str($artwork->purpose)->title() }}</p>
                                <p class="mt-1 text-xs text-slate-500">Order #{{ $order->id }} · {{ $artwork->uploaded_at->format('M j, Y') }}</p></div>
                            <a href="{{ route('artworks.download', $artwork) }}" class="font-medium text-blue-700 hover:text-blue-900">Download {{ strtoupper($artwork->file_type) }}</a>
                        </div>
                    @endforeach
                @endforeach
            @endforeach
            @if ($artworkCount === 0)<p class="px-5 py-8 text-sm text-slate-500">No designs or proofs have been uploaded yet.</p>@endif
        </div>
    </section>
</section>
@endsection
