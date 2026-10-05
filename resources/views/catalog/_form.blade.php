<form method="POST" action="{{ $catalogItem ? route('catalog.update', $catalogItem) : route('catalog.store') }}" class="space-y-5">
    @csrf
    @if ($catalogItem)
        @method('PATCH')
    @endif
    <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
            <input id="name" name="name" required maxlength="255" value="{{ old('name', $catalogItem?->name) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="order_item_type" class="mb-1 block text-sm font-medium text-slate-700">Order item type</label>
            <select id="order_item_type" name="order_item_type" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @foreach ($itemTypes as $value => $label)
                    <option value="{{ $value }}" @selected(old('order_item_type', $catalogItem?->order_item_type) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="category" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
            <select id="category" name="category" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Not applicable</option>
                @foreach ($categories as $category => $label)
                    <option value="{{ $category }}" @selected(old('category', $catalogItem?->category) === $category)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="unit" class="mb-1 block text-sm font-medium text-slate-700">Unit</label>
            <select id="unit" name="unit" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @foreach ($units as $value => $label)
                    <option value="{{ $value }}" @selected(old('unit', $catalogItem?->unit ?? 'meter') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="default_unit_price" class="mb-1 block text-sm font-medium text-slate-700">Default unit price</label>
            <input id="default_unit_price" name="default_unit_price" type="number" min="0" max="9999999999.99" step="0.01" required value="{{ old('default_unit_price', $catalogItem?->default_unit_price) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div class="sm:col-span-2">
            <label for="material_id" class="mb-1 block text-sm font-medium text-slate-700">Linked material (optional)</label>
            <select id="material_id" name="material_id" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">No material linked</option>
                @foreach ($materials as $material)
                    <option value="{{ $material->id }}" @selected((string) old('material_id', $catalogItem?->material_id) === (string) $material->id)>{{ $material->name }} · {{ str($material->category)->replace('-', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
        @if ($catalogItem)
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700 sm:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $catalogItem->is_active)) class="size-4 rounded border-slate-300 text-blue-600">
                Active and available for Quick Sale
            </label>
        @endif
    </section>
    <div class="flex justify-between gap-3">
        <a href="{{ route('catalog.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
        <button class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">{{ $catalogItem ? 'Save changes' : 'Create item' }}</button>
    </div>
</form>
