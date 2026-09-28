<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ink_stocks', function (Blueprint $table) {
            $table->id();
            $table->enum('machine', ['large_format', 'dtf']);
            $table->enum('color', ['cyan', 'magenta', 'yellow', 'black', 'white']);
            $table->string('unit', 20)->default('bottles');
            $table->decimal('quantity_remaining', 12, 0)->default(0);
            $table->timestamps();
            $table->unique(['machine', 'color']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ink_stocks');
    }
};
