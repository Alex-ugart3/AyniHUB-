<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $product = Product::factory()->create();

        return [
            'user_id'      => User::factory(),
            'product_id'   => $product->id,
            'producer_id'  => $product->user_id,
            'product_name' => $product->name,
            'unit'         => $product->unit,
            'unit_price'   => $product->price,
            'quantity'     => 2,
            'total'        => round($product->price * 2, 2),
            'status'       => 'pendiente',
        ];
    }
}
