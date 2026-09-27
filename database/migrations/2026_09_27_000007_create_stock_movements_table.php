<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('ink_stock_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('type', ['purchase', 'usage']);
            $table->decimal('quantity', 12, 3);
            $table->foreignId('related_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['type', 'created_at']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
