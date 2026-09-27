<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['banner', 'sertine', 'sticker', 'dtf_garment']);
            $table->foreignId('material_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('quantity_or_meters', 12, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->nullable();
            $table->boolean('garment_sourced_by_shop')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
            $table->index('item_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
