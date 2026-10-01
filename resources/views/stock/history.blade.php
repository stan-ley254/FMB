@extends('layouts.app')

@section('title', 'Stock history')

@section('content')
<section class="mx-auto max-w-6xl px-1 py-8 sm:px-0">
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">INVENTORY</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Stock history</h1>
            <p class="mt-2 text-sm text-slate-500">Purchases and manual usage records for stored units.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('stock.history') }}" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
        <div>
            <label for="item" class="mb-1 block text-sm font-medium text-slate-700">Item</label>
            <select id="item" name="item" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All items</option>
                <optgroup label="Materials">
                    @foreach ($materials as $material)
                        <option value="material:{{ $material->id }}" @selected(($filters['item'] ?? null) === 'material:'.$material->id)>{{ $material->name }}{{ $material->is_active ? '' : ' (Inactive)' }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Ink">
                    @foreach ($inkStocks as $inkStock)
                        <option value="ink:{{ $inkStock->id }}" @selected(($filters['item'] ?? null) === 'ink:'.$inkStock->id)>{{ str($inkStock->machine)->replace('_', ' ')->title() }} {{ str($inkStock->color)->title() }}{{ $inkStock->is_active ? '' : ' (Inactive)' }}</option>
                    @endforeach
                </optgroup>
            </select>
        </div>
        <div>
            <label for="type" class="mb-1 block text-sm font-medium text-slate-700">Type</label>
            <select id="type" name="type" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All types</option>
                <option value="purchase" @selected(($filters['type'] ?? null) === 'purchase')>Purchase</option>
                <option value="usage" @selected(($filters['type'] ?? null) === 'usage')>Usage</option>
            </select>
        </div>
        <div>
            <label for="from" class="mb-1 block text-sm font-medium text-slate-700">From</label>
            <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="to" class="mb-1 block text-sm font-medium text-slate-700">To</label>
            <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div class="flex gap-2 md:col-span-4">
            <button class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Filter</button>
            <a href="{{ route('stock.history') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
        </div>
    </form>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Date / time</th>
                        <th class="px-5 py-3 font-semibold">Item</th>
                        <th class="px-5 py-3 font-semibold">Type</th>
                        <th class="px-5 py-3 font-semibold">Quantity</th>
                        <th class="px-5 py-3 font-semibold">Days since previous use</th>
                        <th class="px-5 py-3 font-semibold">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movements as $movement)
                        @php($itemName = $movement->material?->name ?? ($movement->inkStock ? str($movement->inkStock->machine)->replace('_', ' ')->title().' '.str($movement->inkStock->color)->title() : 'Stock item'))
                        @php($isInactiveItem = ($movement->material && ! $movement->material->is_active) || ($movement->inkStock && ! $movement->inkStock->is_active))
                        <tr>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $movement->created_at->format('M j, Y H:i') }}</td>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $itemName }} @if ($isInactiveItem)<span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">Inactive</span>@endif</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $movement->type === 'usage' ? 'bg-orange-100 text-orange-800' : 'bg-emerald-100 text-emerald-800' }}">{{ str($movement->type)->title() }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ number_format((float) $movement->quantity, 0) }}</td>
                            <td class="px-5 py-4 text-slate-600">
                                @if ($movement->type !== 'usage' || ! $movement->previous_usage_at)
                                    —
                                @else
                                    {{ $movement->previous_usage_at->diffInDays($movement->created_at) }} days
                                @endif
                            </td>
                            <td class="max-w-xs px-5 py-4 text-slate-600">{{ $movement->notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No stock movements match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movements->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $movements->links() }}</div>
        @endif
    </section>
</section>
@endsection
