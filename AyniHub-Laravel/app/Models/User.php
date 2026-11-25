<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario del sistema. Roles: comprador, productor, gestor.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** El rol no es peligroso aquí porque los requests validados no permiten "gestor". */
    protected $fillable = ['name', 'email', 'contact', 'password', 'role', 'location'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    /** Productos publicados (si es productor). */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Pedidos realizados (si es comprador). */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Pedidos recibidos (si es productor). */
    public function receivedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'producer_id');
    }

    /** Correos en minúscula; celulares sin espacios laterales. */
    public static function normalizeContact(string $contact): string
    {
        $contact = trim($contact);

        return filter_var($contact, FILTER_VALIDATE_EMAIL) ? strtolower($contact) : $contact;
    }

    /** Datos que se pueden enviar al navegador (sin contraseña). */
    public function toPublicArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'role' => $this->role, 'contact' => $this->contact, 'location' => $this->location];
    }
}
