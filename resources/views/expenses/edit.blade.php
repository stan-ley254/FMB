@extends('layouts.app')

@section('title', 'Edit expense')

@section('content')
<section class="mx-auto max-w-4xl px-1 py-8 sm:px-0">
    <div class="mb-6">
        <a href="{{ route('expenses.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Expenses</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Edit expense</h1>
        <p class="mt-2 text-sm text-slate-500">Expense #{{ $expense->id }} · Recorded {{ $expense->created_at->format('M j, Y') }}</p>
    </div>
    @include('expenses._form')
</section>
@endsection
