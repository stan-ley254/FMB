<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\InkStock;
use App\Models\Material;
use App\Services\StockPurchaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    private const CATEGORIES = [
        'ink' => 'Ink',
        'powder' => 'Powder',
        'banner_material' => 'Banner material',
        'tshirts' => 'T-shirts',
        'other' => 'Other',
    ];

    private const BANKS = [
        'Absa Bank',
        'Access Bank',
        'African Bank',
        'Bank of Africa',
        'Citibank',
        'Co-operative Bank',
        'Ecobank',
        'Equity Bank',
        'First National Bank',
        'Guaranty Trust Bank',
        'KCB Bank',
        'NCBA Bank',
        'Standard Chartered Bank',
        'Stanbic Bank',
        'Other',
    ];

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $expenses = $this->expensesQuery($filters)
            ->paginate(20)
            ->withQueryString();

        return view('expenses.index', [
            'expenses' => $expenses,
            'filters' => $filters,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $expenses = $this->expensesQuery($filters);

        return response()->streamDownload(function () use ($expenses): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                throw new \RuntimeException('Unable to open the CSV output stream.');
            }

            try {
                fputcsv($output, [
                    'Date',
                    'Category',
                    'Supplier',
                    'Item description',
                    'Quantity or size',
                    'Amount',
                    'Payment method',
                    'Bank name',
                    'Adds to stock',
                    'Stock item',
                ], ',', '"', '');

                foreach ($expenses->lazy(200) as $expense) {
                    fputcsv($output, [
                        $expense->created_at->format('Y-m-d H:i:s'),
                        $expense->category,
                        $expense->supplier_name ?? '',
                        $expense->item_description,
                        $expense->quantity_or_size ?? '',
                        $expense->amount,
                        $expense->payment_method,
                        $expense->payment_method === 'bank' ? ($expense->bank_name ?? '') : '',
                        $expense->adds_to_stock ? 'Yes' : 'No',
                        $this->stockItemName($expense),
                    ], ',', '"', '');
                }
            } finally {
                fclose($output);
            }
        }, 'expenses.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(): View
    {
        return view('expenses.create', [
            ...$this->formData(),
            'expense' => null,
        ]);
    }

    public function store(Request $request, StockPurchaseService $stockPurchaseService): RedirectResponse
    {
        $validated = $this->validatedExpense($request, creating: true);
        $addsToStock = (bool) ($validated['adds_to_stock'] ?? false);

        $expense = DB::transaction(function () use ($validated, $addsToStock, $stockPurchaseService): Expense {
            $expense = Expense::create([
                'category' => $validated['category'],
                'supplier_name' => $validated['supplier_name'] ?? null,
                'item_description' => $validated['item_description'],
                'quantity_or_size' => $validated['quantity_or_size'] ?? null,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['payment_method'] === 'bank' ? $validated['bank_name'] : null,
                'adds_to_stock' => $addsToStock,
                'related_material_id' => $addsToStock && $validated['stock_type'] === 'material'
                    ? $validated['stock_item_id']
                    : null,
                'related_ink_stock_id' => $addsToStock && $validated['stock_type'] === 'ink'
                    ? $validated['stock_item_id']
                    : null,
            ]);

            if ($addsToStock) {
                $notes = "Expense #{$expense->id}: {$expense->item_description}";

                if ($validated['stock_type'] === 'material') {
                    $stockPurchaseService->addMaterial(
                        Material::findOrFail($validated['stock_item_id']),
                        (int) $validated['quantity_or_size'],
                        $notes,
                    );
                } else {
                    $stockPurchaseService->addInk(
                        InkStock::findOrFail($validated['stock_item_id']),
                        (int) $validated['quantity_or_size'],
                        $notes,
                    );
                }
            }

            return $expense;
        });

        return redirect()->route('expenses.edit', $expense)->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense): View
    {
        return view('expenses.edit', [
            ...$this->formData(),
            'expense' => $expense->load(['relatedMaterial', 'relatedInkStock']),
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $this->validatedExpense($request, creating: false);

        $expense->update([
            'category' => $validated['category'],
            'supplier_name' => $validated['supplier_name'] ?? null,
            'item_description' => $validated['item_description'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'bank_name' => $validated['payment_method'] === 'bank' ? $validated['bank_name'] : null,
        ]);

        return redirect()->route('expenses.edit', $expense)->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense, StockPurchaseService $stockPurchaseService): RedirectResponse
    {
        DB::transaction(function () use ($expense, $stockPurchaseService): void {
            $lockedExpense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

            if ($lockedExpense->adds_to_stock) {
                $stockPurchaseService->reverseExpensePurchase($lockedExpense);
            }

            $lockedExpense->delete();
        });

        return redirect()->route('expenses.index')->with('success', 'Expense deleted and linked stock reversed.');
    }

    /**
     * @return array{categories: array<string, string>, banks: array<int, string>, materials: Collection<int, Material>, inkStocks: Collection<int, InkStock>}
     */
    private function formData(): array
    {
        return [
            'categories' => self::CATEGORIES,
            'banks' => self::BANKS,
            'materials' => Material::where('is_active', true)->orderBy('name')->get(),
            'inkStocks' => InkStock::where('is_active', true)->orderBy('machine')->orderBy('color')->get(),
        ];
    }

    /**
     * @return array{from?: string|null, to?: string|null, category?: string|null, supplier?: string|null}
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'category' => ['nullable', Rule::in(array_keys(self::CATEGORIES))],
            'supplier' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @param  array{from?: string|null, to?: string|null, category?: string|null, supplier?: string|null}  $filters
     * @return Builder<Expense>
     */
    private function expensesQuery(array $filters): Builder
    {
        return Expense::query()
            ->with(['relatedMaterial:id,name', 'relatedInkStock:id,machine,color'])
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['supplier'] ?? null, fn (Builder $query, string $supplier) => $query->where('supplier_name', 'like', "%{$supplier}%"))
            ->latest('created_at')
            ->latest('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedExpense(Request $request, bool $creating): array
    {
        $addsToStock = $creating && $request->boolean('adds_to_stock');
        $stockType = $request->input('stock_type');
        $stockIdRules = match ($stockType) {
            'material' => [Rule::exists('materials', 'id')->where('is_active', true)],
            'ink' => [Rule::exists('ink_stocks', 'id')->where('is_active', true)],
            default => [],
        };

        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'item_description' => ['required', 'string', 'max:255'],
            'quantity_or_size' => $creating
                ? [
                    Rule::requiredIf($addsToStock),
                    'nullable',
                    'string',
                    'max:255',
                    ...($addsToStock ? ['numeric', 'integer', 'gt:0', 'max:999999'] : []),
                ]
                : ['prohibited'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'payment_method' => ['required', Rule::in(['cash', 'mpesa', 'bank'])],
            'bank_name' => [
                Rule::requiredIf($request->input('payment_method') === 'bank'),
                'nullable',
                Rule::in(self::BANKS),
            ],
            'adds_to_stock' => $creating ? ['sometimes', 'boolean'] : ['prohibited'],
            'stock_type' => $creating
                ? [Rule::requiredIf($addsToStock), 'nullable', Rule::in(['material', 'ink'])]
                : ['prohibited'],
            'stock_item_id' => $creating
                ? [Rule::requiredIf($addsToStock), 'nullable', 'integer', ...$stockIdRules]
                : ['prohibited'],
        ]);

        return $validated;
    }

    private function stockItemName(Expense $expense): string
    {
        if (! $expense->adds_to_stock) {
            return '';
        }

        if ($expense->relatedMaterial !== null) {
            return $expense->relatedMaterial->name;
        }

        if ($expense->relatedInkStock !== null) {
            $machine = $expense->relatedInkStock->machine === 'dtf' ? 'DTF Printer' : 'Large Format';

            return $machine.' '.str($expense->relatedInkStock->color)->title();
        }

        return '';
    }
}
