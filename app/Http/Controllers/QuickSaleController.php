<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Order;
use App\Services\CompletedOrderSaleService;
use App\Services\OrderArtworkService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class QuickSaleController extends Controller
{
    public function create(): View
    {
        $catalogItems = CatalogItem::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('material_id')
                    ->orWhereHas('material', fn (Builder $materialQuery) => $materialQuery->where('is_active', true));
            })
            ->with('material:id,name,is_active')
            ->orderBy('name')
            ->get();

        return view('quick-sales.create', [
            'customers' => Customer::orderBy('name')->get(),
            'catalogItems' => $catalogItems,
        ]);
    }

    public function store(
        Request $request,
        CompletedOrderSaleService $completedOrderSaleService,
        OrderArtworkService $orderArtworkService,
    ): RedirectResponse {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'required_without:new_customer.name', 'integer', 'exists:customers,id'],
            'new_customer.name' => ['nullable', 'required_without:customer_id', 'string', 'max:255'],
            'new_customer.phone' => ['nullable', 'string', 'max:40'],
            'new_customer.email' => ['nullable', 'email', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999.999'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'items.*.artworks' => ['nullable', 'array', 'max:5'],
            'items.*.artworks.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:10240'],
            'amount_paid' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'payment_method' => ['required', 'in:cash,mpesa,bank'],
            'bank_name' => ['nullable', 'required_if:payment_method,bank', 'string', 'max:255'],
        ]);

        $catalogItems = CatalogItem::query()
            ->whereKey(collect($validated['items'])->pluck('catalog_item_id')->unique())
            ->where('is_active', true)
            ->with('material')
            ->get()
            ->keyBy('id');
        $lineItems = [];
        $totalAmount = 0.0;

        foreach ($validated['items'] as $index => $inputItem) {
            /** @var CatalogItem|null $catalogItem */
            $catalogItem = $catalogItems->get((int) $inputItem['catalog_item_id']);

            if ($catalogItem === null || ($catalogItem->material_id !== null && ! $catalogItem->material?->is_active)) {
                throw ValidationException::withMessages([
                    "items.$index.catalog_item_id" => 'This catalog item is inactive or its linked material is unavailable.',
                ]);
            }

            $quantity = (float) $inputItem['quantity'];

            if ($catalogItem->unit === 'piece' && floor($quantity) !== $quantity) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => 'Quantity must be a whole number for items sold by piece.',
                ]);
            }

            $unitPrice = isset($inputItem['unit_price']) && $inputItem['unit_price'] !== ''
                ? (float) $inputItem['unit_price']
                : (float) $catalogItem->default_unit_price;
            $discount = (float) ($inputItem['discount'] ?? 0);
            $lineTotal = round(($quantity * $unitPrice) - $discount, 2);

            if ($discount > round($quantity * $unitPrice, 2)) {
                throw ValidationException::withMessages([
                    "items.$index.discount" => 'The discount cannot exceed the line total.',
                ]);
            }

            $totalAmount += $lineTotal;
            $lineItems[] = [
                ...$inputItem,
                'catalog_item' => $catalogItem,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'subtotal' => $lineTotal,
            ];
        }

        $totalAmount = round($totalAmount, 2);

        if ((float) $validated['amount_paid'] > $totalAmount) {
            throw ValidationException::withMessages([
                'amount_paid' => 'The amount paid cannot exceed the order total.',
            ]);
        }

        $storedPaths = [];

        try {
            $order = DB::transaction(function () use (
                $request,
                $validated,
                $lineItems,
                $totalAmount,
                &$storedPaths,
                $completedOrderSaleService,
                $orderArtworkService,
            ): Order {
                $customer = isset($validated['customer_id'])
                    ? Customer::findOrFail($validated['customer_id'])
                    : Customer::create([
                        'name' => $validated['new_customer']['name'],
                        'phone' => $validated['new_customer']['phone'] ?? null,
                        'email' => $validated['new_customer']['email'] ?? null,
                        'is_walk_in' => true,
                    ]);

                $order = $customer->orders()->create([
                    'status' => 'completed',
                    'due_date' => null,
                    'total_amount' => $totalAmount,
                    'amount_paid' => $validated['amount_paid'],
                    'payment_method' => $validated['payment_method'],
                    'bank_name' => $validated['payment_method'] === 'bank' ? $validated['bank_name'] : null,
                    'is_company_job' => false,
                ]);

                foreach ($lineItems as $index => $lineItem) {
                    /** @var CatalogItem $catalogItem */
                    $catalogItem = $lineItem['catalog_item'];
                    $orderItem = $order->items()->create([
                        'item_type' => $catalogItem->order_item_type,
                        'material_id' => $catalogItem->material_id,
                        'catalog_item_id' => $catalogItem->id,
                        'catalog_item_name' => $catalogItem->name,
                        'catalog_item_unit' => $catalogItem->unit,
                        'quantity_or_meters' => $lineItem['quantity'],
                        'unit_price' => $lineItem['unit_price'],
                        'discount' => $lineItem['discount'] ?: null,
                        'garment_sourced_by_shop' => $catalogItem->order_item_type === 'dtf_garment'
                            ? $catalogItem->material_id !== null
                            : null,
                        'subtotal' => $lineItem['subtotal'],
                    ]);

                    $storedPaths = [
                        ...$storedPaths,
                        ...$orderArtworkService->attachToOrderItem(
                            $orderItem,
                            $request->file("items.$index.artworks", []),
                        ),
                    ];
                }

                $completedOrderSaleService->createSale($order);

                return $order;
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('local')->delete($storedPaths);
            }

            throw $exception;
        }

        return redirect()->route('orders.show', $order)->with('success', 'Quick sale recorded.');
    }
}
