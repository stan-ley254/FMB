@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<section class="mx-auto max-w-7xl px-1 py-8 sm:px-0">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">FINANCE</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Sales</h1>
            <p class="mt-2 text-sm text-slate-500">Completed orders and their recorded payments.</p>
        </div>
        <a href="{{ route('sales.export', $filters) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download CSV</a>
    </div>

    <form method="GET" action="{{ route('sales.index') }}" class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-6">
        <div><label for="from" class="mb-1 block text-xs font-medium text-slate-600">From date</label><input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        <div><label for="to" class="mb-1 block text-xs font-medium text-slate-600">To date</label><input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        <div><label for="customer" class="mb-1 block text-xs font-medium text-slate-600">Customer</label><input id="customer" name="customer" type="search" value="{{ $filters['customer'] ?? '' }}" placeholder="Search customer" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        <div><label for="material" class="mb-1 block text-xs font-medium text-slate-600">Material</label><input id="material" name="material" type="search" value="{{ $filters['material'] ?? '' }}" placeholder="Search material" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        <div>
            <label for="item_type" class="mb-1 block text-xs font-medium text-slate-600">Item type</label>
            <select id="item_type" name="item_type" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All types</option>
                @foreach (['banner', 'sertine', 'sticker', 'dtf_print', 'dtf_garment'] as $itemType)
                    <option value="{{ $itemType }}" @selected(($filters['item_type'] ?? '') === $itemType)>{{ $itemType === 'dtf_garment' ? 'DTF Garment' : str($itemType)->title() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button class="flex-1 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Filter</button>
            <a href="{{ route('sales.index') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700">Clear</a>
        </div>
    </form>

    <section class="mb-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="sales-usage-heading">
        <div class="mb-4">
            <h2 id="sales-usage-heading" class="text-base font-semibold text-slate-900">Filtered item usage</h2>
            <p class="mt-1 text-sm text-slate-500">Quantities are grouped from the historical sale snapshots shown below.</p>
        </div>
        @if ($usageSummary === [])
            <p class="text-sm text-slate-500">No item usage matches these filters.</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($usageSummary as $usageItem)
                    <article class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ match ($usageItem['item_type']) {
                                'dtf_garment' => 'DTF Garment',
                                'dtf_print' => 'DTF Print',
                                default => str($usageItem['item_type'])->replace('_', ' ')->title(),
                            } }}
                        </p>
                        <p class="mt-1 font-medium text-slate-800">{{ $usageItem['name'] }}</p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ rtrim(rtrim(number_format($usageItem['quantity'], 3, '.', ''), '0'), '.') }}
                            {{ $usageItem['unit'] === 'piece' ? 'pcs sold' : 'm sold' }}
                        </p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">Completed</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Items</th><th class="px-5 py-3">Total</th><th class="px-5 py-3">Paid</th><th class="px-5 py-3">Payment</th><th class="px-5 py-3">Order</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $sale->completed_at->format('M j, Y') }}</td>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $sale->customer->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $sale->itemsSummary() ?: 'No item details recorded' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-800">{{ number_format((float) $sale->total_amount, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-700">{{ number_format((float) $sale->amount_paid, 2) }}</td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $sale->payment_method ? str($sale->payment_method)->upper() : '—' }}
                                @if ($sale->payment_method === 'bank' && $sale->bank_name)
                                    <span class="block text-xs text-slate-500">{{ $sale->bank_name }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4"><a class="font-medium text-blue-700 hover:text-blue-900" href="{{ route('orders.show', $sale->order_id) }}">View order #{{ $sale->order_id }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No sales match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sales->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $sales->links() }}</div>@endif
    </div>
</section>
@endsection
