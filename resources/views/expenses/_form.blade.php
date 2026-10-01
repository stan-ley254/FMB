@php
    $addsToStock = old('adds_to_stock', $expense?->adds_to_stock ?? false);
    $stockType = old('stock_type', $expense?->related_ink_stock_id ? 'ink' : 'material');
    $stockItemId = old('stock_item_id', $expense?->related_material_id ?? $expense?->related_ink_stock_id);
@endphp

<form method="POST" action="{{ $expense ? route('expenses.update', $expense) : route('expenses.store') }}" class="space-y-6">
    @csrf
    @if ($expense)
        @method('PATCH')
    @endif

    <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2">
        <div>
            <label for="category" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
            <select id="category" name="category" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $expense?->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="supplier_name" class="mb-1 block text-sm font-medium text-slate-700">Supplier</label>
            <input id="supplier_name" name="supplier_name" maxlength="255" value="{{ old('supplier_name', $expense?->supplier_name) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div class="sm:col-span-2">
            <label for="item_description" class="mb-1 block text-sm font-medium text-slate-700">Item description</label>
            <input id="item_description" name="item_description" required maxlength="255" value="{{ old('item_description', $expense?->item_description) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="quantity_or_size" class="mb-1 block text-sm font-medium text-slate-700">Quantity / size</label>
            @if ($expense)
                <input id="quantity_or_size" value="{{ $expense->quantity_or_size }}" readonly class="w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                <p class="mt-1 text-xs text-slate-500">Locked after recording to preserve the stock audit trail.</p>
            @else
                <input id="quantity_or_size" name="quantity_or_size" maxlength="255" value="{{ old('quantity_or_size') }}" placeholder="For stock additions, enter whole units" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @endif
        </div>
        <div>
            <label for="amount" class="mb-1 block text-sm font-medium text-slate-700">Amount</label>
            <input id="amount" name="amount" type="number" min="0.01" max="9999999999.99" step="0.01" required value="{{ old('amount', $expense?->amount) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="payment_method" class="mb-1 block text-sm font-medium text-slate-700">Payment method</label>
            <select id="payment_method" name="payment_method" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @foreach (['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'bank' => 'Bank'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('payment_method', $expense?->payment_method ?? 'cash') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div id="bank-name-field" class="{{ old('payment_method', $expense?->payment_method) === 'bank' ? '' : 'hidden' }}">
            <label for="bank_name" class="mb-1 block text-sm font-medium text-slate-700">Bank name</label>
            <select id="bank_name" name="bank_name" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Select a bank</option>
                @foreach ($banks as $bank)
                    <option value="{{ $bank }}" @selected(old('bank_name', $expense?->bank_name) === $bank)>{{ $bank }}</option>
                @endforeach
            </select>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
            @if ($expense)
                <input type="checkbox" @checked($expense->adds_to_stock) disabled class="size-4 rounded border-slate-300 text-blue-600">
            @else
                <input id="adds_to_stock" name="adds_to_stock" type="checkbox" value="1" @checked($addsToStock) class="size-4 rounded border-slate-300 text-blue-600">
            @endif
            This purchase adds to stock
        </label>

        @if ($expense)
            @if ($expense->adds_to_stock)
                <p class="mt-3 text-sm text-slate-600">Stock quantity and item are locked. Deleting this expense will reverse its stock movement.</p>
                <p class="mt-1 text-sm font-medium text-slate-800">{{ $expense->relatedMaterial?->name ?? ($expense->relatedInkStock ? ($expense->relatedInkStock->machine === 'dtf' ? 'DTF Printer' : 'Large Format').' '.str($expense->relatedInkStock->color)->title() : 'Linked stock item unavailable') }} · {{ $expense->quantity_or_size }} units</p>
            @endif
        @else
            <div id="stock-fields" class="{{ $addsToStock ? '' : 'hidden' }} mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="stock_type" class="mb-1 block text-sm font-medium text-slate-700">Inventory type</label>
                    <select id="stock_type" name="stock_type" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="material" @selected($stockType === 'material')>Material</option>
                        <option value="ink" @selected($stockType === 'ink')>Ink</option>
                    </select>
                </div>
                <div>
                    <label for="material_id" class="mb-1 block text-sm font-medium text-slate-700">Material</label>
                    <select id="material_id" name="stock_item_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select a material</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material->id }}" @selected($stockType === 'material' && (string) $stockItemId === (string) $material->id)>{{ $material->name }} ({{ number_format((float) $material->quantity_remaining, 0) }} {{ $material->unit }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="ink_stock_id" class="mb-1 block text-sm font-medium text-slate-700">Ink stock</label>
                    <select id="ink_stock_id" name="stock_item_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Select ink stock</option>
                        @foreach ($inkStocks as $inkStock)
                            <option value="{{ $inkStock->id }}" @selected($stockType === 'ink' && (string) $stockItemId === (string) $inkStock->id)>{{ $inkStock->machine === 'dtf' ? 'DTF Printer' : 'Large Format' }} {{ str($inkStock->color)->title() }} ({{ number_format((float) $inkStock->quantity_remaining, 0) }} {{ $inkStock->unit }})</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-xs text-slate-500 sm:col-span-3">For stock purchases, Quantity / size above is the whole number of units to add. The same quantity will be removed from stock if you delete this expense.</p>
            </div>
        @endif
    </section>

    <div class="flex flex-wrap justify-between gap-3">
        <a href="{{ route('expenses.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
        @if ($expense)
            <button form="delete-expense-form" class="rounded-lg border border-red-300 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Delete expense</button>
        @endif
        <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ $expense ? 'Save changes' : 'Record expense' }}</button>
    </div>
</form>

@if ($expense)
    <form id="delete-expense-form" method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('Delete this expense? Any stock addition will be reversed and recorded in stock history.')">
        @csrf
        @method('DELETE')
    </form>
@endif

@unless ($expense)
    @pushOnce('scripts')
        <script>
            const addsToStock = document.getElementById('adds_to_stock');
            const stockFields = document.getElementById('stock-fields');
            const stockType = document.getElementById('stock_type');
            const materialSelect = document.getElementById('material_id');
            const inkSelect = document.getElementById('ink_stock_id');
            const quantityOrSize = document.getElementById('quantity_or_size');

            if (addsToStock && stockFields && stockType && materialSelect && inkSelect && quantityOrSize) {
                function updateStockFields() {
                    stockFields.classList.toggle('hidden', !addsToStock.checked);
                    materialSelect.disabled = !addsToStock.checked || stockType.value !== 'material';
                    inkSelect.disabled = !addsToStock.checked || stockType.value !== 'ink';
                    quantityOrSize.required = addsToStock.checked;
                    quantityOrSize.type = addsToStock.checked ? 'number' : 'text';
                    quantityOrSize.min = addsToStock.checked ? '1' : '';
                    quantityOrSize.step = addsToStock.checked ? '1' : '';
                }

                addsToStock.addEventListener('change', updateStockFields);
                stockType.addEventListener('change', updateStockFields);
                updateStockFields();
            }
        </script>
    @endpushOnce
@endunless

@pushOnce('scripts')
    <script>
        const paymentMethod = document.getElementById('payment_method');
        const bankNameField = document.getElementById('bank-name-field');
        const bankName = document.getElementById('bank_name');

        function updateBankField() {
            const requiresBank = paymentMethod.value === 'bank';
            bankNameField.classList.toggle('hidden', !requiresBank);
            bankName.required = requiresBank;
        }

        paymentMethod.addEventListener('change', updateBankField);
        updateBankField();
    </script>
@endPushOnce
