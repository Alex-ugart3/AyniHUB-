<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Reglas de autorización de productos:
 * - gestor: administra todos.
 * - productor: solo crea y administra los suyos.
 * - comprador: solo consulta.
 */
class ProductPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, ['productor', 'gestor'], true);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->role === 'gestor'
            || ($user->role === 'productor' && $product->user_id === $user->id);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
