<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;

Route::get('/', function () {
    return view('welcome');
});

// Rutas de Productos
Route::resource('productos', ProductoController::class);
Route::get('productos/{producto}/stock', [ProductoController::class, 'stock'])->name('productos.stock');
