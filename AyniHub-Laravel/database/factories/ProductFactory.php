<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id'       => User::factory()->productor(),
            'category'      => 'agricola',
            'name'          => 'Papa nativa',
            'producer_name' => 'Familia Quispe',
            'price'         => 1.80,
            'unit'          => 'kilo',
            'location'      => 'Feria dominical',
            'description'   => 'Producto de prueba.',
        ];
    }
}
