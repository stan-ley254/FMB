<form method="POST" action="{{ $material ? route('stock.materials.update', $material) : route('stock.materials.store') }}" class="space-y-5">
    @csrf
    @if ($material)
        @method('PATCH')
    @endif

    <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Material name</label>
            <input id="name" name="name" required maxlength="255" value="{{ old('name', $material?->name) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label for="category" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
            <select id="category" name="category" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $material?->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="unit" class="mb-1 block text-sm font-medium text-slate-700">Unit</label>
            <select id="unit" name="unit" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @foreach ($units as $unit)
                    <option value="{{ $unit }}" @selected(old('unit', $material?->unit ?? 'rolls') === $unit)>{{ str($unit)->title() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="machine" class="mb-1 block text-sm font-medium text-slate-700">Machine</label>
            <select id="machine" name="machine" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <option value="">Shared / not machine-specific</option>
                @foreach ($machines as $value => $label)
                    <option value="{{ $value }}" @selected(old('machine', $material?->machine) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if ($material)
            <div>
                <label for="quantity_remaining" class="mb-1 block text-sm font-medium text-slate-700">Quantity remaining</label>
                <input id="quantity_remaining" name="quantity_remaining" type="number" min="0" max="999999" step="1" required value="{{ old('quantity_remaining', $material->quantity_remaining) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">Changing quantity records the signed correction in stock history.</p>
            </div>
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700 sm:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $material->is_active)) class="size-4 rounded border-slate-300 text-blue-600">
                Active and available for new orders and expenses
            </label>
        @else
            <div>
                <label for="starting_quantity" class="mb-1 block text-sm font-medium text-slate-700">Starting quantity</label>
                <input id="starting_quantity" name="starting_quantity" type="number" min="0" max="999999" step="1" required value="{{ old('starting_quantity', 0) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-slate-500">A non-zero starting quantity is recorded as a purchase movement.</p>
            </div>
        @endif
    </section>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('stock.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</a>
        <div class="flex flex-wrap gap-2">
            @if ($material)
                @if ($canDelete)
                    <button type="submit" form="delete-material-form" class="rounded-lg border border-red-300 px-4 py-2.5 text-sm font-semibold text-red-700">Delete material</button>
                @else
                    <p class="self-center text-xs text-slate-500">Has historical references; deactivate instead of deleting.</p>
                @endif
            @endif
            <button class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ $material ? 'Save changes' : 'Create material' }}</button>
        </div>
    </div>
</form>
@if ($material && $canDelete)
    <form id="delete-material-form" method="POST" action="{{ route('stock.materials.destroy', $material) }}" onsubmit="return confirm('Permanently delete this material?');">
        @csrf
        @method('DELETE')
    </form>
@endif
