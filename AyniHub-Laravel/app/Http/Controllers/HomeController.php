<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

/**
 * Controlador de la página principal (vista home).
 */
class HomeController extends Controller
{
    /** Muestra la página principal con el catálogo inicial de productos. */
    public function index(): View
    {
        return view('home', ['products' => Product::select('id', 'user_id', 'category', 'name', 'producer_name', 'price', 'unit', 'location', 'image')->latest('id')->limit(100)->get()]);
    }
}
