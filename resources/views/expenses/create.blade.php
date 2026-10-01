@extends('layouts.app')

@section('title', 'Record expense')

@section('content')
<section class="mx-auto max-w-4xl px-1 py-8 sm:px-0">
    <div class="mb-6">
        <a href="{{ route('expenses.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Expenses</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Record expense</h1>
    </div>
    @include('expenses._form')
</section>
@endsection
