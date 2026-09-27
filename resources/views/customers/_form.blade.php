<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Name <span class="text-pink-600">*</span></label>
        <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $customer->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="phone" class="mb-1.5 block text-sm font-medium text-slate-700">Phone</label>
        <input id="phone" name="phone" type="text" maxlength="40" value="{{ old('phone', $customer->phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
        <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $customer->email) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="flex items-center gap-3 pt-7">
        <input id="is_walk_in" name="is_walk_in" type="checkbox" value="1" @checked(old('is_walk_in', $customer->is_walk_in)) class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
        <label for="is_walk_in" class="text-sm font-medium text-slate-700">Walk-in customer</label>
    </div>
    <div class="sm:col-span-2">
        <label for="notes" class="mb-1.5 block text-sm font-medium text-slate-700">Notes</label>
        <textarea id="notes" name="notes" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('notes', $customer->notes) }}</textarea>
        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
