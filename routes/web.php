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
use App\Http\Controllers\Admin\CategoriaProductoController;
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

// --- RUTAS VERIFICADAS Y DE ADMINISTRACIÓN ---
Route::middleware(['auth', 'verified'])->group(function () {
    // Accesible por Admin y Vendedor (Panel General)
    Route::get('/vendedor', function () {
        return Inertia::render('Panel/General');
    })->middleware('role:admin,vendedor')->name('panel.vendedor');

    // SOLO Admin (Panel Exclusivo)
    Route::get('/solo-admin', function () {
        return Inertia::render('Panel/AdminOnly');
    })->middleware('role:admin')->name('panel.admin');

    // --- GESTIÓN DE MAESTROS (Configuración de Tablas Paramétricas) ---
    // Todas estas rutas tendrán el prefijo /admin/ y el nombre admin.
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        
        // 1. Categorías de Producto (Implementación Actual)
        Route::resource('categorias', CategoriaProductoController::class);
        Route::resource('estados-producto', \App\Http\Controllers\Admin\EstadoProductoController::class);
        Route::resource('proveedores', \App\Http\Controllers\Admin\ProveedorController::class);
        Route::resource('unidades-medida', \App\Http\Controllers\Admin\UnidadMedidaController::class);
        // 2. Otras posibles rutas para  maestros
        Route::resource('estados-reparacion', \App\Http\Controllers\Admin\EstadoReparacionController::class);
        Route::resource('provincias', \App\Http\Controllers\Admin\ProvinciaController::class);
        Route::resource('localidades', \App\Http\Controllers\Admin\LocalidadController::class);
        Route::resource('depositos', \App\Http\Controllers\Admin\DepositoController::class);
        Route::resource('tipos-cliente', \App\Http\Controllers\Admin\TipoClienteController::class);
        Route::resource('estados-cliente', \App\Http\Controllers\Admin\EstadoClienteController::class);
        Route::resource('medios-pago', \App\Http\Controllers\Admin\MedioPagoController::class);
        Route::resource('marcas', \App\Http\Controllers\Admin\MarcaController::class);
        Route::resource('modelos', \App\Http\Controllers\Admin\ModeloController::class);
    });
});

require __DIR__.'/auth.php';

// --- RUTAS API ---
Route::get('/api/provincias/{provincia}/localidades', [LocalidadController::class, 'getLocalidadesByProvincia'])
    ->name('api.localidades.por-provincia');
Route::get('/api/provincias/{provincia}/localidades', [LocalidadController::class, 'getLocalidadesByProvincia'])
    ->name('api.localidades.por-provincia');

// API de Modelos por Marca
Route::get('/api/marcas/{marca}/modelos', [\App\Http\Controllers\API\ModeloController::class, 'index'])
    ->name('api.modelos.por-marca');

// --- API DE NOTIFICACIONES ---
Route::middleware(['auth'])->group(function () {
    Route::get('/api/notifications', function () {
        return auth()->user()->notifications()
            ->take(20)
            ->get();
    });
    
    Route::post('/api/notifications/{id}/read', function ($id) {
        auth()->user()->notifications()
            ->where('id', $id)
            ->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    });
    
    Route::post('/api/notifications/mark-all-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    });
});

// --- RUTAS PROTEGIDAS (OPERATIVAS: VENTAS, PAGOS, ETC.) ---
Route::middleware(['auth'])->group(function () {

    // --- MÓDULO DE VENTAS ---
    Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])
        ->name('ventas.anular');
    
    Route::resource('ventas', VentaController::class);

    // --- MÓDULO DE DESCUENTOS (CU-08) ---
    Route::resource('descuentos', DescuentoController::class);

    // --- MÓDULO DE PAGOS (CU-10) ---
    Route::prefix('pagos')->name('pagos.')->group(function () {
        // Listado (Index)
        Route::get('/', [PagoController::class, 'index'])->name('index');
        // Formulario de Carga (Create)
        Route::get('/crear', [PagoController::class, 'create'])->name('create');
        // Guardar Pago (Store)
        Route::post('/', [PagoController::class, 'store'])->name('store');
        // Ver Recibo (Show)
        Route::get('/{pago}', [PagoController::class, 'show'])->name('show');
        // Anular Pago
        Route::delete('/{pago}', [PagoController::class, 'anular'])->name('anular');
    });

    //--- MÓDULO DE CLIENTES (CU-01) ---
    Route::prefix('clientes')->name('clientes.')->group(function () {
        Route::get('/', [ClienteController::class, 'index'])->name('index');   
        Route::get('/crear', [ClienteController::class, 'create'])->name('create');   
        Route::get('/{cliente}', [ClienteController::class, 'show'])->name('show');
        Route::get('/{cliente}/editar', [ClienteController::class, 'edit'])->name('edit');
        Route::post('/', [ClienteController::class, 'store'])->name('store');
        Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update');
        Route::get('/{cliente}/confirmar-baja', [ClienteController::class, 'confirmDelete'])->name('confirmDelete');   
        Route::post('/{cliente}/dar-de-baja', [ClienteController::class, 'darDeBaja'])->name('darDeBaja'); 
    });
    
    // API Interna para buscar clientes (Buscador Asíncrono)
    Route::get('/api/clientes/buscar', [App\Http\Controllers\ClienteController::class, 'buscar'])
        ->name('api.clientes.buscar');

    //--- MÓDULO DE PRODUCTOS ---
    Route::prefix('productos')->name('productos.')->group(function () {
        // CU-29: Consultar Stock   
        Route::get('/consultar-stock', [ProductoController::class, 'stock'])->name('stock'); 

        // CU-28: Catálogo (Index)
        Route::get('/', [ProductoController::class, 'index'])->name('index');   
        
        // CU-25: Registrar (Create & Store)
        Route::get('/crear', [ProductoController::class, 'create'])->name('create');   
        Route::post('/', [ProductoController::class, 'store'])->name('store');

        // CU-28: Ver Detalle (Show)
        Route::get('/{producto}', [ProductoController::class, 'show'])->name('show');

        // CU-26: Modificar (Edit & Update)
        Route::get('/{producto}/editar', [ProductoController::class, 'edit'])->name('edit');
        Route::put('/{producto}', [ProductoController::class, 'update'])->name('update');

        // CU-27: Dar de Baja
        Route::post('/{producto}/dar-de-baja', [ProductoController::class, 'darDeBaja'])->name('darDeBaja'); 
    });

    // Ruta para CU-30: Ajuste de Stock
    Route::post('/stock/movimiento', [StockController::class, 'storeMovimiento'])
      ->name('stock.movimiento.store');
    
    // NUEVA RUTA: Actualizar Configuración de Stock (Mínimos)
    Route::put('/stock/{stock}', [StockController::class, 'update'])
        ->name('stock.update');
    
    // --- MÓDULO DE AUDITORÍA ---
    Route::get('/auditorias', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\Auditoria::with('usuario');
        
        // Filtros dinámicos
        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }
        if ($request->filled('tabla')) {
            $query->where('tablaAfectada', $request->tabla);
        }
        if ($request->filled('usuario')) {
            $query->whereHas('usuario', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->usuario . '%');
            });
        }
        
        return Inertia::render('Auditorias/Index', [
            'auditorias' => $query->latest()->paginate(15)->withQueryString()
        ]);
    })->name('auditorias.index');
    
    // --- MÓDULO DE CONFIGURACIÓN ---
    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        Route::get('/', [ConfiguracionController::class, 'index'])->name('index');
        Route::put('/', [ConfiguracionController::class, 'update'])->name('update');
    });

    // --- MÓDULO DE REPARACIONES (CU-11, CU-12, CU-13) ---
    Route::prefix('reparaciones')->name('reparaciones.')->group(function () {
        Route::get('/', [ReparacionController::class, 'index'])->name('index');
        Route::get('/crear', [ReparacionController::class, 'create'])->name('create');
        Route::post('/', [ReparacionController::class, 'store'])->name('store');
        Route::get('/{reparacion}', [ReparacionController::class, 'show'])->name('show');
        Route::get('/{reparacion}/editar', [ReparacionController::class, 'edit'])->name('edit');
        Route::put('/{reparacion}', [ReparacionController::class, 'update'])->name('update');
        Route::delete('/{reparacion}', [ReparacionController::class, 'destroy'])->name('destroy');
    });

});

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- PROGRAMADOR PARA CU-09 ---
// Esto registra el comando para ejecutarse diariamente
Schedule::command('cc:check-vencimientos')
    ->dailyAt('08:00') // Se ejecuta a las 8:00 AM todos los días
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));


