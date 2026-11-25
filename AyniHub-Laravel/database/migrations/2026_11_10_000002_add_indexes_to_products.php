<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Índice compuesto para el catálogo: filtro por categoría + orden por fecha. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['category', 'created_at'], 'products_category_created_idx');
            $table->index('user_id', 'products_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_created_idx');
            $table->dropIndex('products_user_idx');
        });
    }
};
