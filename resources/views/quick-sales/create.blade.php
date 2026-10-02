@extends('layouts.app')

@section('title', 'Quick Sale')

@section('content')
<section class="mx-auto max-w-6xl px-1 py-8 sm:px-0">
    <div class="mb-6">
        <a href="{{ route('orders.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Orders</a>
        <p class="eyebrow mt-3">WALK-IN SALES</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Quick Sale</h1>
        <p class="mt-2 text-sm text-slate-500">Records a completed order and sale. Inventory is not consumed by sales.</p>
    </div>

    @if ($catalogItems->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            No active catalog items are available. Add or activate items in the <a class="font-semibold underline" href="{{ route('catalog.index') }}">Service &amp; Item Catalog</a>.
        </div>
    @else
        <form method="POST" action="{{ route('quick-sales.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <h2 class="mb-4 font-semibold text-slate-800">Customer</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="customer_id" class="mb-1 block text-sm font-medium text-slate-700">Existing customer</label>
                        <select id="customer_id" name="customer_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                            <option value="">Select customer or create a new walk-in</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}{{ $customer->is_walk_in ? ' · Walk-in' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700 sm:col-span-2">
                        <input id="new-customer-toggle" type="checkbox" @checked(old('new_customer.name')) class="size-4 rounded border-slate-300 text-blue-600">
                        Create a new walk-in customer
                    </label>
                    <div id="new-customer-fields" class="hidden grid gap-4 sm:col-span-2 sm:grid-cols-2">
                        <div>
                            <label for="new_customer_name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                            <input id="new_customer_name" name="new_customer[name]" value="{{ old('new_customer.name') }}" maxlength="255" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="new_customer_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                            <input id="new_customer_phone" name="new_customer[phone]" value="{{ old('new_customer.phone') }}" maxlength="40" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="new_customer_email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                            <input id="new_customer_email" name="new_customer[email]" type="email" value="{{ old('new_customer.email') }}" maxlength="255" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <p class="self-center text-sm text-slate-500">New customer is saved as a walk-in customer.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-slate-800">Items</h2>
                        <p class="mt-1 text-xs text-slate-500">Default catalog prices can be changed for this sale. Discounts are fixed amounts per line.</p>
                    </div>
                    <button type="button" id="add-quick-item" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">+ Add item</button>
                </div>
                <div id="quick-sale-items" class="space-y-4"></div>
                <p class="mt-4 flex justify-between border-t border-slate-100 pt-4 font-semibold text-slate-800"><span>Total</span><span id="quick-sale-total">0.00</span></p>
            </section>

            <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-3">
                <div>
                    <label for="amount_paid" class="mb-1 block text-sm font-medium text-slate-700">Amount paid</label>
                    <input id="amount_paid" name="amount_paid" type="number" min="0" step="0.01" required value="{{ old('amount_paid', 0) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <p class="mt-1 text-xs text-slate-500">Must not exceed the sale total.</p>
                </div>
                <div>
                    <label for="payment_method" class="mb-1 block text-sm font-medium text-slate-700">Payment method</label>
                    <select id="payment_method" name="payment_method" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        @foreach (['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'bank' => 'Bank'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="bank-name-field" class="{{ old('payment_method', 'cash') === 'bank' ? '' : 'hidden' }}">
                    <label for="bank_name" class="mb-1 block text-sm font-medium text-slate-700">Bank name</label>
                    <input id="bank_name" name="bank_name" value="{{ old('bank_name') }}" maxlength="255" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('orders.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
                <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Complete sale</button>
            </div>
        </form>

        <template id="quick-sale-item-template">
            <article class="quick-sale-item rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">Sale item</h3>
                    <button type="button" class="remove-quick-item text-sm font-medium text-red-600 hover:text-red-800">Remove</button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Catalog item</label>
                        <select name="items[__INDEX__][catalog_item_id]" class="catalog-item-select w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm" required>
                            <option value="">Choose service or item</option>
                            @foreach ($catalogItems as $catalogItem)
                                <option value="{{ $catalogItem->id }}" data-price="{{ $catalogItem->default_unit_price }}" data-unit="{{ $catalogItem->unit }}">{{ $catalogItem->name }} · {{ number_format((float) $catalogItem->default_unit_price, 2) }} / {{ $catalogItem->unit }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Quantity</label>
                        <input name="items[__INDEX__][quantity]" type="number" min="0.001" step="0.001" required class="quick-quantity w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Unit price</label>
                        <input name="items[__INDEX__][unit_price]" type="number" min="0" step="0.01" required class="quick-unit-price w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Line discount</label>
                        <input name="items[__INDEX__][discount]" type="number" min="0" step="0.01" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Artwork (optional, up to 5 files)</label>
                        <input name="items[__INDEX__][artworks][]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    </div>
                </div>
            </article>
        </template>
    @endif
</section>
@endsection

@push('scripts')
<script>
    const quickItems = document.getElementById('quick-sale-items');
    const itemTemplate = document.getElementById('quick-sale-item-template');
    let nextIndex = 0;

    function addQuickSaleItem() {
        const item = itemTemplate.content.cloneNode(true);
        const article = item.querySelector('.quick-sale-item');
        article.innerHTML = article.innerHTML.replaceAll('__INDEX__', nextIndex++);
        const select = article.querySelector('.catalog-item-select');
        const quantity = article.querySelector('.quick-quantity');
        const price = article.querySelector('.quick-unit-price');

        select.addEventListener('change', () => {
            const option = select.selectedOptions[0];
            price.value = option.dataset.price || '';
            quantity.step = option.dataset.unit === 'piece' ? '1' : '0.001';
            quantity.min = option.dataset.unit === 'piece' ? '1' : '0.001';
            quantity.placeholder = option.dataset.unit === 'piece' ? 'Pieces' : 'Meters';
            updateQuickSaleTotal();
        });
        article.querySelectorAll('.quick-quantity, .quick-unit-price, [name$="[discount]"]').forEach(input => {
            input.addEventListener('input', updateQuickSaleTotal);
        });
        article.querySelector('.remove-quick-item').addEventListener('click', () => {
            article.remove();
            updateQuickSaleTotal();
        });
        quickItems.append(article);
    }

    function updateQuickSaleTotal() {
        let total = 0;
        quickItems.querySelectorAll('.quick-sale-item').forEach(item => {
            const quantity = Number(item.querySelector('.quick-quantity').value || 0);
            const price = Number(item.querySelector('.quick-unit-price').value || 0);
            const discount = Number(item.querySelector('[name$="[discount]"]').value || 0);
            total += Math.max(0, (quantity * price) - discount);
        });
        document.getElementById('quick-sale-total').textContent = total.toFixed(2);

        const amountPaid = document.getElementById('amount_paid');

        if (amountPaid.dataset.manual !== 'true') {
            amountPaid.value = total.toFixed(2);
        }
    }

    document.getElementById('add-quick-item')?.addEventListener('click', addQuickSaleItem);
    document.getElementById('amount_paid')?.addEventListener('input', event => {
        event.target.dataset.manual = 'true';
    });
    document.getElementById('new-customer-toggle')?.addEventListener('change', event => {
        document.getElementById('new-customer-fields').classList.toggle('hidden', !event.target.checked);
        document.getElementById('customer_id').disabled = event.target.checked;
    });
    document.getElementById('payment_method')?.addEventListener('change', event => {
        document.getElementById('bank-name-field').classList.toggle('hidden', event.target.value !== 'bank');
    });

    if (quickItems) {
        addQuickSaleItem();

        if (document.getElementById('new-customer-toggle').checked) {
            document.getElementById('customer_id').disabled = true;
            document.getElementById('new-customer-fields').classList.remove('hidden');
        }
    }
</script>
@endpush
