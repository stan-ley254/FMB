<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $salesQuery = $this->salesQuery($filters)->with(['customer:id,name']);
        $usageSummary = $this->usageSummary($filters);
        $sales = $salesQuery
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', compact('sales', 'filters', 'usageSummary'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $sales = $this->salesQuery($filters)->with(['customer:id,name']);

        return response()->streamDownload(function () use ($sales): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                throw new \RuntimeException('Unable to open the CSV output stream.');
            }

            try {
                fputcsv($output, [
                    'Completed at',
                    'Order',
                    'Customer',
                    'Total amount',
                    'Amount paid',
                    'Payment method',
                    'Bank name',
                    'Items',
                ], ',', '"', '');

                foreach ($sales->lazy(200) as $sale) {
                    fputcsv($output, [
                        $sale->completed_at->format('Y-m-d H:i:s'),
                        $sale->order_id,
                        $sale->customer->name,
                        $sale->total_amount,
                        $sale->amount_paid,
                        $sale->payment_method ?? '',
                        $sale->payment_method === 'bank' ? ($sale->bank_name ?? '') : '',
                        $sale->itemsSummary(),
                    ], ',', '"', '');
                }
            } finally {
                fclose($output);
            }
        }, 'sales.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{from?: string|null, to?: string|null, customer?: string|null, material?: string|null, item_type?: string|null}
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'customer' => ['nullable', 'string', 'max:255'],
            'material' => ['nullable', 'string', 'max:255'],
            'item_type' => ['nullable', Rule::in(['banner', 'sertine', 'sticker', 'dtf_garment', 'dtf_print'])],
        ]);
    }

    /**
     * @param  array{from?: string|null, to?: string|null, customer?: string|null, material?: string|null, item_type?: string|null}  $filters
     * @return Builder<Sale>
     */
    private function salesQuery(array $filters): Builder
    {
        $query = Sale::query();

        if (($filters['from'] ?? null) !== null) {
            $query->whereDate('completed_at', '>=', $filters['from']);
        }

        if (($filters['to'] ?? null) !== null) {
            $query->whereDate('completed_at', '<=', $filters['to']);
        }

        if (($filters['customer'] ?? null) !== null) {
            $customer = $filters['customer'];
            $query->whereHas('customer', fn (Builder $customerQuery) => $customerQuery
                ->where('name', 'like', "%{$customer}%"));
        }

        if (($filters['material'] ?? null) !== null || ($filters['item_type'] ?? null) !== null) {
            $salesTable = DB::connection()->getQueryGrammar()->wrapTable((new Sale)->getTable());
            $query->whereExists(function (QueryBuilder $snapshotQuery) use ($filters, $salesTable): void {
                $snapshotQuery->selectRaw('1');

                if (DB::connection()->getDriverName() === 'sqlite') {
                    $snapshotQuery->fromRaw("json_each({$salesTable}.items_snapshot) AS snapshot_item");

                    if (($filters['material'] ?? null) !== null) {
                        $snapshotQuery->whereRaw(
                            "json_extract(snapshot_item.value, '$.material_name') LIKE ?",
                            ['%'.$filters['material'].'%'],
                        );
                    }

                    if (($filters['item_type'] ?? null) !== null) {
                        $snapshotQuery->whereRaw('json_extract(snapshot_item.value, \'$.item_type\') = ?', [
                            $filters['item_type'],
                        ]);
                    }

                    return;
                }

                if (DB::connection()->getDriverName() === 'mysql') {
                    $snapshotQuery->fromRaw(
                        "JSON_TABLE({$salesTable}.items_snapshot, '$[*]' COLUMNS (material_name VARCHAR(255) PATH '$.material_name', item_type VARCHAR(32) PATH '$.item_type')) AS snapshot_item",
                    );

                    if (($filters['material'] ?? null) !== null) {
                        $snapshotQuery->where('snapshot_item.material_name', 'like', '%'.$filters['material'].'%');
                    }

                    if (($filters['item_type'] ?? null) !== null) {
                        $snapshotQuery->where('snapshot_item.item_type', $filters['item_type']);
                    }

                    return;
                }

                throw new \RuntimeException('Sales snapshot filtering is not supported by this database driver.');
            });
        }

        return $query->latest('completed_at')->latest('id');
    }

    /**
     * @param  array{from?: string|null, to?: string|null, customer?: string|null, material?: string|null, item_type?: string|null}  $filters
     * @return array<int, array{item_type: string, name: string, quantity: float, unit: string}>
     */
    private function usageSummary(array $filters): array
    {
        $usage = [];

        foreach ($this->salesQuery($filters)->lazy(200) as $sale) {
            foreach ($sale->items_snapshot ?? [] as $item) {
                $itemType = (string) ($item['item_type'] ?? '');
                $materialName = trim((string) ($item['material_name'] ?? ''));

                if (($filters['material'] ?? null) !== null
                    && stripos($materialName, $filters['material']) === false) {
                    continue;
                }

                if (($filters['item_type'] ?? null) !== null
                    && $itemType !== $filters['item_type']) {
                    continue;
                }

                $catalogItemName = trim((string) ($item['catalog_item_name'] ?? ''));
                $name = $materialName !== ''
                    ? $materialName
                    : ($catalogItemName !== '' ? $catalogItemName : $itemType);
                $unit = $item['unit'] ?? ($itemType === 'dtf_garment' ? 'piece' : 'meter');
                $key = implode('|', [$itemType, $name, $unit]);

                if (! isset($usage[$key])) {
                    $usage[$key] = [
                        'item_type' => $itemType,
                        'name' => $name,
                        'quantity' => 0.0,
                        'unit' => $unit,
                    ];
                }

                $usage[$key]['quantity'] += (float) ($item['quantity'] ?? 0);
            }
        }

        $summary = array_values($usage);
        usort($summary, fn (array $left, array $right): int => [
            $left['item_type'],
            $left['name'],
        ] <=> [
            $right['item_type'],
            $right['name'],
        ]);

        return $summary;
    }
}
