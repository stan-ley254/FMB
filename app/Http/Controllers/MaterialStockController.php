<?php

namespace App\Http\Controllers;

use App\Models\InkStock;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaterialStockController extends Controller
{
    public function index(): View
    {
        return view('stock.index', [
            'materialsByCategory' => Material::orderBy('name')->get()->groupBy('category'),
            'inkStocksByMachine' => InkStock::orderBy('machine')->orderBy('color')->get()->groupBy('machine'),
            'categories' => [
                'banner' => 'Banner widths',
                'sertine' => 'Sertine',
                'sticker' => 'Stickers',
                'dtf-consumable' => 'DTF consumables',
                'garment' => 'Blank garments',
            ],
        ]);
    }

    public function addMaterial(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999.999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($material, $validated): void {
            $lockedMaterial = Material::whereKey($material->id)->lockForUpdate()->firstOrFail();
            $lockedMaterial->increment('quantity_remaining', $validated['quantity']);

            $lockedMaterial->stockMovements()->create([
                'type' => 'purchase',
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return back()->with('success', "Stock added to {$material->name}.");
    }

    public function addInk(Request $request, InkStock $inkStock): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999.999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($inkStock, $validated): void {
            $lockedInkStock = InkStock::whereKey($inkStock->id)->lockForUpdate()->firstOrFail();
            $lockedInkStock->increment('quantity_remaining', $validated['quantity']);

            $lockedInkStock->stockMovements()->create([
                'type' => 'purchase',
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Ink stock added.');
    }
}
