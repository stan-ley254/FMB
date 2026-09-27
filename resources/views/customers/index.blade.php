@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<section class="mx-auto max-w-6xl px-1 py-8 sm:px-0">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">CUSTOMER DIRECTORY</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Customers</h1>
        </div>
        <a href="{{ route('customers.create') }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Add customer</a>
    </div>

    <form method="GET" action="{{ route('customers.index') }}" class="mb-5 flex max-w-xl gap-2">
        <label class="sr-only" for="customer-search">Search by name or phone</label>
        <input id="customer-search" type="search" name="q" value="{{ $search }}" placeholder="Search by name or phone" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        <button class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Search</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[600px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Phone</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Orders</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="px-5 py-4 font-medium text-slate-800"><a class="hover:text-blue-700" href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a></td>
                            <td class="px-5 py-4 text-slate-600">{{ $customer->phone ?: '—' }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $customer->is_walk_in ? 'bg-pink-50 text-pink-700' : 'bg-blue-50 text-blue-700' }}">{{ $customer->is_walk_in ? 'Walk-in' : 'Customer' }}</span></td>
                            <td class="px-5 py-4 text-slate-600">{{ $customer->orders_count }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('customers.edit', $customer) }}" class="font-medium text-blue-700 hover:text-blue-900">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No customers match your search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $customers->links() }}</div>@endif
    </div>
</section>
@endsection
