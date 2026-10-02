<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('item_type')->change();
            $table->foreignId('catalog_item_id')->nullable()->after('material_id')->constrained()->restrictOnDelete();
            $table->string('catalog_item_name')->nullable()->after('catalog_item_id');
            $table->string('catalog_item_unit', 20)->nullable()->after('catalog_item_name');
        });
    }

    public function down(): void
    {
        if (DB::table('order_items')->where('item_type', 'dtf_print')->exists()) {
            throw new RuntimeException('Cannot roll back catalog mapping while DTF print order items exist.');
        }

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['catalog_item_id']);
            $table->dropColumn(['catalog_item_id', 'catalog_item_name', 'catalog_item_unit']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->enum('item_type', ['banner', 'sertine', 'sticker', 'dtf_garment'])->change();
        });
    }
};
