<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\LocalidadController; // Necesario para la API de localidades
use Inertia\Inertia;
// No usamos el LoginController de forma explícita por ahora,
// ya que su ubicación puede variar o no existir.

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Ruta de inicio que simplemente redirige al listado de clientes
Route::get('/', function () {
    return redirect()->route('clientes.index');
});

// --- RUTA DE PRUEBA PARA VUE.JS + INERTIA.JS ---
Route::get('/test', function () {
    return Inertia::render('Test');
})->name('test');

// --- RUTA DE PRUEBA SIMPLE ---
Route::get('/simple-test', function () {
    return response()->json(['status' => 'Laravel funcionando', 'time' => now()]);
})->name('simple-test');

// --- RUTA DE PRUEBA MUY SIMPLE PARA VUE ---
Route::get('/vue-test', function () {
    return Inertia::render('SimpleTest');
})->name('vue-test');

// --- TEMPORALMENTE: Rutas de autenticación comentadas para evitar el error ---
// Si necesitas un sistema de autenticación completo, te guiaré para instalar Laravel Breeze
// o Jetstream más adelante, que proporcionan estos controladores.
// Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
// Route::post('/login', [LoginController::class, 'login']);
// Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// --- TEMPORALMENTE: Se elimina el middleware 'auth' para que el CRUD funcione sin login ---
// Lo volveremos a agregar cuando tengamos un sistema de autenticación completo.
Route::resource('clientes', ClienteController::class);

// --- RUTAS ADICIONALES PARA CASOS DE USO ESPECÍFICOS ---
Route::get('/clientes/{cliente}/confirm-delete', [ClienteController::class, 'confirmDelete'])->name('clientes.confirm-delete');
Route::post('/clientes/{cliente}/dar-de-baja', [ClienteController::class, 'darDeBaja'])->name('clientes.dar-de-baja');
Route::post('/clientes/{cliente}/toggle-activo', [ClienteController::class, 'toggleActivo'])->name('clientes.toggleActivo');

// --- RUTAS DEL MÓDULO DE PRODUCTOS ---
Route::resource('productos', ProductoController::class);

// CU-29: Consultar Stock
Route::get('/productos-stock', [ProductoController::class, 'stock'])->name('productos.stock');


// --- RUTAS API (Para carga dinámica de Localidades) ---
Route::get('/api/provincias/{provincia}/localidades', [LocalidadController::class, 'getLocalidadesByProvincia']);