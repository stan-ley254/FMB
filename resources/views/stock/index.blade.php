@extends('layouts.app')

@section('title', 'Materials & Ink Stock')

@section('content')
<section class="mx-auto max-w-6xl px-1 py-8 sm:px-0">
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">INVENTORY</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Materials &amp; Ink Stock</h1>
            <p class="mt-2 text-sm text-slate-500">Current quantities and stock additions for both machines.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('stock.materials.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Add New Material</a>
            @if ($hasAvailableInkCombination)
                <a href="{{ route('stock.inks.create') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Add Ink Stock</a>
            @endif
        </div>
    </div>

    <div class="space-y-7">
        @foreach ($categories as $category => $label)
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-800">{{ $label }}</h2>
                </header>
                @php($materials = $materialsByCategory->get($category, collect()))
                @if ($materials->isEmpty())
                    <p class="px-5 py-6 text-sm text-slate-500">No materials in this category.</p>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($materials as $material)
                            @php($threshold = $material->name === 'TPU Powder' ? 2 : 10)
                            @php($isLow = (float) $material->quantity_remaining < $threshold)
                            <div class="grid gap-4 px-5 py-4 md:grid-cols-[minmax(10rem,1fr)_8rem_minmax(20rem,1.4fr)_auto] md:items-center {{ $material->is_active ? '' : 'bg-slate-50 opacity-70' }}">
                                <div>
                                    <p class="font-medium text-slate-800">
                                        {{ $material->name }}
                                        @unless ($material->is_active)
                                            <span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">Inactive</span>
                                        @endunless
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">{{ str($material->machine ?? 'shared')->replace('_', ' ')->title() }}</p>
                                    <div class="mt-2 space-y-0.5 text-xs text-slate-500">
                                        <p>
                                            Last used:
                                            @if ($material->last_used_at)
                                                {{ \Illuminate\Support\Carbon::parse($material->last_used_at)->format('M j, Y') }}
                                                ({{ \Illuminate\Support\Carbon::parse($material->last_used_at)->diffInDays(now()) }} days ago)
                                            @else
                                                Never used
                                            @endif
                                        </p>
                                        <p>Last restocked: {{ $material->last_restocked_at ? \Illuminate\Support\Carbon::parse($material->last_restocked_at)->format('M j, Y') : '—' }}</p>
                                        <a class="font-medium text-blue-700 hover:underline" href="{{ route('stock.history', ['item' => 'material:'.$material->id]) }}">View history</a>
                                        <div class="flex flex-wrap items-center gap-3 pt-1">
                                            <a class="font-medium text-blue-700 hover:underline" href="{{ route('stock.materials.edit', $material) }}">Edit</a>
                                            @if ($material->is_active && ($material->stock_movements_exists || $material->order_items_exists || $material->expenses_exists))
                                                <form method="POST" action="{{ route('stock.materials.destroy', $material) }}" onsubmit="return confirm('Deactivate this material? It has historical references.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="font-medium text-red-700 hover:underline">Deactivate</button>
                                                </form>
                                            @elseif (! $material->stock_movements_exists && ! $material->order_items_exists && ! $material->expenses_exists)
                                                <form method="POST" action="{{ route('stock.materials.destroy', $material) }}" onsubmit="return confirm('Permanently delete this material?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="font-medium text-red-700 hover:underline">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">Remaining</p>
                                    <p class="mt-1 font-semibold {{ $isLow ? 'text-orange-700' : 'text-slate-800' }}">
                                        {{ number_format((float) $material->quantity_remaining, 0) }} {{ $material->unit }}
                                        @if ($isLow)
                                            <span class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800">Low</span>
                                        @endif
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('stock.materials.add', $material) }}" class="grid gap-2 sm:grid-cols-[7rem_minmax(8rem,1fr)_auto]">
                                    @csrf
                                    <label class="sr-only" for="material-quantity-{{ $material->id }}">Quantity to add</label>
                                    <input id="material-quantity-{{ $material->id }}" name="quantity" type="number" min="1" step="1" required placeholder="Units to add" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <label class="sr-only" for="material-notes-{{ $material->id }}">Optional stock note</label>
                                    <input id="material-notes-{{ $material->id }}" name="notes" type="text" maxlength="2000" placeholder="Optional note" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <button class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add stock</button>
                                </form>
                                <form method="POST" action="{{ route('stock.materials.use', $material) }}">
                                    @csrf
                                    <button class="rounded-md border border-orange-300 px-3 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50 disabled:cursor-not-allowed disabled:opacity-50" {{ (float) $material->quantity_remaining < 1 ? 'disabled' : '' }}>Use 1</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <header class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-800">Ink stock</h2>
                <p class="mt-1 text-sm text-slate-500">Ink quantities are tracked separately for each printer.</p>
            </header>
            <div class="divide-y divide-slate-100">
                @foreach (['large_format' => 'Large Format (I3200)', 'dtf' => 'DTF Printer'] as $machine => $label)
                    <div class="px-5 py-5">
                        <h3 class="mb-3 text-sm font-semibold text-slate-700">{{ $label }}</h3>
                        <div class="divide-y divide-slate-100">
                            @foreach ($inkStocksByMachine->get($machine, collect()) as $inkStock)
                                @php($isLow = (float) $inkStock->quantity_remaining < 1)
                                <div class="grid gap-4 py-4 md:grid-cols-[minmax(10rem,1fr)_8rem_minmax(20rem,1.4fr)_auto] md:items-center {{ $inkStock->is_active ? '' : 'bg-slate-50 opacity-70' }}">
                                    <div>
                                        <p class="font-medium text-slate-800">
                                            {{ $label }} {{ str($inkStock->color)->title() }}
                                            @unless ($inkStock->is_active)
                                                <span class="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600">Inactive</span>
                                            @endunless
                                        </p>
                                        <div class="mt-2 space-y-0.5 text-xs text-slate-500">
                                            <p>
                                                Last used:
                                                @if ($inkStock->last_used_at)
                                                    {{ \Illuminate\Support\Carbon::parse($inkStock->last_used_at)->format('M j, Y') }}
                                                    ({{ \Illuminate\Support\Carbon::parse($inkStock->last_used_at)->diffInDays(now()) }} days ago)
                                                @else
                                                    Never used
                                                @endif
                                            </p>
                                            <p>Last restocked: {{ $inkStock->last_restocked_at ? \Illuminate\Support\Carbon::parse($inkStock->last_restocked_at)->format('M j, Y') : '—' }}</p>
                                            <a class="font-medium text-blue-700 hover:underline" href="{{ route('stock.history', ['item' => 'ink:'.$inkStock->id]) }}">View history</a>
                                            <div class="flex flex-wrap items-center gap-3 pt-1">
                                                <a class="font-medium text-blue-700 hover:underline" href="{{ route('stock.inks.edit', $inkStock) }}">Edit</a>
                                                @if ($inkStock->is_active && ($inkStock->stock_movements_exists || $inkStock->expenses_exists))
                                                    <form method="POST" action="{{ route('stock.inks.destroy', $inkStock) }}" onsubmit="return confirm('Deactivate this ink stock? It has historical references.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="font-medium text-red-700 hover:underline">Deactivate</button>
                                                    </form>
                                                @elseif (! $inkStock->stock_movements_exists && ! $inkStock->expenses_exists)
                                                    <form method="POST" action="{{ route('stock.inks.destroy', $inkStock) }}" onsubmit="return confirm('Permanently delete this ink stock?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="font-medium text-red-700 hover:underline">Delete</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <p class="font-semibold {{ $isLow ? 'text-orange-700' : 'text-slate-800' }}">
                                        {{ number_format((float) $inkStock->quantity_remaining, 0) }} {{ $inkStock->unit }}
                                        @if ($isLow)<span class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800">Low</span>@endif
                                    </p>
                                    <form method="POST" action="{{ route('stock.inks.add', $inkStock) }}" class="grid gap-2 sm:grid-cols-[7rem_minmax(8rem,1fr)_auto]">
                                        @csrf
                                        <label class="sr-only" for="ink-quantity-{{ $inkStock->id }}">Bottles to add</label>
                                        <input id="ink-quantity-{{ $inkStock->id }}" name="quantity" type="number" min="1" step="1" required placeholder="Bottles to add" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                        <label class="sr-only" for="ink-notes-{{ $inkStock->id }}">Optional stock note</label>
                                        <input id="ink-notes-{{ $inkStock->id }}" name="notes" type="text" maxlength="2000" placeholder="Optional note" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                        <button class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add stock</button>
                                    </form>
                                    <form method="POST" action="{{ route('stock.inks.use', $inkStock) }}">
                                        @csrf
                                        <button class="rounded-md border border-orange-300 px-3 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50 disabled:cursor-not-allowed disabled:opacity-50" {{ (float) $inkStock->quantity_remaining < 1 ? 'disabled' : '' }}>Use 1</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</section>
@endsection
