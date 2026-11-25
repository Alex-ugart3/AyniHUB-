<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedidos de compradores a productores.
 * Guarda una "foto" del producto (nombre, unidad, precio) para que el historial
 * no cambie si el productor edita o elimina el producto después.
 * Los índices compuestos aceleran las consultas del panel (por rol y estado).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            return; // ya existe (por ejemplo, creada en una versión anterior)
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();           // comprador
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('producer_id')->nullable()->constrained('users')->nullOnDelete(); // productor
            $table->string('product_name');
            $table->string('unit', 20);
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('total', 12, 2);
            $table->enum('status', ['pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado'])->default('pendiente');
            $table->string('note')->nullable();
            $table->timestamps();

            // Índices para las consultas más frecuentes del panel y el dashboard
            $table->index(['user_id', 'status']);
            $table->index(['producer_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
