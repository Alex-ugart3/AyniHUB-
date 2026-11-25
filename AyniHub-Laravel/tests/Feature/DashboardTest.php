<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Dashboard por rol y optimización de consultas (sin N+1). */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_see_dashboard(): void
    {
        $this->getJson('/panel/dashboard')->assertUnauthorized();
    }

    public function test_each_role_gets_its_own_numbers(): void
    {
        $productor = User::factory()->productor()->create();
        $producto = Product::factory()->create(['user_id' => $productor->id, 'price' => 2]);
        $ana = User::factory()->create();
        $luis = User::factory()->create();

        foreach ([[$ana, 'entregado', 3], [$luis, 'pendiente', 5], [$luis, 'cancelado', 9]] as [$comprador, $estado, $cant]) {
            Order::create([
                'user_id' => $comprador->id, 'product_id' => $producto->id, 'producer_id' => $productor->id,
                'product_name' => $producto->name, 'unit' => 'kilo', 'unit_price' => 2, 'quantity' => $cant,
                'total' => 2 * $cant, 'status' => $estado,
            ]);
        }

        // Gestor: ve todo. Ventas = 6 + 10 (el cancelado no cuenta)
        $r = $this->actingAs(User::factory()->gestor()->create())->getJson('/panel/dashboard')->assertOk()
            ->assertJsonPath('kpi.orders', 3)->assertJsonPath('kpi.pending', 1)
            ->assertJsonCount(14, 'sales_daily')->assertJsonStructure(['kpi', 'by_status', 'sales_daily', 'top_products', 'by_category']);
        $this->assertEquals(16, $r->json('kpi.sales'));

        // Productor: solo sus pedidos recibidos
        $this->actingAs($productor)->getJson('/panel/dashboard')->assertOk()->assertJsonPath('kpi.orders', 3)->assertJsonPath('kpi.products', 1);
        $this->actingAs(User::factory()->productor()->create())->getJson('/panel/dashboard')->assertOk()->assertJsonPath('kpi.orders', 0);

        // Comprador: solo sus compras (ana: 1 pedido de 6)
        $r = $this->actingAs($ana)->getJson('/panel/dashboard')->assertOk()->assertJsonPath('kpi.orders', 1);
        $this->assertEquals(6, $r->json('kpi.sales'));
    }

    /** Evidencia de optimización: el número de consultas NO crece con la cantidad de pedidos. */
    public function test_orders_list_has_no_n_plus_one(): void
    {
        $gestor = User::factory()->gestor()->create();

        $contar = function () use ($gestor): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($gestor)->getJson('/pedidos')->assertOk();
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        Order::factory()->count(2)->create();
        $con2 = $contar();

        Order::factory()->count(20)->create();
        $con22 = $contar();

        $this->assertSame($con2, $con22, "Se detectó N+1: $con2 consultas con 2 pedidos vs $con22 con 22.");
    }

    public function test_users_list_uses_withcount_without_n_plus_one(): void
    {
        $gestor = User::factory()->gestor()->create();
        User::factory()->count(3)->create();

        DB::enableQueryLog();
        $this->actingAs($gestor)->getJson('/panel/usuarios')->assertOk()->assertJsonStructure([['orders_count', 'products_count']]);
        $consultas = collect(DB::getQueryLog())->filter(fn ($q) => str_starts_with(strtolower($q['query']), 'select'))->count();

        $this->assertLessThanOrEqual(3, $consultas); // sesión/usuario autenticado + lista con conteos
    }
}
