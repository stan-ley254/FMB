<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Order;
use App\Services\CompletedOrderSaleService;
use App\Services\OrderArtworkService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,in_production,ready,completed'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'customer' => ['nullable', 'string', 'max:255'],
        ]);

        $orders = Order::query()
            ->with('customer')
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($validated['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($validated['customer'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('customer', fn (Builder $customerQuery) => $customerQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('orders.index', ['orders' => $orders, 'filters' => $validated]);
    }

    public function create(Request $request): View
    {
        return view('orders.create', [
            'customers' => Customer::orderBy('name')->get(),
            'materials' => Material::where('is_active', true)->orderBy('name')->get(),
            'selectedCustomerId' => $request->integer('customer_id') ?: null,
        ]);
    }

    public function store(StoreOrderRequest $request, OrderArtworkService $orderArtworkService): RedirectResponse
    {
        $validated = $request->validated();
        $storedPaths = [];

        try {
            $order = DB::transaction(function () use ($request, $validated, &$storedPaths, $orderArtworkService): Order {
                $customer = isset($validated['customer_id'])
                    ? Customer::findOrFail($validated['customer_id'])
                    : Customer::create([
                        ...$validated['new_customer'],
                        'is_walk_in' => (bool) ($validated['new_customer']['is_walk_in'] ?? false),
                    ]);

                $order = $customer->orders()->create([
                    'status' => 'pending',
                    'due_date' => $validated['due_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'total_amount' => 0,
                    'amount_paid' => 0,
                    'is_company_job' => (bool) ($validated['is_company_job'] ?? false),
                ]);
                $totalAmount = 0;
                $catalogItems = CatalogItem::whereKey(
                    collect($validated['items'])->pluck('catalog_item_id')->filter()->unique(),
                )->get()->keyBy('id');

                foreach ($validated['items'] as $index => $itemData) {
                    $catalogItem = isset($itemData['catalog_item_id'])
                        ? $catalogItems->get((int) $itemData['catalog_item_id'])
                        : null;
                    $material = null;
                    $requiresMaterial = $catalogItem !== null
                        ? $catalogItem->material_id !== null
                        : ($itemData['item_type'] !== 'dtf_garment'
                            && $itemData['item_type'] !== 'dtf_print')
                            || (bool) ($itemData['garment_sourced_by_shop'] ?? false);

                    if ($requiresMaterial) {
                        $material = Material::whereKey($itemData['material_id'])
                            ->where('is_active', true)
                            ->firstOrFail();
                    }
                    $quantity = (float) $itemData['quantity_or_meters'];
                    $unitPrice = (float) $itemData['unit_price'];
                    $discount = (float) ($itemData['discount'] ?? 0);
                    $subtotal = round(($quantity * $unitPrice) - $discount, 2);
                    $orderItem = $order->items()->create([
                        'item_type' => $itemData['item_type'],
                        'material_id' => $material?->id,
                        'catalog_item_id' => $catalogItem?->id,
                        'catalog_item_name' => $catalogItem?->name,
                        'catalog_item_unit' => $catalogItem?->unit,
                        'quantity_or_meters' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount' => $itemData['discount'] ?? null,
                        'garment_sourced_by_shop' => $itemData['item_type'] === 'dtf_garment'
                            ? (bool) ($itemData['garment_sourced_by_shop'] ?? false)
                            : null,
                        'subtotal' => $subtotal,
                    ]);
                    $totalAmount += $subtotal;

                    $storedPaths = [
                        ...$storedPaths,
                        ...$orderArtworkService->attachToOrderItem(
                            $orderItem,
                            $request->file("items.$index.artworks", []),
                        ),
                    ];
                }

                $order->update(['total_amount' => round($totalAmount, 2)]);

                return $order;
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('local')->delete($storedPaths);
            }

            throw $exception;
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order created.');
    }

    public function show(Order $order): View
    {
        $order->load([
            'customer',
            'items.material',
            'items.artworks',
            'stockMovements.material',
            'stockMovements.inkStock',
        ]);

        return view('orders.show', compact('order'));
    }

    public function update(Request $request, Order $order, CompletedOrderSaleService $completedOrderSaleService): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_production,ready,completed'],
            'amount_paid' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
                function (string $attribute, mixed $value, \Closure $fail) use ($order): void {
                    if ((float) $value > (float) $order->total_amount) {
                        $fail('The amount paid cannot exceed the order total.');
                    }
                },
            ],
            'payment_method' => [
                Rule::requiredIf((float) $request->input('amount_paid', 0) > 0),
                'nullable',
                'in:cash,mpesa,bank',
            ],
            'bank_name' => ['nullable', 'required_if:payment_method,bank', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($order, $validated, $completedOrderSaleService): void {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lockedOrder->update([
                'status' => $validated['status'],
                'amount_paid' => $validated['amount_paid'],
                'payment_method' => $validated['payment_method'] ?? null,
                'bank_name' => ($validated['payment_method'] ?? null) === 'bank'
                    ? ($validated['bank_name'] ?? null)
                    : null,
            ]);

            if ($validated['status'] === 'completed') {
                $completedOrderSaleService->createSale($lockedOrder);
            }
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order updated.');
    }

    public function artwork(OrderArtwork $artwork): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($artwork->file_path), 404);

        return Storage::disk('local')->download($artwork->file_path);
    }
}
