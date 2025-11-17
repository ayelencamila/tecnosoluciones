<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\LocalidadController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\DescuentoController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Ruta de inicio
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // accesible por Admin y Vendedor
    Route::get('/vendedor', function () {
        return Inertia::render('Panel/General');
    })->middleware('role:admin,vendedor')->name('panel.vendedor');

    // SOLO Admin
    Route::get('/solo-admin', function () {
        return Inertia::render('Panel/AdminOnly');
    })->middleware('role:admin')->name('panel.admin');
});

require __DIR__.'/auth.php';
// --- RUTAS API ---
Route::get('/api/provincias/{provincia}/localidades', [LocalidadController::class, 'getLocalidadesByProvincia']);

// --- RUTAS PROTEGIDAS (VENTAS Y PAGOS) ---
Route::middleware(['auth'])->group(function () {

    // --- MÓDULO DE VENTAS ---
    Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])
        ->name('ventas.anular');
    
    Route::resource('ventas', VentaController::class);

    // --- MÓDULO DE DESCUENTOS (CU-08) ---
    Route::resource('descuentos', DescuentoController::class);

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

    //--- MÓDULO DE CLIENTES (CU-01) ---
    Route::prefix('clientes')->name('clientes.')->group(function () {
        Route::get('/', [ClienteController::class, 'index'])
        ->name('index');   
        
        Route::get('/crear', [ClienteController::class, 'create'])
        ->name('create');   

        Route::get('/{cliente}', [ClienteController::class, 'show'])
        ->name('show');

        Route::get('/{cliente}/editar', [ClienteController::class, 'edit'])
        ->name('edit');
        
        Route::post('/', [ClienteController::class, 'store'])
        ->name('store');

        Route::put('/{cliente}', [ClienteController::class, 'update'])
        ->name('update');

        Route::get('/{cliente}/confirmar-baja', [ClienteController::class, 'confirmDelete'])
        ->name('confirmDelete');   

        Route::post('/{cliente}/dar-de-baja', [ClienteController::class, 'darDeBaja'])
        ->name('darDeBaja'); 
    });

    //--- MÓDULO DE PRODUCTOS ---
    Route::prefix('productos')->name('productos.')->group(function () {
        Route::get('/', [ProductoController::class, 'index'])
        ->name('index');   
        
        Route::get('/crear', [ProductoController::class, 'create'])
        ->name('create');   

        Route::get('/{producto}', [ProductoController::class, 'show'])
        ->name('show');

        Route::get('/{producto}/editar', [ProductoController::class, 'edit'])
        ->name('edit');

        Route::put('/{producto}', [ProductoController::class, 'update'])
        ->name('update');

        Route::get('/{producto}/confirmar-baja', [ProductoController::class, 'confirmDelete'])
        ->name('confirmDelete');   

        Route::post('/{producto}/dar-de-baja', [ProductoController::class, 'darDeBaja'])
        ->name('darDeBaja'); 

    });

});


