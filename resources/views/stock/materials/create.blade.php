@extends('layouts.app')

@section('title', 'Add material')

@section('content')
<section class="mx-auto max-w-3xl px-1 py-8 sm:px-0">
    <div class="mb-6">
        <a href="{{ route('stock.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Materials &amp; Ink Stock</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Add New Material</h1>
    </div>
    @include('stock.materials._form', ['material' => null])
</section>
@endsection
