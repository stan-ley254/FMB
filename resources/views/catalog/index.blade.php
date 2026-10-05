@extends('layouts.app')

@section('title', 'Service & Item Catalog')

@section('content')
<section class="mx-auto max-w-7xl px-1 py-8 sm:px-0">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">PRICE LIST</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Service &amp; Item Catalog</h1>
            <p class="mt-2 text-sm text-slate-500">Reusable items and default prices for Quick Sale.</p>
        </div>
        <a href="{{ route('catalog.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Add catalog item</a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">Item</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Unit</th><th class="px-5 py-3">Default price</th><th class="px-5 py-3">Material</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Manage</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($catalogItems as $catalogItem)
                        <tr class="{{ $catalogItem->is_active ? '' : 'bg-slate-50 text-slate-500' }}">
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $catalogItem->name }}</td>
                            <td class="px-5 py-4">{{ $catalogItem->category ?? $itemTypes[$catalogItem->order_item_type] ?? str($catalogItem->order_item_type)->replace('_', ' ')->title() }}</td>
                            <td class="px-5 py-4">{{ str($catalogItem->unit)->title() }}</td>
                            <td class="px-5 py-4">{{ number_format((float) $catalogItem->default_unit_price, 2) }}</td>
                            <td class="px-5 py-4">{{ $catalogItem->material?->name ?? '—' }}</td>
                            <td class="px-5 py-4">{{ $catalogItem->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-5 py-4"><a href="{{ route('catalog.edit', $catalogItem) }}" class="font-medium text-blue-700 hover:underline">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No catalog items are configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($catalogItems->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $catalogItems->links() }}</div>
        @endif
    </div>
</section>
@endsection
