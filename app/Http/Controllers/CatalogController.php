<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        return view('catalog.index', [
            'catalogItems' => CatalogItem::query()->with('material')->orderBy('name')->paginate(25),
            'itemTypes' => CatalogItem::ITEM_TYPES,
        ]);
    }

    public function create(): View
    {
        return view('catalog.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedItem($request);
        CatalogItem::create($validated + ['is_active' => true]);

        return redirect()->route('catalog.index')->with('success', 'Catalog item created.');
    }

    public function edit(CatalogItem $catalogItem): View
    {
        return view('catalog.edit', [
            ...$this->formData(),
            'catalogItem' => $catalogItem,
        ]);
    }

    public function update(Request $request, CatalogItem $catalogItem): RedirectResponse
    {
        $validated = $this->validatedItem($request, $catalogItem);
        $catalogItem->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('catalog.index')->with('success', 'Catalog item updated.');
    }

    private function validatedItem(Request $request, ?CatalogItem $catalogItem = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('catalog_items', 'name')->ignore($catalogItem?->id),
            ],
            'category' => ['nullable', Rule::in(array_keys(CatalogItem::CATEGORIES))],
            'order_item_type' => ['required', Rule::in(array_keys(CatalogItem::ITEM_TYPES))],
            'unit' => ['required', Rule::in(['meter', 'piece'])],
            'default_unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'material_id' => [
                'nullable',
                'integer',
                Rule::exists('materials', 'id')->where('is_active', true),
            ],
            'is_active' => $catalogItem ? ['sometimes', 'boolean'] : ['prohibited'],
        ]);

        $expectedCategory = CatalogItem::EXPECTED_CATEGORIES[$validated['order_item_type']];

        if (($validated['category'] ?? null) !== $expectedCategory) {
            throw ValidationException::withMessages([
                'category' => 'The category must match the selected order item type.',
            ]);
        }

        if (isset($validated['material_id'])) {
            $material = Material::findOrFail($validated['material_id']);

            if ($material->category !== ($validated['category'] ?? null)) {
                throw ValidationException::withMessages([
                    'material_id' => 'The linked material must match the selected category.',
                ]);
            }
        }

        return $validated;
    }

    /**
     * @return array{categories: array<string, string>, itemTypes: array<string, string>, units: array<string, string>, materials: Collection<int, Material>}
     */
    private function formData(): array
    {
        return [
            'categories' => CatalogItem::CATEGORIES,
            'itemTypes' => CatalogItem::ITEM_TYPES,
            'units' => ['meter' => 'Meter', 'piece' => 'Piece'],
            'materials' => Material::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
