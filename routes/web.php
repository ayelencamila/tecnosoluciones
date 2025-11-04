<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\LocalidadController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Ruta de inicio
Route::get('/', function () {
    return redirect()->route('clientes.index');
});

// --- RUTAS DE PRUEBA ---
Route::get('/test', function () {
    return Inertia::render('Test');
})->name('test');

Route::get('/simple-test', function () {
    return response()->json(['status' => 'Laravel funcionando', 'time' => now()]);
})->name('simple-test');

Route::get('/vue-test', function () {
    return Inertia::render('SimpleTest');
})->name('vue-test');

// --- MÓDULO DE CLIENTES ---
Route::resource('clientes', ClienteController::class);
Route::get('/clientes/{cliente}/confirm-delete', [ClienteController::class, 'confirmDelete'])->name('clientes.confirm-delete');
Route::post('/clientes/{cliente}/dar-de-baja', [ClienteController::class, 'darDeBaja'])->name('clientes.dar-de-baja');
Route::post('/clientes/{cliente}/toggle-activo', [ClienteController::class, 'toggleActivo'])->name('clientes.toggleActivo');

// --- MÓDULO DE PRODUCTOS ---
Route::resource('productos', ProductoController::class);
Route::get('/productos-stock', [ProductoController::class, 'stock'])->name('productos.stock');

// --- RUTAS API ---
Route::get('/api/provincias/{provincia}/localidades', [LocalidadController::class, 'getLocalidadesByProvincia']);