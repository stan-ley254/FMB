<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->withCount('orders')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create(): View
    {
        return view('customers.create', ['customer' => new Customer]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create($this->validatedCustomer($request));

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'orders' => fn ($query) => $query->latest('created_at')->with([
                'items.material',
                'items.artworks',
            ]),
        ]);

        $itemTotals = OrderItem::query()
            ->whereHas('order', fn ($query) => $query->where('customer_id', $customer->id))
            ->select('item_type', 'catalog_item_unit')
            ->selectRaw('SUM(quantity_or_meters) AS total_quantity')
            ->groupBy('item_type', 'catalog_item_unit')
            ->orderBy('item_type')
            ->get()
            ->map(fn (OrderItem $item): array => [
                'item_type' => $item->item_type,
                'label' => CatalogItem::ITEM_TYPES[$item->item_type]
                    ?? str($item->item_type)->replace('_', ' ')->title()->toString(),
                'quantity' => (float) $item->total_quantity,
                'unit' => $item->catalog_item_unit ?? ($item->item_type === 'dtf_garment' ? 'piece' : 'meter'),
            ]);

        return view('customers.show', compact('customer', 'itemTotals'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validatedCustomer($request));

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    /**
     * @return array{name: string, phone: ?string, email: ?string, notes: ?string, is_walk_in: bool}
     */
    private function validatedCustomer(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_walk_in' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_walk_in' => (bool) ($validated['is_walk_in'] ?? false),
        ];
    }
}
