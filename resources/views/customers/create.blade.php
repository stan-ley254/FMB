@extends('layouts.app')

@section('title', 'Add Customer')

@section('content')
<section class="mx-auto max-w-3xl px-1 py-8 sm:px-0">
    <a href="{{ route('customers.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Customers</a>
    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900">Add customer</h1>
    <form method="POST" action="{{ route('customers.store') }}" class="mt-6 space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @include('customers._form')
        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('customers.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
            <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Save customer</button>
        </div>
    </form>
</section>
@endsection
