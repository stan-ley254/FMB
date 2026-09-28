<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('category', ['banner', 'sertine', 'sticker', 'dtf-consumable', 'garment']);
            $table->string('unit', 20);
            $table->decimal('quantity_remaining', 12, 0)->default(0);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->enum('machine', ['large_format', 'dtf'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
