@extends('layouts.app')

@section('title', 'Create Order')

@section('content')
<section class="mx-auto max-w-5xl px-1 py-8 sm:px-0">
    <a href="{{ route('orders.index') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">← Orders</a>
    <div class="mt-3">
        <p class="eyebrow">NEW JOB</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Create order</h1>
    </div>

    <form method="POST" action="{{ route('orders.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="mb-5 font-semibold text-slate-800">Customer &amp; schedule</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="customer_id" class="mb-1.5 block text-sm font-medium text-slate-700">Customer</label>
                    <select id="customer_id" name="customer_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">Select a customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id', $selectedCustomerId) == $customer->id)>{{ $customer->name }}{{ $customer->phone ? ' · '.$customer->phone : '' }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input id="new-customer-toggle" type="checkbox" @checked(old('new_customer.name')) class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Create a new customer for this order
                    </label>
                </div>
                <div id="new-customer-fields" class="hidden grid gap-4 sm:col-span-2 sm:grid-cols-2">
                    <div><label for="new_customer_name" class="mb-1 block text-sm font-medium text-slate-700">New customer name *</label><input id="new_customer_name" name="new_customer[name]" value="{{ old('new_customer.name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                    <div><label for="new_customer_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label><input id="new_customer_phone" name="new_customer[phone]" value="{{ old('new_customer.phone') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                    <div><label for="new_customer_email" class="mb-1 block text-sm font-medium text-slate-700">Email</label><input id="new_customer_email" name="new_customer[email]" type="email" value="{{ old('new_customer.email') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                    <div class="flex items-center gap-2 pt-6"><input id="new_customer_walk_in" name="new_customer[is_walk_in]" type="checkbox" value="1" @checked(old('new_customer.is_walk_in')) class="size-4 rounded border-slate-300 text-blue-600"><label for="new_customer_walk_in" class="text-sm text-slate-700">Walk-in customer</label></div>
                </div>
                <div><label for="due_date" class="mb-1.5 block text-sm font-medium text-slate-700">Due date</label><input id="due_date" name="due_date" type="date" value="{{ old('due_date') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div class="flex items-center gap-2 pt-6"><input id="is_company_job" name="is_company_job" type="checkbox" value="1" @checked(old('is_company_job')) class="size-4 rounded border-slate-300 text-blue-600"><label for="is_company_job" class="text-sm font-medium text-slate-700">Company job</label></div>
                <div class="sm:col-span-2"><label for="notes" class="mb-1.5 block text-sm font-medium text-slate-700">Order notes</label><textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">{{ old('notes') }}</textarea></div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="font-semibold text-slate-800">Order items</h2><p class="mt-1 text-xs text-slate-500">Discount is a fixed amount off each line total.</p></div>
                <button type="button" id="add-item" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">+ Add line item</button>
            </div>
            <div id="order-items" class="space-y-4"></div>
            @error('items')<p class="mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('orders.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
            <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Save order</button>
        </div>
    </form>
</section>

<template id="order-item-template">
    <article class="order-item rounded-lg border border-slate-200 bg-slate-50 p-4">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="item-heading text-sm font-semibold text-slate-700">Line item</h3>
            <button type="button" class="remove-item text-sm font-medium text-red-600 hover:text-red-800">Remove</button>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Item type</label>
                <select name="items[__INDEX__][item_type]" class="item-type w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm" required>
                    <option value="banner">Banner</option><option value="sertine">Sertine</option><option value="sticker">Sticker</option><option value="dtf_garment">DTF garment</option>
                </select>
            </div>
            <div class="material-field">
                <label class="material-label mb-1 block text-xs font-medium text-slate-600">Material</label>
                <select name="items[__INDEX__][material_id]" class="material-select w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Choose stock material</option>
                    @foreach ($materials as $material)
                        <option value="{{ $material->id }}" data-category="{{ $material->category }}" data-unit="{{ $material->unit }}">{{ $material->name }} ({{ number_format((float) $material->quantity_remaining, 0) }} {{ $material->unit }})</option>
                    @endforeach
                </select>
            </div>
            <div class="hidden garment-sourced-field flex items-center gap-2 pt-6">
                <input type="checkbox" name="items[__INDEX__][garment_sourced_by_shop]" value="1" class="garment-sourced size-4 rounded border-slate-300 text-blue-600">
                <span class="text-sm text-slate-700">Blank garment supplied by shop</span>
            </div>
            <div><label class="quantity-label mb-1 block text-xs font-medium text-slate-600">Meters</label><input name="items[__INDEX__][quantity_or_meters]" type="number" min="0.001" step="0.001" required class="quantity-input w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-xs font-medium text-slate-600">Unit price</label><input name="items[__INDEX__][unit_price]" type="number" min="0" step="0.01" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-xs font-medium text-slate-600">Discount (fixed amount)</label><input name="items[__INDEX__][discount]" type="number" min="0" step="0.01" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm"></div>
            <div class="sm:col-span-2 lg:col-span-3"><label class="mb-1 block text-xs font-medium text-slate-600">Artwork (image and/or PDF, up to 5 files)</label><input name="items[__INDEX__][artworks][]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-blue-700"></div>
        </div>
    </article>
</template>

@push('scripts')
<script>
    const itemsContainer = document.getElementById('order-items');
    const itemTemplate = document.getElementById('order-item-template');
    let itemIndex = 0;

    function refreshMaterialOptions(item) {
        const type = item.querySelector('.item-type').value;
        const sourced = item.querySelector('.garment-sourced').checked;
        const materialField = item.querySelector('.material-field');
        const materialSelect = item.querySelector('.material-select');
        const quantityLabel = item.querySelector('.quantity-label');
        const isGarment = type === 'dtf_garment';
        const needsMaterial = !isGarment || sourced;
        const categories = { banner: 'banner', sertine: 'sertine', sticker: 'sticker', dtf_garment: 'garment' };
        const wantedCategory = categories[type];
        const currentValue = materialSelect.value;

        item.querySelector('.garment-sourced-field').classList.toggle('hidden', !isGarment);
        materialField.classList.toggle('hidden', !needsMaterial);
        materialSelect.required = needsMaterial;
        quantityLabel.textContent = isGarment ? 'Garment quantity (pieces)' : 'Length (meters)';
        materialSelect.querySelectorAll('option[data-category]').forEach((option) => {
            option.hidden = option.dataset.category !== wantedCategory;
        });

        if (!needsMaterial || !materialSelect.selectedOptions[0]?.matches(`[data-category="${wantedCategory}"]`)) {
            materialSelect.value = '';
        } else {
            materialSelect.value = currentValue;
        }
    }

    function addItem() {
        const fragment = itemTemplate.content.cloneNode(true);
        fragment.querySelectorAll('[name]').forEach((field) => {
            field.name = field.name.replaceAll('__INDEX__', itemIndex);
        });
        const item = fragment.querySelector('.order-item');
        item.querySelector('.item-heading').textContent = `Line item ${itemIndex + 1}`;
        item.querySelector('.item-type').addEventListener('change', () => refreshMaterialOptions(item));
        item.querySelector('.garment-sourced').addEventListener('change', () => refreshMaterialOptions(item));
        item.querySelector('.remove-item').addEventListener('click', () => {
            item.remove();
            renumberItems();
        });
        itemsContainer.append(fragment);
        refreshMaterialOptions(itemsContainer.lastElementChild);
        itemIndex++;
    }

    function renumberItems() {
        itemsContainer.querySelectorAll('.item-heading').forEach((heading, index) => {
            heading.textContent = `Line item ${index + 1}`;
        });
    }

    document.getElementById('add-item').addEventListener('click', addItem);
    addItem();

    const newCustomerToggle = document.getElementById('new-customer-toggle');
    const newCustomerFields = document.getElementById('new-customer-fields');
    const customerSelect = document.getElementById('customer_id');

    function refreshCustomerFields() {
        newCustomerFields.classList.toggle('hidden', !newCustomerToggle.checked);
        customerSelect.disabled = newCustomerToggle.checked;
        if (newCustomerToggle.checked) {
            customerSelect.value = '';
        }
    }

    newCustomerToggle.addEventListener('change', refreshCustomerFields);
    refreshCustomerFields();
</script>
@endpush
@endsection
