<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $sales = $this->salesQuery($filters)
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', compact('sales', 'filters'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $sales = $this->salesQuery($filters);

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
            'item_type' => ['nullable', Rule::in(['banner', 'sertine', 'sticker', 'dtf_garment'])],
        ]);
    }

    /**
     * @param  array{from?: string|null, to?: string|null, customer?: string|null, material?: string|null, item_type?: string|null}  $filters
     * @return Builder<Sale>
     */
    private function salesQuery(array $filters): Builder
    {
        return Sale::query()
            ->with(['customer:id,name'])
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('completed_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('completed_at', '<=', $to))
            ->when($filters['customer'] ?? null, function (Builder $query, string $customer): void {
                $query->whereHas('customer', fn (Builder $customerQuery) => $customerQuery
                    ->where('name', 'like', "%{$customer}%"));
            })
            ->when($filters['material'] ?? null, fn (Builder $query, string $material) => $query->whereRaw('CAST(items_snapshot AS CHAR) LIKE ?', ["%{$material}%"]))
            ->when($filters['item_type'] ?? null, fn (Builder $query, string $itemType) => $query->whereRaw(
                'CAST(items_snapshot AS CHAR) LIKE ?',
                ['%"item_type":"'.$itemType.'"%'],
            ))
            ->latest('completed_at')
            ->latest('id');
    }
}
