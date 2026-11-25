<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Quién puede cambiar el estado de un pedido:
 * - gestor: cualquiera, a cualquier estado.
 * - productor: solo los pedidos de SUS productos y mientras no sean finales.
 * - comprador: solo cancelar SU pedido mientras esté pendiente.
 */
class OrderPolicy
{
    public function changeStatus(User $user, Order $order, string $newStatus): bool
    {
        if ($user->role === 'gestor') {
            return true;
        }

        if (in_array($order->status, Order::FINAL, true)) {
            return false;
        }

        if ($user->role === 'productor') {
            return $order->producer_id === $user->id
                && in_array($newStatus, ['confirmado', 'enviado', 'entregado', 'cancelado'], true);
        }

        // comprador
        return $order->user_id === $user->id
            && $order->status === 'pendiente'
            && $newStatus === 'cancelado';
    }
}
