@extends('layouts.app')

@section('title', 'Add ink stock')

@section('content')
<section class="mx-auto max-w-2xl px-1 py-8 sm:px-0">
    <div class="mb-6">
        <a href="{{ route('stock.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Materials &amp; Ink Stock</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Add Ink Stock</h1>
    </div>
    @if ($combinations === [])
        <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm">All machine and color combinations are already configured.</div>
    @else
        <form method="POST" action="{{ route('stock.inks.store') }}" class="space-y-5">
            @csrf
            <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <label for="combination" class="mb-1 block text-sm font-medium text-slate-700">Machine and color</label>
                    <select id="combination" name="combination" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Choose an unconfigured combination</option>
                        @foreach ($combinations as $value => $label)
                            <option value="{{ $value }}" @selected(old('combination') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="starting_quantity" class="mb-1 block text-sm font-medium text-slate-700">Starting quantity (bottles)</label>
                    <input id="starting_quantity" name="starting_quantity" type="number" min="0" max="999999" step="1" required value="{{ old('starting_quantity', 0) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">A non-zero starting quantity is recorded as a purchase movement.</p>
                </div>
            </section>
            <div class="flex justify-between gap-3">
                <a href="{{ route('stock.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
                <button class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Create ink stock</button>
            </div>
        </form>
    @endif
</section>
@endsection
