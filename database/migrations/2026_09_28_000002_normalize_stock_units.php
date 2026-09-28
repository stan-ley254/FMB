<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ink_stocks', 'unit')) {
            Schema::table('ink_stocks', function (Blueprint $table): void {
                $table->string('unit', 20)->default('bottles')->after('color');
            });
        }

        DB::table('materials')->update([
            'quantity_remaining' => DB::raw('ROUND(quantity_remaining)'),
        ]);
        DB::table('ink_stocks')->update([
            'quantity_remaining' => DB::raw('ROUND(quantity_remaining)'),
            'unit' => 'bottles',
        ]);
    }

    public function down(): void
    {
        Schema::table('ink_stocks', function (Blueprint $table): void {
            $table->dropColumn('unit');
        });
    }
};
