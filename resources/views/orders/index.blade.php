@extends('layouts.app')

@section('title', 'Orders')

@section('content')
<section class="mx-auto max-w-6xl px-1 py-8 sm:px-0">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">JOB TRACKING</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Orders</h1>
        </div>
        <a href="{{ route('orders.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Create order</a>
    </div>

    <form method="GET" action="{{ route('orders.index') }}" class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-slate-600">Status</label>
            <select id="status" name="status" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">All statuses</option>
                @foreach (['pending', 'in_production', 'ready', 'completed'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        <div><label for="from" class="mb-1 block text-xs font-medium text-slate-600">From date</label><input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        <div><label for="to" class="mb-1 block text-xs font-medium text-slate-600">To date</label><input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        <div><label for="customer" class="mb-1 block text-xs font-medium text-slate-600">Customer</label><input id="customer" name="customer" type="search" value="{{ $filters['customer'] ?? '' }}" placeholder="Name or phone" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
        <div class="flex items-end gap-2">
            <button class="flex-1 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Filter</button>
            <a href="{{ route('orders.index') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700">Clear</a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">Order</th><th class="px-5 py-3">Customer</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Due date</th><th class="px-5 py-3">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-5 py-4 font-medium"><a class="text-blue-700 hover:text-blue-900" href="{{ route('orders.show', $order) }}">#{{ $order->id }}</a><p class="mt-1 text-xs font-normal text-slate-500">{{ $order->created_at->format('M j, Y') }}</p></td>
                            <td class="px-5 py-4 text-slate-700"><a class="hover:text-blue-700" href="{{ route('customers.show', $order->customer) }}">{{ $order->customer->name }}</a></td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $order->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($order->status === 'ready' ? 'bg-pink-50 text-pink-700' : 'bg-blue-50 text-blue-700') }}">{{ str($order->status)->replace('_', ' ')->title() }}</span></td>
                            <td class="px-5 py-4 text-slate-600">{{ $order->due_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ number_format((float) $order->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No orders match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $orders->links() }}</div>@endif
    </div>
</section>
@endsection
