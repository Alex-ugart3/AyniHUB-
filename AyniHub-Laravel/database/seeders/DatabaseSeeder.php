<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostración: usuarios (uno o más por rol), productos y pedidos de
 * los últimos 14 días para que el dashboard tenga información.
 * Contraseña de todos los usuarios demo: Ayni2026
 * Se puede ejecutar varias veces sin duplicar datos.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $gestor = $this->user('Gestor AyniHub', 'gestor@aynihub.pe', 'gestor', 'Chinchero, Cusco');
        $quispe = $this->user('Familia Quispe', 'productor@aynihub.pe', 'productor', 'Feria dominical, puesto 4');
        $sara   = $this->user('Asociación Sara Ayllu', 'saraayllu@aynihub.pe', 'productor', 'Comunidad de Ayarmaca');
        $compradores = [
            $this->user('Comprador Demo', 'comprador@aynihub.pe', 'comprador'),
            $this->user('Ana Torres', 'ana@aynihub.pe', 'comprador'),
            $this->user('Luis Ccori', 'luis@aynihub.pe', 'comprador'),
        ];

        $catalogo = [
            ['agricola', 'Papa nativa', 'Familia Quispe', 1.80, 'kilo', 'Feria dominical, puesto 4', 'Papa nativa cultivada en los Andes de Cusco.', 'images/papa1.jpg', $quispe],
            ['agricola', 'Yuca', 'Asociación Sara Ayllu', 3.00, 'kilo', 'Comunidad de Ayarmaca', 'Raíz alimenticia para preparar sancochada o frita.', 'images/yuca1.jpg', $sara],
            ['agricola', 'Olluco', 'Familia Mamani', 2.30, 'kilo', 'Feria dominical, puesto 9', 'Tubérculo andino cultivado en los pisos altos.', 'images/olluco2.jpg', $quispe],
            ['agricola', 'Oca', 'Centro Comunal de Productores', 2.50, 'kilo', 'Ruta Chinchero - Maras, parador km 3', 'Tubérculo andino de sabor ligeramente dulce.', 'images/ocas1.jpg', $sara],
            ['agricola', 'Habas', 'Familia Huamán', 2.00, 'kilo', 'Feria dominical, puesto 12', 'Leguminosa tradicional de la agricultura andina.', 'images/habas.jpg', $quispe],
        ];

        $productos = [];
        foreach ($catalogo as [$cat, $name, $prod, $price, $unit, $loc, $desc, $img, $owner]) {
            $p = Product::firstOrCreate(['name' => $name], [
                'category' => $cat, 'producer_name' => $prod, 'price' => $price, 'unit' => $unit,
                'location' => $loc, 'description' => $desc, 'image' => $img, 'user_id' => $owner->id,
            ]);
            if ($p->user_id === null) { // productos de una versión anterior sin dueño
                $p->update(['user_id' => $owner->id]);
            }
            $productos[] = $p;
        }

        // Pedidos de ejemplo (solo si aún no hay ninguno)
        if (Order::count() === 0) {
            $estados = ['entregado', 'entregado', 'enviado', 'confirmado', 'pendiente', 'entregado', 'cancelado', 'pendiente', 'entregado'];
            for ($i = 0; $i < 22; $i++) {
                $p = $productos[$i % count($productos)];
                $qty = 1 + ($i * 3) % 7;
                $order = new Order([
                    'user_id'      => $compradores[$i % 3]->id,
                    'product_id'   => $p->id,
                    'producer_id'  => $p->user_id,
                    'product_name' => $p->name,
                    'unit'         => $p->unit,
                    'unit_price'   => $p->price,
                    'quantity'     => $qty,
                    'total'        => round($p->price * $qty, 2),
                    'status'       => $estados[$i % count($estados)],
                ]);
                $order->created_at = now()->subDays(13 - intdiv($i * 13, 21))->subHours($i % 9);
                $order->save();
            }
        }
    }

    private function user(string $name, string $contact, string $role, ?string $location = null): User
    {
        return User::firstOrCreate(['contact' => $contact], [
            'name' => $name, 'email' => $contact, 'password' => Hash::make('Ayni2026'),
            'role' => $role, 'location' => $location,
        ]);
    }
}
