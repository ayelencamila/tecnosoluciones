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
use App\Http\Controllers\StockController; 
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ReparacionController;
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

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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
Route::get('/api/provincias/{provincia}/localidades', [LocalidadController::class, 'getLocalidadesByProvincia'])
    ->name('api.localidades.por-provincia');

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
    // API Interna para buscar clientes (Buscador Asíncrono)
    Route::get('/api/clientes/buscar', [App\Http\Controllers\ClienteController::class, 'buscar'])
    ->name('api.clientes.buscar');

    //--- MÓDULO DE PRODUCTOS ---
    Route::prefix('productos')->name('productos.')->group(function () {
        
        // CU-29: Consultar Stock   
        // La ponemos antes de /{producto} para que no confunda "stock" con un ID
        Route::get('/consultar-stock', [ProductoController::class, 'stock'])
            ->name('stock'); 

        // CU-28: Catálogo (Index)
        Route::get('/', [ProductoController::class, 'index'])
            ->name('index');   
        
        // CU-25: Registrar (Create & Store)
        Route::get('/crear', [ProductoController::class, 'create'])
            ->name('create');   
        Route::post('/', [ProductoController::class, 'store'])
            ->name('store');

        // CU-28: Ver Detalle (Show)
        Route::get('/{producto}', [ProductoController::class, 'show'])
            ->name('show');

        // CU-26: Modificar (Edit & Update)
        Route::get('/{producto}/editar', [ProductoController::class, 'edit'])
            ->name('edit');
        Route::put('/{producto}', [ProductoController::class, 'update'])
            ->name('update');

        // CU-27: Dar de Baja
        // Eliminamos 'confirmDelete' porque usamos un Modal en la vista Show.
        Route::post('/{producto}/dar-de-baja', [ProductoController::class, 'darDeBaja'])
            ->name('darDeBaja'); 
            // Nota: Si usas Resource standard sería DELETE /productos/{id}, 
            // pero tu controlador usa 'darDeBaja', así que POST está bien.
    });

    // Ruta para CU-30: Ajuste de Stock
    Route::post('/stock/movimiento', [StockController::class, 'storeMovimiento'])
      ->name('stock.movimiento.store');
    
    // NUEVA RUTA: Actualizar Configuración de Stock (Mínimos)
    Route::put('/stock/{stock}', [StockController::class, 'update'])
        ->name('stock.update');
    
    // --- MÓDULO DE AUDITORÍA ---
    // Usamos una ruta simple para listar. Necesitarás crear el AuditoriaController más adelante
    // si quieres ver la tabla real.
    Route::get('/auditorias', function () {
        return Inertia::render('Auditorias/Index', [
            // Si ya tienes el modelo Auditoria, puedes pasar datos aquí:
            'auditorias' => \App\Models\Auditoria::with('usuario')->latest()->paginate(15)
        ]);
    })->name('auditorias.index');
    
    // --- MÓDULO DE CONFIGURACIÓN ---
    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        Route::get('/', [ConfiguracionController::class, 'index'])->name('index');
        Route::put('/', [ConfiguracionController::class, 'update'])->name('update');
    });

    // --- MÓDULO DE REPARACIONES (CU-11, CU-12, CU-13) ---
    Route::prefix('reparaciones')->name('reparaciones.')->group(function () {
        
        // Listado (Index)
        Route::get('/', [ReparacionController::class, 'index'])
            ->name('index');
        
        // Formulario de Ingreso (Create)
        Route::get('/crear', [ReparacionController::class, 'create'])
            ->name('create');
        
        // Guardar Ingreso (Store)
        Route::post('/', [ReparacionController::class, 'store'])
            ->name('store');
        
        // Ver Detalle (Show)
        Route::get('/{reparacion}', [ReparacionController::class, 'show'])
            ->name('show');
            
        // Formulario de Edición/Diagnóstico (Edit)
        Route::get('/{reparacion}/editar', [ReparacionController::class, 'edit'])
            ->name('edit');
            
        // Guardar Cambios (Update)
        Route::put('/{reparacion}', [ReparacionController::class, 'update'])
            ->name('update');
            
        // Anular (Destroy)
        Route::delete('/{reparacion}', [ReparacionController::class, 'destroy'])
            ->name('destroy');
    });

});


