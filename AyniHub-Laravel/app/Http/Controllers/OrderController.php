<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private const WITH = ['user:id,name,contact', 'producer:id,name'];

    /** Lista los pedidos que le corresponden a cada rol. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        // Eager loading con columnas limitadas: evita N+1 (1 consulta de pedidos + 1 por relación).
        $query = Order::with(self::WITH)
            ->select(['id', 'user_id', 'producer_id', 'product_name', 'unit', 'unit_price', 'quantity', 'total', 'status', 'created_at'])
            ->latest('id');

        if ($user->role === 'comprador') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'productor') {
            $query->where('producer_id', $user->id);
        } // gestor: todos

        if ($request->filled('status') && in_array($request->status, Order::STATUSES, true)) {
            $query->where('status', $request->status);
        }

        return response()->json($query->limit(200)->get());
    }

    /** El comprador hace un pedido. El total lo calcula el servidor (nunca el navegador). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:1000'],
            'note'       => ['nullable', 'string', 'max:255'],
        ], [
            'product_id.exists' => __('Ese producto ya no existe.'),
            'quantity.min'      => __('La cantidad mínima es 1.'),
            'quantity.max'      => __('La cantidad máxima por pedido es 1000.'),
            'quantity.integer'  => __('La cantidad debe ser un número entero.'),
        ]);

        $product = Product::findOrFail($data['product_id']);

        $order = Order::create([
            'user_id'      => $request->user()->id,
            'product_id'   => $product->id,
            'producer_id'  => $product->user_id,
            'product_name' => $product->name,
            'unit'         => $product->unit,
            'unit_price'   => $product->price,
            'quantity'     => $data['quantity'],
            'total'        => round((float) $product->price * (int) $data['quantity'], 2),
            'note'         => $data['note'] ?? null,
        ]);

        return response()->json($order->load(self::WITH), 201);
    }

    /** Cambia el estado del pedido (la Policy decide según el rol). */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Order::STATUSES)]]);

        $this->authorize('changeStatus', [$order, $data['status']]);

        $order->update(['status' => $data['status']]);

        return response()->json($order->fresh()->load(self::WITH));
    }
}
