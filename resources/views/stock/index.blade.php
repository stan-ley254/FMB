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
                            <div class="grid gap-4 px-5 py-4 md:grid-cols-[minmax(10rem,1fr)_8rem_minmax(20rem,1.4fr)] md:items-center">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $material->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ str($material->machine ?? 'shared')->replace('_', ' ')->title() }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">Remaining</p>
                                    <p class="mt-1 font-semibold {{ $isLow ? 'text-orange-700' : 'text-slate-800' }}">
                                        {{ number_format((float) $material->quantity_remaining, 3) }} {{ $material->unit }}
                                        @if ($isLow)
                                            <span class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800">Low</span>
                                        @endif
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('stock.materials.add', $material) }}" class="grid gap-2 sm:grid-cols-[7rem_minmax(8rem,1fr)_auto]">
                                    @csrf
                                    <label class="sr-only" for="material-quantity-{{ $material->id }}">Quantity to add</label>
                                    <input id="material-quantity-{{ $material->id }}" name="quantity" type="number" min="0.001" step="0.001" required placeholder="Qty to add" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <label class="sr-only" for="material-notes-{{ $material->id }}">Optional stock note</label>
                                    <input id="material-notes-{{ $material->id }}" name="notes" type="text" maxlength="2000" placeholder="Optional note" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                    <button class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add stock</button>
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
                                <div class="grid gap-4 py-4 md:grid-cols-[minmax(10rem,1fr)_8rem_minmax(20rem,1.4fr)] md:items-center">
                                    <p class="font-medium text-slate-800">{{ $label }} {{ str($inkStock->color)->title() }}</p>
                                    <p class="font-semibold {{ $isLow ? 'text-orange-700' : 'text-slate-800' }}">
                                        {{ number_format((float) $inkStock->quantity_remaining, 3) }} kg
                                        @if ($isLow)<span class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800">Low</span>@endif
                                    </p>
                                    <form method="POST" action="{{ route('stock.inks.add', $inkStock) }}" class="grid gap-2 sm:grid-cols-[7rem_minmax(8rem,1fr)_auto]">
                                        @csrf
                                        <label class="sr-only" for="ink-quantity-{{ $inkStock->id }}">Quantity to add in kg</label>
                                        <input id="ink-quantity-{{ $inkStock->id }}" name="quantity" type="number" min="0.001" step="0.001" required placeholder="Kg to add" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                        <label class="sr-only" for="ink-notes-{{ $inkStock->id }}">Optional stock note</label>
                                        <input id="ink-notes-{{ $inkStock->id }}" name="notes" type="text" maxlength="2000" placeholder="Optional note" class="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                        <button class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Add stock</button>
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
