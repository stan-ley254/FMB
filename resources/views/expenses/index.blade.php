@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
<section class="mx-auto max-w-7xl px-1 py-8 sm:px-0">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">FINANCE</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Expenses</h1>
            <p class="mt-2 text-sm text-slate-500">Purchases, payments, and inventory restocks.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('expenses.export', $filters) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download CSV</a>
            <a href="{{ route('expenses.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Record expense</a>
        </div>
    </div>

    <form method="GET" action="{{ route('expenses.index') }}" class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
        <div><label for="from" class="mb-1 block text-xs font-medium text-slate-600">From date</label><input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        <div><label for="to" class="mb-1 block text-xs font-medium text-slate-600">To date</label><input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        <div>
            <label for="category" class="mb-1 block text-xs font-medium text-slate-600">Category</label>
            <select id="category" name="category" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All categories</option>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div><label for="supplier" class="mb-1 block text-xs font-medium text-slate-600">Supplier</label><input id="supplier" name="supplier" type="search" value="{{ $filters['supplier'] ?? '' }}" placeholder="Search supplier" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        <div class="flex items-end gap-2">
            <button class="flex-1 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Filter</button>
            <a href="{{ route('expenses.index') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700">Clear</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Supplier</th><th class="px-5 py-3">Description</th><th class="px-5 py-3">Quantity / size</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Payment</th><th class="px-5 py-3">Stock</th><th class="px-5 py-3">Manage</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($expenses as $expense)
                        @php($stockItemName = $expense->relatedMaterial?->name ?? ($expense->relatedInkStock ? ($expense->relatedInkStock->machine === 'dtf' ? 'DTF Printer' : 'Large Format').' '.str($expense->relatedInkStock->color)->title() : null))
                        @php($stockItemKey = $expense->related_material_id ? 'material:'.$expense->related_material_id : ($expense->related_ink_stock_id ? 'ink:'.$expense->related_ink_stock_id : null))
                        <tr>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $expense->created_at->format('M j, Y') }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $categories[$expense->category] }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $expense->supplier_name ?: '—' }}</td>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $expense->item_description }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $expense->quantity_or_size ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-800">{{ number_format((float) $expense->amount, 2) }}</td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ str($expense->payment_method)->upper() }}
                                @if ($expense->payment_method === 'bank' && $expense->bank_name)
                                    <span class="block text-xs text-slate-500">{{ $expense->bank_name }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($expense->adds_to_stock && $stockItemKey)
                                    <a class="font-medium text-emerald-700 hover:underline" href="{{ route('stock.history', ['item' => $stockItemKey]) }}">Added: {{ $stockItemName }}</a>
                                @else
                                    <span class="text-slate-500">No</span>
                                @endif
                            </td>
                            <td class="px-5 py-4"><a class="font-medium text-blue-700 hover:text-blue-900" href="{{ route('expenses.edit', $expense) }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-10 text-center text-slate-500">No expenses match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $expenses->links() }}</div>@endif
    </div>
</section>
@endsection
