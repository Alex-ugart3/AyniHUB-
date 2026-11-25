<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pedido de un comprador a un productor.
 */
class Order extends Model
{
    use HasFactory;

    /** Estados posibles del pedido, en orden de avance. */
    public const STATUSES = ['pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado'];

    /** Estados finales: ya no se pueden modificar (salvo por el gestor). */
    public const FINAL = ['entregado', 'cancelado'];

    protected $fillable = [
        'user_id', 'product_id', 'producer_id', 'product_name', 'unit',
        'unit_price', 'quantity', 'total', 'status', 'note',
    ];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'total' => 'decimal:2', 'quantity' => 'integer'];
    }

    /** Comprador. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Productor que recibe el pedido. */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'producer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
