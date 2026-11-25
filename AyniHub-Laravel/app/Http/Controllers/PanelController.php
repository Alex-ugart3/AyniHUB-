<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Panel y dashboard.
 *
 * Optimización de consultas (Eloquent):
 *  - Todos los totales se calculan en la base de datos (COUNT, SUM, GROUP BY),
 *    nunca cargando miles de modelos en memoria para sumarlos en PHP.
 *  - Los KPIs salen de UNA sola consulta con agregados condicionales.
 *  - Se seleccionan solo las columnas necesarias y se usan índices
 *    (user_id/status, producer_id/status, created_at).
 *  - withCount() evita el problema N+1 al contar pedidos y productos por usuario.
 */
class PanelController extends Controller
{
    /** Resumen global (solo gestor): usuarios por rol, productos, pedidos por estado e ingresos. */
    public function resumen(): JsonResponse
    {
        return response()->json([
            'users'    => User::query()->select('role', DB::raw('COUNT(*) as n'))->groupBy('role')->pluck('n', 'role'),
            'products' => Product::count(),
            'orders'   => Order::query()->select('status', DB::raw('COUNT(*) as n'))->groupBy('status')->pluck('n', 'status'),
            'revenue'  => (float) Order::where('status', '<>', 'cancelado')->sum('total'),
        ]);
    }

    /** Lista de usuarios (solo gestor) con sus conteos, sin consultas N+1. */
    public function usuarios(): JsonResponse
    {
        $users = User::query()
            ->select('id', 'name', 'contact', 'role', 'location', 'created_at')
            ->withCount(['orders', 'products'])
            ->latest('id')
            ->limit(500)
            ->get();

        return response()->json($users);
    }

    /**
     * Dashboard según el rol:
     *  - gestor: toda la plataforma
     *  - productor: pedidos recibidos de sus productos
     *  - comprador: sus propias compras
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // KPIs en una sola consulta (agregados condicionales; compatible con MySQL y SQLite).
        $kpi = $this->scoped($user)
            ->selectRaw("COUNT(*) AS pedidos")
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.status = 'pendiente' THEN 1 ELSE 0 END), 0) AS pendientes")
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.status <> 'cancelado' THEN orders.total ELSE 0 END), 0) AS ventas")
            ->selectRaw("COALESCE(AVG(CASE WHEN orders.status <> 'cancelado' THEN orders.total END), 0) AS ticket")
            ->first();

        $porEstado = $this->scoped($user)
            ->select('orders.status', DB::raw('COUNT(*) AS n'))
            ->groupBy('orders.status')
            ->pluck('n', 'status');

        // Ventas de los últimos 14 días (los días sin ventas se rellenan con 0).
        $desde = now()->subDays(13)->startOfDay();
        $ventasDia = $this->scoped($user)
            ->where('orders.status', '<>', 'cancelado')
            ->where('orders.created_at', '>=', $desde)
            ->select(DB::raw('DATE(orders.created_at) AS d'), DB::raw('SUM(orders.total) AS v'))
            ->groupBy('d')
            ->pluck('v', 'd');

        $dias = [];
        for ($i = 0; $i < 14; $i++) {
            $d = $desde->copy()->addDays($i)->toDateString();
            $dias[] = ['date' => $d, 'total' => round((float) ($ventasDia[$d] ?? 0), 2)];
        }

        $top = $this->scoped($user)
            ->where('orders.status', '<>', 'cancelado')
            ->select('orders.product_name', DB::raw('SUM(orders.quantity) AS cantidad'), DB::raw('SUM(orders.total) AS ventas'))
            ->groupBy('orders.product_name')
            ->orderByDesc('ventas')
            ->limit(5)
            ->get();

        $porCategoria = $this->scoped($user)
            ->join('products', 'products.id', '=', 'orders.product_id')
            ->where('orders.status', '<>', 'cancelado')
            ->select('products.category', DB::raw('SUM(orders.total) AS ventas'))
            ->groupBy('products.category')
            ->pluck('ventas', 'category');

        $data = [
            'role' => $user->role,
            'kpi' => [
                'orders'  => (int) $kpi->pedidos,
                'pending' => (int) $kpi->pendientes,
                'sales'   => round((float) $kpi->ventas, 2),
                'average' => round((float) $kpi->ticket, 2),
            ],
            'by_status'   => $porEstado,
            'sales_daily' => $dias,
            'top_products' => $top,
            'by_category' => $porCategoria,
        ];

        if ($user->role === 'gestor') {
            $data['kpi']['products'] = Product::count();
            $data['kpi']['producers'] = User::where('role', 'productor')->count();
            $data['kpi']['buyers'] = User::where('role', 'comprador')->count();
        } elseif ($user->role === 'productor') {
            $data['kpi']['products'] = Product::where('user_id', $user->id)->count();
        }

        return response()->json($data);
    }

    /** Limita la consulta de pedidos a lo que el rol puede ver. */
    private function scoped(User $user): Builder
    {
        $query = Order::query();

        return match ($user->role) {
            'gestor'   => $query,
            'productor' => $query->where('orders.producer_id', $user->id),
            default    => $query->where('orders.user_id', $user->id),
        };
    }
}
