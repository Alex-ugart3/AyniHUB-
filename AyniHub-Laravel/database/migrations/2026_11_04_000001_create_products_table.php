<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tabla de productos publicados por los productores. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('category', ['agricola', 'artesanal'])->index(); // índice: filtro del catálogo
            $table->string('name');
            $table->string('producer_name');
            $table->decimal('price', 10, 2);
            $table->enum('unit', ['kilo', 'unidad', 'arroba'])->default('kilo');
            $table->string('location');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
