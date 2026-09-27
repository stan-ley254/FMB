<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['ink', 'powder', 'banner_material', 'tshirts', 'other']);
            $table->string('supplier_name')->nullable();
            $table->string('item_description');
            $table->string('quantity_or_size')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'mpesa', 'bank']);
            $table->string('bank_name')->nullable();
            $table->boolean('adds_to_stock')->default(false);
            $table->foreignId('related_material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('related_ink_stock_id')->nullable()->constrained('ink_stocks')->nullOnDelete();
            $table->timestamps();
            $table->index(['category', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
