@extends('layouts.app')

@section('title', 'Edit ink stock')

@section('content')
<section class="mx-auto max-w-2xl px-1 py-8 sm:px-0">
    <div class="mb-6">
        <a href="{{ route('stock.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Materials &amp; Ink Stock</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Edit {{ str($inkStock->machine)->replace('_', ' ')->title() }} {{ str($inkStock->color)->title() }}</h1>
    </div>
    <form method="POST" action="{{ route('stock.inks.update', $inkStock) }}" class="space-y-5">
        @csrf
        @method('PATCH')
        <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <p class="mb-1 text-sm font-medium text-slate-700">Machine and color</p>
                <p class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ str($inkStock->machine)->replace('_', ' ')->title() }} · {{ str($inkStock->color)->title() }}</p>
                <p class="mt-1 text-xs text-slate-500">Machine and color are fixed so existing stock history keeps its original identity.</p>
            </div>
            <div>
                <label for="quantity_remaining" class="mb-1 block text-sm font-medium text-slate-700">Quantity remaining</label>
                <input id="quantity_remaining" name="quantity_remaining" type="number" min="0" max="999999" step="1" required value="{{ old('quantity_remaining', $inkStock->quantity_remaining) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Changing quantity records the signed correction in stock history.</p>
            </div>
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $inkStock->is_active)) class="size-4 rounded border-slate-300 text-blue-600">
                Active and available for new expenses
            </label>
        </section>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('stock.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
            <div class="flex flex-wrap gap-2">
                @if ($canDelete)
                    <button type="submit" form="delete-ink-form" class="rounded-lg border border-red-300 px-4 py-2.5 text-sm font-semibold text-red-700">Delete ink stock</button>
                @else
                    <p class="self-center text-xs text-slate-500">Has historical references; deactivate instead of deleting.</p>
                @endif
                <button class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Save changes</button>
            </div>
        </div>
    </form>
    @if ($canDelete)
        <form id="delete-ink-form" method="POST" action="{{ route('stock.inks.destroy', $inkStock) }}" onsubmit="return confirm('Permanently delete this ink stock?');">
            @csrf
            @method('DELETE')
        </form>
    @endif
</section>
@endsection
