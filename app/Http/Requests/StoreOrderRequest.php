<?php

namespace App\Http\Requests;

use App\Models\CatalogItem;
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
            'items.*.item_type' => ['prohibited'],
            'items.*.material_id' => ['prohibited'],
            'items.*.catalog_item_id' => ['required', 'integer', Rule::exists('catalog_items', 'id')->where('is_active', true)],
            'items.*.quantity_or_meters' => ['required', 'numeric', 'gt:0', 'max:999999.999'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
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
                $catalogItems = CatalogItem::query()
                    ->whereKey(collect($items)->pluck('catalog_item_id')->unique())
                    ->where('is_active', true)
                    ->where(function ($query): void {
                        $query->whereNull('material_id')
                            ->orWhereHas('material', fn ($materialQuery) => $materialQuery->where('is_active', true));
                    })
                    ->with('material')
                    ->get()
                    ->keyBy('id');

                foreach ($items as $index => $item) {
                    $catalogItem = $catalogItems->get((int) $item['catalog_item_id']);

                    if ($catalogItem === null) {
                        $validator->errors()->add(
                            "items.$index.catalog_item_id",
                            'Select an active catalog item with an active linked material.',
                        );

                        continue;
                    }

                    $isGarment = $catalogItem->category === 'garment';
                    $isShopSourcedGarment = $isGarment
                        && filter_var($item['garment_sourced_by_shop'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    if ($isShopSourcedGarment && $catalogItem->material_id === null) {
                        $validator->errors()->add(
                            "items.$index.garment_sourced_by_shop",
                            'This catalog item has no linked garment stock to source from.',
                        );
                    }

                    $quantity = (float) $item['quantity_or_meters'];

                    if ($catalogItem->unit === 'piece' && floor($quantity) !== $quantity) {
                        $validator->errors()->add(
                            "items.$index.quantity_or_meters",
                            'Quantity must be a whole number for items sold by piece.',
                        );
                    }

                    $unitPrice = isset($item['unit_price']) && $item['unit_price'] !== ''
                        ? (float) $item['unit_price']
                        : (float) $catalogItem->default_unit_price;
                    $lineTotal = (float) $item['quantity_or_meters'] * $unitPrice;

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
