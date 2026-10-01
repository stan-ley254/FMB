<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('ink_stocks', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('ink_stocks', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
