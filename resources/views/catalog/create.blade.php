@extends('layouts.app')

@section('title', 'Add catalog item')

@section('content')
<section class="mx-auto max-w-3xl px-1 py-8 sm:px-0">
    <div class="mb-6">
        <a href="{{ route('catalog.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Catalog</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Add catalog item</h1>
    </div>
    @include('catalog._form', ['catalogItem' => null])
</section>
@endsection
