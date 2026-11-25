<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pruebas de autenticación, autorización por rol y CRUD de productos. */
class AyniHubTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $over = []): array
    {
        return array_merge([
            'category' => 'agricola', 'name' => 'Oca', 'producer_name' => 'Familia Mamani',
            'price' => 2.5, 'unit' => 'kilo', 'location' => 'Feria dominical',
        ], $over);
    }

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('Ayni Hub');
    }

    public function test_register_comprador_and_login(): void
    {
        $this->postJson('/registro', ['name' => 'Ana Torres', 'contact' => 'ana@mail.com', 'password' => 'Clave1234', 'role' => 'comprador'])
            ->assertCreated();
        $this->assertAuthenticated();
    }

    public function test_cannot_self_register_as_gestor(): void
    {
        $this->postJson('/registro', ['name' => 'Hacker', 'contact' => 'h@mail.com', 'password' => 'Clave1234', 'role' => 'gestor', 'location' => 'x'])
            ->assertStatus(422);
        $this->assertDatabaseMissing('users', ['contact' => 'h@mail.com']);
    }

    public function test_weak_password_rejected(): void
    {
        $this->postJson('/registro', ['name' => 'Ana Torres', 'contact' => 'ana@mail.com', 'password' => 'sololetras', 'role' => 'comprador'])
            ->assertStatus(422);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['contact' => 'a@mail.com']);
        $this->postJson('/login', ['contact' => 'a@mail.com', 'password' => 'incorrecta1', 'role' => 'comprador'])
            ->assertStatus(422);
    }

    public function test_login_ok_with_correct_role(): void
    {
        User::factory()->productor()->create(['contact' => 'p@mail.com']);
        $this->postJson('/login', ['contact' => 'P@mail.com', 'password' => 'Clave1234', 'role' => 'productor'])
            ->assertOk()->assertJsonPath('user.role', 'productor')->assertJsonPath('redirect', '#Catalogo');
        $this->assertAuthenticated();
    }

    public function test_login_fails_when_role_does_not_match(): void
    {
        User::factory()->create(['contact' => 'c@mail.com']); // comprador
        $this->postJson('/login', ['contact' => 'c@mail.com', 'password' => 'Clave1234', 'role' => 'gestor'])
            ->assertStatus(422)->assertJsonValidationErrors(['role']);
        $this->assertGuest();
    }

    public function test_login_with_unknown_user_gives_generic_error(): void
    {
        $this->postJson('/login', ['contact' => 'nadie@mail.com', 'password' => 'Clave1234', 'role' => 'comprador'])
            ->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['contact' => 'r@mail.com']);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', ['contact' => 'r@mail.com', 'password' => 'mala1234', 'role' => 'comprador'])->assertStatus(422);
        }
        $this->postJson('/login', ['contact' => 'r@mail.com', 'password' => 'mala1234', 'role' => 'comprador'])->assertStatus(429);
    }

    public function test_seeded_users_can_login_with_their_own_password(): void
    {
        $this->seed();
        foreach ([['gestor', 'Gestor#2026'], ['productor', 'Productor#2026'], ['comprador', 'Comprador#2026']] as [$rol, $clave]) {
            $this->postJson('/login', ['contact' => "$rol@aynihub.pe", 'password' => $clave, 'role' => $rol])->assertOk();
            $this->postJson('/logout')->assertOk();
        }
    }

    public function test_guest_cannot_create_product(): void
    {
        $this->postJson('/productos', $this->payload())->assertUnauthorized();
    }

    public function test_comprador_cannot_create_product(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/productos', $this->payload())->assertForbidden();
    }

    public function test_productor_crud_own_product(): void
    {
        $productor = User::factory()->productor()->create();
        $this->actingAs($productor);

        $id = $this->postJson('/productos', $this->payload())->assertCreated()->json('id');
        $this->getJson("/productos/$id")->assertOk()->assertJsonPath('name', 'Oca');
        $this->putJson("/productos/$id", $this->payload(['name' => 'Oca morada']))->assertOk();
        $this->assertDatabaseHas('products', ['id' => $id, 'name' => 'Oca morada']);
        $this->deleteJson("/productos/$id")->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $id]);
    }

    public function test_productor_cannot_edit_other_product(): void
    {
        $product = Product::factory()->create();
        $other = User::factory()->productor()->create();
        $this->actingAs($other)->putJson("/productos/{$product->id}", $this->payload())->assertForbidden();
        $this->actingAs($other)->deleteJson("/productos/{$product->id}")->assertForbidden();
    }

    public function test_gestor_can_manage_any_product(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->gestor()->create())
            ->deleteJson("/productos/{$product->id}")->assertOk();
    }

    public function test_invalid_product_data_rejected(): void
    {
        $this->actingAs(User::factory()->productor()->create())
            ->postJson('/productos', $this->payload(['price' => -5, 'category' => 'otra']))
            ->assertStatus(422)->assertJsonValidationErrors(['price', 'category']);
    }

    public function test_xss_payload_is_escaped_in_html(): void
    {
        Product::factory()->create(['name' => '<script>alert(1)</script>']);
        $this->get('/')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_category_filter(): void
    {
        Product::factory()->create(['category' => 'artesanal', 'name' => 'Chalina']);
        Product::factory()->create(['category' => 'agricola', 'name' => 'Papa']);
        $this->getJson('/productos?category=artesanal')->assertOk()->assertJsonCount(1);
    }

    // ---------- Pedidos y panel del gestor ----------

    private function pedido(User $comprador, Product $product, string $estado = 'pendiente'): Order
    {
        return Order::create([
            'user_id' => $comprador->id, 'product_id' => $product->id, 'producer_id' => $product->user_id,
            'product_name' => $product->name, 'unit' => $product->unit, 'unit_price' => $product->price,
            'quantity' => 2, 'total' => 3.60, 'status' => $estado,
        ]);
    }

    public function test_comprador_creates_order_and_server_computes_total(): void
    {
        $product = Product::factory()->create(['price' => 2.50]);
        $this->actingAs(User::factory()->create())
            ->postJson('/pedidos', ['product_id' => $product->id, 'quantity' => 4, 'total' => 1])
            ->assertCreated()->assertJsonPath('total', '10.00')->assertJsonPath('status', 'pendiente');
    }

    public function test_productor_and_guest_cannot_create_order(): void
    {
        $product = Product::factory()->create();
        $this->postJson('/pedidos', ['product_id' => $product->id, 'quantity' => 1])->assertUnauthorized();
        $this->actingAs(User::factory()->productor()->create())
            ->postJson('/pedidos', ['product_id' => $product->id, 'quantity' => 1])->assertForbidden();
    }

    public function test_each_role_only_sees_its_orders(): void
    {
        $product = Product::factory()->create();
        $ana = User::factory()->create();
        $luis = User::factory()->create();
        $this->pedido($ana, $product);
        $this->pedido($luis, $product);

        $this->actingAs($ana)->getJson('/pedidos')->assertOk()->assertJsonCount(1);
        $this->actingAs($product->user)->getJson('/pedidos')->assertOk()->assertJsonCount(2);
        $this->actingAs(User::factory()->productor()->create())->getJson('/pedidos')->assertOk()->assertJsonCount(0);
        $this->actingAs(User::factory()->gestor()->create())->getJson('/pedidos')->assertOk()->assertJsonCount(2);
    }

    public function test_productor_updates_status_only_for_own_orders(): void
    {
        $product = Product::factory()->create();
        $order = $this->pedido(User::factory()->create(), $product);

        $this->actingAs(User::factory()->productor()->create())
            ->patchJson("/pedidos/{$order->id}/estado", ['status' => 'confirmado'])->assertForbidden();
        $this->actingAs($product->user)
            ->patchJson("/pedidos/{$order->id}/estado", ['status' => 'confirmado'])->assertOk()->assertJsonPath('status', 'confirmado');
    }

    public function test_comprador_can_only_cancel_own_pending_order(): void
    {
        $product = Product::factory()->create();
        $ana = User::factory()->create();
        $order = $this->pedido($ana, $product);

        $this->actingAs($ana)->patchJson("/pedidos/{$order->id}/estado", ['status' => 'enviado'])->assertForbidden();
        $this->actingAs(User::factory()->create())->patchJson("/pedidos/{$order->id}/estado", ['status' => 'cancelado'])->assertForbidden();
        $this->actingAs($ana)->patchJson("/pedidos/{$order->id}/estado", ['status' => 'cancelado'])->assertOk();
    }

    public function test_gestor_can_change_any_order_status(): void
    {
        $order = $this->pedido(User::factory()->create(), Product::factory()->create(), 'entregado');
        $this->actingAs(User::factory()->gestor()->create())
            ->patchJson("/pedidos/{$order->id}/estado", ['status' => 'pendiente'])->assertOk();
    }

    public function test_panel_is_only_for_gestor(): void
    {
        $this->getJson('/panel/resumen')->assertUnauthorized();
        $this->actingAs(User::factory()->productor()->create())->getJson('/panel/resumen')->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson('/panel/usuarios')->assertForbidden();
        $this->actingAs(User::factory()->gestor()->create())->getJson('/panel/resumen')->assertOk()->assertJsonStructure(['users', 'products', 'orders', 'revenue']);
        $this->actingAs(User::factory()->gestor()->create())->getJson('/panel/usuarios')->assertOk();
    }

    public function test_server_messages_follow_selected_language(): void
    {
        User::factory()->create(['contact' => 'q@mail.com']);
        $datos = ['contact' => 'q@mail.com', 'password' => 'mala1234', 'role' => 'comprador'];

        $this->postJson('/login', $datos)->assertJsonPath('errors.password.0', 'Las credenciales no son correctas.');
        $this->withHeader('X-Lang', 'qu')->postJson('/login', $datos)
            ->assertJsonPath('errors.password.0', 'Correo otaq contraseña mana allinchu.');
    }
}
