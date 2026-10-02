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
    private const CATEGORIES = ['banner', 'sertine', 'sticker', 'dtf-consumable', 'garment'];

    private const ITEM_TYPES = ['banner', 'sertine', 'sticker', 'dtf_garment', 'dtf_print'];

    public function index(): View
    {
        return view('catalog.index', [
            'catalogItems' => CatalogItem::query()->with('material')->orderBy('name')->paginate(25),
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
            'category' => ['nullable', Rule::in(self::CATEGORIES)],
            'order_item_type' => ['required', Rule::in(self::ITEM_TYPES)],
            'unit' => ['required', Rule::in(['meter', 'piece'])],
            'default_unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'material_id' => [
                'nullable',
                'integer',
                Rule::exists('materials', 'id')->where('is_active', true),
            ],
            'is_active' => $catalogItem ? ['sometimes', 'boolean'] : ['prohibited'],
        ]);

        $expectedCategory = match ($validated['order_item_type']) {
            'banner' => 'banner',
            'sertine' => 'sertine',
            'sticker' => 'sticker',
            'dtf_garment' => 'garment',
            'dtf_print' => null,
        };

        if (($validated['category'] ?? null) !== $expectedCategory) {
            throw ValidationException::withMessages([
                'category' => 'The material category must match the selected order item type.',
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
     * @return array{categories: array<int, string>, itemTypes: array<string, string>, units: array<string, string>, materials: Collection<int, Material>}
     */
    private function formData(): array
    {
        return [
            'categories' => self::CATEGORIES,
            'itemTypes' => [
                'banner' => 'Banner',
                'sertine' => 'Sertine',
                'sticker' => 'Sticker',
                'dtf_garment' => 'DTF garment',
                'dtf_print' => 'DTF print',
            ],
            'units' => ['meter' => 'Meter', 'piece' => 'Piece'],
            'materials' => Material::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
