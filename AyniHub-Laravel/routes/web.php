<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Página principal
Route::get('/', [HomeController::class, 'index'])->name('home');

// Autenticación (límite de intentos contra fuerza bruta: ver AppServiceProvider)
Route::middleware('guest')->group(function () {
    Route::post('/registro', [AuthController::class, 'register'])->middleware('throttle:auth')->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Productos: lectura pública, escritura solo autenticada (la Policy decide el rol/propiedad)
Route::get('/productos', [ProductController::class, 'index'])->name('products.index');
Route::get('/productos/{product}', [ProductController::class, 'show'])->name('products.show');

Route::middleware('auth')->group(function () {
    Route::post('/productos', [ProductController::class, 'store'])->name('products.store');
    Route::put('/productos/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/productos/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Pedidos: cada rol ve solo los suyos; solo el comprador crea pedidos
    Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/pedidos', [OrderController::class, 'store'])->middleware('role:comprador')->name('orders.store');
    Route::patch('/pedidos/{order}/estado', [OrderController::class, 'updateStatus'])->name('orders.status');

    // Dashboard: los 3 roles, con datos distintos según el rol
    Route::get('/panel/dashboard', [PanelController::class, 'dashboard'])->name('panel.dashboard');

    // Administración: solo gestor
    Route::middleware('role:gestor')->group(function () {
        Route::get('/panel/resumen', [PanelController::class, 'resumen'])->name('panel.summary');
        Route::get('/panel/usuarios', [PanelController::class, 'usuarios'])->name('panel.users');
    });
});
