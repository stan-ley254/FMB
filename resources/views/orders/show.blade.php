@extends('layouts.app')

@section('title', 'Order #'.$order->id)

@section('content')
<section class="mx-auto max-w-6xl px-1 py-8 sm:px-0">
    <div class="mb-7 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('orders.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Orders</a>
            <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Order #{{ $order->id }}</h1>
            <p class="mt-2 text-sm text-slate-500"><a class="text-blue-700 hover:underline" href="{{ route('customers.show', $order->customer) }}">{{ $order->customer->name }}</a> · Created {{ $order->created_at->format('M j, Y') }}</p>
        </div>
        <span class="rounded-full bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700">{{ str($order->status)->replace('_', ' ')->title() }}</span>
    </div>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(18rem,0.8fr)]">
        <div class="space-y-6">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-800">Items</h2></header>
                <div class="divide-y divide-slate-100">
                    @foreach ($order->items as $item)
                        <article class="px-5 py-5">
                            <div class="flex flex-wrap justify-between gap-3">
                                <div><h3 class="font-semibold text-slate-800">{{ $item->material?->name ?? str($item->item_type)->replace('_', ' ')->title() }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ number_format((float) $item->quantity_or_meters, 3) }} {{ $item->item_type === 'dtf_garment' ? 'pieces' : 'meters' }} × {{ number_format((float) $item->unit_price, 2) }}{{ $item->discount ? ' · Discount '.number_format((float) $item->discount, 2) : '' }}</p>
                                    @if ($item->item_type === 'dtf_garment')<p class="mt-1 text-xs text-slate-500">{{ $item->garment_sourced_by_shop ? 'Blank garment supplied by shop' : 'Blank garment supplied by customer' }}</p>@endif
                                </div>
                                <p class="font-semibold text-slate-800">{{ number_format((float) $item->subtotal, 2) }}</p>
                            </div>
                            @if ($item->artworks->isNotEmpty())
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($item->artworks as $artwork)
                                        <a href="{{ route('artworks.download', $artwork) }}" class="rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-50">{{ str($artwork->purpose)->title() }} · {{ strtoupper($artwork->file_type) }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
                <div class="flex justify-between border-t border-slate-100 bg-slate-50 px-5 py-4 font-semibold"><span>Total</span><span>{{ number_format((float) $order->total_amount, 2) }}</span></div>
            </section>

            @if ($order->notes)
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-800">Notes</h2><p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $order->notes }}</p></section>
            @endif

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-800">Stock usage</h2></header>
                <div class="divide-y divide-slate-100">
                    @forelse ($order->stockMovements as $movement)
                        <div class="flex flex-wrap justify-between gap-2 px-5 py-3 text-sm">
                            <span class="text-slate-700">{{ $movement->material?->name ?? ($movement->inkStock ? str($movement->inkStock->machine)->replace('_', ' ')->title().' '.$movement->inkStock->color.' ink' : 'Stock item') }}</span>
                            <span class="text-slate-600">{{ str($movement->type)->title() }} · {{ number_format((float) $movement->quantity, 3) }} · {{ $movement->created_at->format('M j, Y H:i') }}</span>
                        </div>
                    @empty
                        <p class="px-5 py-5 text-sm text-slate-500">No stock movements linked to this order.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-800">Payment &amp; status</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd class="font-medium text-slate-800">{{ number_format((float) $order->amount_paid, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Balance</dt><dd class="font-medium text-slate-800">{{ number_format((float) $order->total_amount - (float) $order->amount_paid, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Method</dt><dd class="font-medium text-slate-800">{{ $order->payment_method ? str($order->payment_method)->upper() : '—' }}</dd></div>
                    @if ($order->bank_name)<div class="flex justify-between"><dt class="text-slate-500">Bank</dt><dd class="font-medium text-slate-800">{{ $order->bank_name }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-slate-500">Due date</dt><dd class="font-medium text-slate-800">{{ $order->due_date?->format('M j, Y') ?? '—' }}</dd></div>
                </dl>
                <form method="POST" action="{{ route('orders.update', $order) }}" class="mt-5 space-y-4 border-t border-slate-100 pt-5">
                    @csrf
                    @method('PATCH')
                    <div><label for="status" class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                        <select id="status" name="status" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            @foreach (['pending', 'in_production', 'ready', 'completed'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $order->status) === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label for="amount_paid" class="mb-1 block text-sm font-medium text-slate-700">Amount paid</label><input id="amount_paid" name="amount_paid" type="number" min="0" max="{{ $order->total_amount }}" step="0.01" required value="{{ old('amount_paid', $order->amount_paid) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
                    <div><label for="payment_method" class="mb-1 block text-sm font-medium text-slate-700">Payment method</label>
                        <select id="payment_method" name="payment_method" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Not recorded</option>
                            @foreach (['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'bank' => 'Bank'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_method', $order->payment_method) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="bank-name-field" class="{{ old('payment_method', $order->payment_method) === 'bank' ? '' : 'hidden' }}"><label for="bank_name" class="mb-1 block text-sm font-medium text-slate-700">Bank name</label><input id="bank_name" name="bank_name" maxlength="255" value="{{ old('bank_name', $order->bank_name) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></div>
                    <button class="w-full rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Save updates</button>
                </form>
            </section>
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-800">Job type</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $order->is_company_job ? 'Company job' : 'Walk-in / standard order' }}</p>
            </section>
        </aside>
    </div>
</section>
@push('scripts')
<script>
    const paymentMethod = document.getElementById('payment_method');
    const bankField = document.getElementById('bank-name-field');
    paymentMethod.addEventListener('change', () => bankField.classList.toggle('hidden', paymentMethod.value !== 'bank'));
</script>
@endpush
@endsection
