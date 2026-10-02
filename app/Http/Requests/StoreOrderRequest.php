<?php

namespace App\Http\Requests;

use App\Models\CatalogItem;
use App\Models\Material;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'required_without:new_customer.name', 'integer', 'exists:customers,id'],
            'new_customer.name' => ['nullable', 'required_without:customer_id', 'string', 'max:255'],
            'new_customer.phone' => ['nullable', 'string', 'max:40'],
            'new_customer.email' => ['nullable', 'email', 'max:255'],
            'new_customer.notes' => ['nullable', 'string'],
            'new_customer.is_walk_in' => ['nullable', 'boolean'],
            'is_company_job' => ['nullable', 'boolean'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.item_type' => ['required', 'in:banner,sertine,sticker,dtf_garment,dtf_print'],
            'items.*.material_id' => ['nullable', 'integer', Rule::exists('materials', 'id')->where('is_active', true)],
            'items.*.catalog_item_id' => ['nullable', 'integer', Rule::exists('catalog_items', 'id')->where('is_active', true)],
            'items.*.quantity_or_meters' => ['required', 'numeric', 'gt:0', 'max:999999.999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.garment_sourced_by_shop' => ['nullable', 'boolean'],
            'items.*.artworks' => ['nullable', 'array', 'max:5'],
            'items.*.artworks.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $items = $this->input('items', []);
                $materialIds = collect($items)
                    ->pluck('material_id')
                    ->filter()
                    ->unique()
                    ->values();
                $materials = Material::whereKey($materialIds)->where('is_active', true)->get()->keyBy('id');
                $catalogItems = CatalogItem::whereKey(collect($items)->pluck('catalog_item_id')->filter()->unique())
                    ->where('is_active', true)
                    ->get()
                    ->keyBy('id');

                foreach ($items as $index => $item) {
                    $itemType = $item['item_type'];
                    $catalogItemId = $item['catalog_item_id'] ?? null;
                    $catalogItem = $catalogItemId === null ? null : $catalogItems->get((int) $catalogItemId);
                    $isShopSourcedGarment = $itemType === 'dtf_garment'
                        && filter_var($item['garment_sourced_by_shop'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    $requiredCategory = match ($itemType) {
                        'banner' => 'banner',
                        'sertine' => 'sertine',
                        'sticker' => 'sticker',
                        'dtf_garment' => $isShopSourcedGarment ? 'garment' : null,
                        'dtf_print' => null,
                    };
                    $materialId = $item['material_id'] ?? null;

                    if ($catalogItemId !== null && (
                        $catalogItem === null
                        || $catalogItem->order_item_type !== $itemType
                        || ($catalogItem->material_id === null
                            ? $materialId !== null
                            : (int) $catalogItem->material_id !== (int) $materialId)
                    )) {
                        $validator->errors()->add(
                            "items.$index.catalog_item_id",
                            'Select an active catalog item that matches this order line.',
                        );
                    } elseif ($requiredCategory === null && $materialId !== null) {
                        $validator->errors()->add(
                            "items.$index.material_id",
                            $itemType === 'dtf_garment'
                                ? 'Customer-supplied garments do not use shop garment stock.'
                                : 'This order item does not use a material.',
                        );
                    } elseif ($requiredCategory !== null && $materialId === null && $catalogItem?->material_id === null) {
                        $validator->errors()->add(
                            "items.$index.material_id",
                            'Select the material used for this item.',
                        );
                    } elseif ($requiredCategory !== null && $materialId !== null
                        && $materials->get((int) $materialId)?->category !== $requiredCategory) {
                        $validator->errors()->add(
                            "items.$index.material_id",
                            'Select a material that matches the item type.',
                        );
                    }

                    $lineTotal = (float) $item['quantity_or_meters'] * (float) $item['unit_price'];

                    if (isset($item['discount']) && (float) $item['discount'] > $lineTotal) {
                        $validator->errors()->add(
                            "items.$index.discount",
                            'The discount cannot exceed the line total.',
                        );
                    }
                }
            },
        ];
    }
}
