<?php

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\LocalidadController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\PagoController; // <--- NUEVO: Importamos el controlador
use Illuminate\Support\Facades\Route;
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

// --- RUTAS PROTEGIDAS (VENTAS Y PAGOS) ---
Route::middleware(['auth'])->group(function () {

    // --- MÓDULO DE VENTAS ---
    Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])
        ->name('ventas.anular');
    
    Route::resource('ventas', VentaController::class);

    // --- MÓDULO DE PAGOS (CU-10) ---
    // Agrupamos todas las rutas bajo el prefijo 'pagos'
    Route::prefix('pagos')->name('pagos.')->group(function () {
        
        // Listado (Index)
        Route::get('/', [PagoController::class, 'index'])->name('index');
        
        // Formulario de Carga (Create)
        Route::get('/crear', [PagoController::class, 'create'])->name('create');
        
        // Guardar Pago (Store)
        Route::post('/', [PagoController::class, 'store'])->name('store');
        
        // Ver Recibo (Show)
        Route::get('/{pago}', [PagoController::class, 'show'])->name('show');
        
        // Usamos DELETE porque es la acción semántica de "eliminar" (anular) un recurso
        Route::delete('/{pago}', [PagoController::class, 'anular'])
             ->name('anular');
    });

});