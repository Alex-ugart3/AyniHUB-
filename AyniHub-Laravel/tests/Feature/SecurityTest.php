<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pruebas de vulnerabilidades (evidencia para el informe).
 * Cada prueba indica el ataque que intenta y la defensa que lo detiene.
 *
 * Ejecutar:  php artisan test --filter=SecurityTest
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** SQL Injection en el filtro del catálogo: se trata como texto, no como SQL. */
    public function test_sql_injection_in_category_filter_is_harmless(): void
    {
        Product::factory()->count(2)->create();

        $this->getJson("/productos?category=' OR '1'='1")->assertOk()->assertJsonCount(0);
        $this->getJson("/productos?category=agricola'; DROP TABLE products;--")->assertOk()->assertJsonCount(0);
        $this->assertDatabaseCount('products', 2); // la tabla sigue existiendo
    }

    /** SQL Injection en el login: Eloquent usa consultas parametrizadas. */
    public function test_sql_injection_in_login_does_not_authenticate(): void
    {
        User::factory()->create(['contact' => 'ana@mail.com']);

        $this->postJson('/login', ['contact' => "ana@mail.com' OR '1'='1", 'password' => "x' OR '1'='1", 'role' => 'comprador'])
            ->assertStatus(422);
        $this->assertGuest();
    }

    /** XSS almacenado: el HTML malicioso se escapa al mostrarlo en Blade. */
    public function test_stored_xss_is_escaped_in_page(): void
    {
        Product::factory()->create(['name' => '<img src=x onerror=alert(1)>', 'producer_name' => '"><script>alert(2)</script>']);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringNotContainsString('"><script>alert(2)</script>', $html);
    }

    /** Asignación masiva: el usuario no puede registrarse como gestor ni cambiar el dueño del producto. */
    public function test_mass_assignment_is_blocked(): void
    {
        $this->postJson('/registro', ['name' => 'Eve Mal', 'contact' => 'eve@mail.com', 'password' => 'Clave1234', 'role' => 'gestor', 'location' => 'x'])
            ->assertStatus(422);

        $productor = User::factory()->productor()->create();
        $otro = User::factory()->productor()->create();
        $id = $this->actingAs($productor)->postJson('/productos', [
            'category' => 'agricola', 'name' => 'Maíz', 'producer_name' => 'Familia X', 'price' => 3, 'unit' => 'kilo',
            'location' => 'Feria', 'user_id' => $otro->id,
        ])->assertCreated()->json('id');

        $this->assertDatabaseHas('products', ['id' => $id, 'user_id' => $productor->id]); // se ignoró user_id enviado
    }

    /** Manipulación de precios: el total lo calcula el servidor, no el navegador. */
    public function test_client_cannot_tamper_order_total(): void
    {
        $product = Product::factory()->create(['price' => 5]);

        $this->actingAs(User::factory()->create())
            ->postJson('/pedidos', ['product_id' => $product->id, 'quantity' => 3, 'total' => 0.01, 'unit_price' => 0.01, 'status' => 'entregado'])
            ->assertCreated()
            ->assertJsonPath('total', '15.00')
            ->assertJsonPath('status', 'pendiente');
    }

    /** IDOR: un comprador no puede cancelar ni ver pedidos de otro comprador. */
    public function test_idor_on_orders_is_blocked(): void
    {
        $victima = User::factory()->create();
        $atacante = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $victima->id]);

        $this->actingAs($atacante)->patchJson("/pedidos/{$order->id}/estado", ['status' => 'cancelado'])->assertForbidden();
        $this->actingAs($atacante)->getJson('/pedidos')->assertOk()->assertJsonCount(0);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pendiente']);
    }

    /** Escalada de privilegios: el comprador no accede a rutas del gestor ni crea productos. */
    public function test_role_escalation_is_blocked(): void
    {
        $comprador = User::factory()->create();

        $this->actingAs($comprador)->getJson('/panel/usuarios')->assertForbidden();
        $this->actingAs($comprador)->getJson('/panel/resumen')->assertForbidden();
        $this->actingAs($comprador)->postJson('/productos', ['category' => 'agricola', 'name' => 'Hack', 'producer_name' => 'x', 'price' => 1, 'unit' => 'kilo', 'location' => 'x'])->assertForbidden();
    }

    /** Fuerza bruta: tras 5 intentos fallidos el login se bloquea (429). */
    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['contact' => 'ana@mail.com']);
        $datos = ['contact' => 'ana@mail.com', 'password' => 'incorrecta1', 'role' => 'comprador'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', $datos)->assertStatus(422);
        }
        $this->postJson('/login', $datos)->assertStatus(429);
    }

    /** Las respuestas JSON nunca exponen contraseñas. */
    public function test_password_hash_is_never_exposed(): void
    {
        $gestor = User::factory()->gestor()->create();

        $this->actingAs($gestor)->getJson('/panel/usuarios')->assertOk()->assertDontSee('password')->assertDontSee('remember_token');
    }

    /** Las contraseñas se guardan con hash, no en texto plano. */
    public function test_passwords_are_hashed(): void
    {
        $this->postJson('/registro', ['name' => 'Ana Torres', 'contact' => 'ana@mail.com', 'password' => 'Clave1234', 'role' => 'comprador'])->assertCreated();

        $guardada = User::where('contact', 'ana@mail.com')->value('password');
        $this->assertNotSame('Clave1234', $guardada);
        $this->assertTrue(password_verify('Clave1234', $guardada));
    }

    /** Cabeceras de seguridad (CSP, anti-clickjacking, anti-sniffing). */
    public function test_security_headers_are_present(): void
    {
        $this->get('/')->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');
    }

    /** Validación de entradas extremas: cantidades negativas o enormes se rechazan. */
    public function test_invalid_quantities_are_rejected(): void
    {
        $product = Product::factory()->create();
        $comprador = User::factory()->create();

        foreach ([0, -5, 100000, 'abc'] as $cantidad) {
            $this->actingAs($comprador)->postJson('/pedidos', ['product_id' => $product->id, 'quantity' => $cantidad])->assertStatus(422);
        }
    }

    /** Estado inválido: no se aceptan estados fuera de la lista permitida. */
    public function test_invalid_order_status_is_rejected(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->gestor()->create())
            ->patchJson("/pedidos/{$order->id}/estado", ['status' => 'hackeado'])->assertStatus(422);
    }
}
