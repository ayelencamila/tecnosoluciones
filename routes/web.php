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
use App\Http\Controllers\ProveedorController; 
use App\Http\Controllers\Admin\CategoriaProductoController;
use App\Http\Controllers\Api\ClienteBonificacionController;
use App\Http\Controllers\ComprobanteInternoController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas para respuesta de cliente a bonificaciones
Route::prefix('bonificacion')->group(function () {
    Route::get('/{token}', [ClienteBonificacionController::class, 'mostrar'])->name('bonificacion.mostrar');
    Route::post('/{token}/responder', [ClienteBonificacionController::class, 'responder'])->name('bonificacion.responder');
});

// CU-20: Rutas públicas del Portal de Proveedores (Magic Links)
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/cotizacion/{token}', [\App\Http\Controllers\Portal\PortalProveedorController::class, 'mostrarCotizacion'])->name('cotizacion');
    Route::post('/cotizacion/{token}/responder', [\App\Http\Controllers\Portal\PortalProveedorController::class, 'responderCotizacion'])->name('cotizacion.responder');
    Route::post('/cotizacion/{token}/rechazar', [\App\Http\Controllers\Portal\PortalProveedorController::class, 'rechazarCotizacion'])->name('cotizacion.rechazar');
    Route::get('/cotizacion/{token}/agradecimiento-rechazo', [\App\Http\Controllers\Portal\PortalProveedorController::class, 'agradecimientoRechazo'])->name('agradecimiento.rechazo');
});

// Ruta de inicio - Redirigir al login o dashboard
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// --- RUTAS VERIFICADAS Y DE ADMINISTRACIÓN ---
Route::middleware(['auth', 'verified'])->group(function () {
    // Accesible por Admin y Vendedor (Panel General)
    Route::get('/vendedor', function () {
        return Inertia::render('Panel/General');
    })->middleware('role:administrador,vendedor')->name('panel.vendedor');

    // SOLO Admin (Panel Exclusivo)
    Route::get('/solo-admin', function () {
        return Inertia::render('Panel/AdminOnly');
    })->middleware('role:administrador')->name('panel.admin');

    // --- GESTIÓN DE MAESTROS (Configuración de Tablas Paramétricas) ---
    // Todas estas rutas tendrán el prefijo /admin/ y el nombre admin.
    Route::prefix('admin')->name('admin.')->middleware('role:administrador')->group(function () {
        
        // ============================================================
        // CU-12: GESTIÓN DE USUARIOS Y ROLES
        // ============================================================
        
        // --- Gestión de Usuarios ---
        Route::resource('usuarios', \App\Http\Controllers\Admin\UserController::class);
        Route::patch('usuarios/{usuario}/toggle-activo', [\App\Http\Controllers\Admin\UserController::class, 'toggleActivo'])
            ->name('usuarios.toggle-activo');
        Route::patch('usuarios/{usuario}/toggle-bloqueo', [\App\Http\Controllers\Admin\UserController::class, 'toggleBloqueo'])
            ->name('usuarios.toggle-bloqueo');
        Route::post('usuarios/{usuario}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])
            ->name('usuarios.reset-password');

        // --- Gestión de Roles ---
        Route::resource('roles', \App\Http\Controllers\Admin\RolController::class)->parameters(['roles' => 'role']);
        Route::patch('roles/{role}/toggle-activo', [\App\Http\Controllers\Admin\RolController::class, 'toggleActivo'])
            ->name('roles.toggle-activo');

        // ============================================================
        // MAESTROS (Tablas Paramétricas)
        // ============================================================
        
        // 1. Categorías de Producto (Implementación Actual)
        Route::resource('categorias', CategoriaProductoController::class);
        Route::resource('estados-producto', \App\Http\Controllers\Admin\EstadoProductoController::class);
        // Proveedores MOVIDO al grupo principal (es un módulo operativo)
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
        Route::resource('motivos-demora', \App\Http\Controllers\Admin\MotivoDemoraReparacionController::class);
        Route::post('admin/motivos-demora/reorder', [\App\Http\Controllers\Admin\MotivoDemoraReparacionController::class, 'reorder'])->name('admin.motivos-demora.reorder');
        Route::patch('admin/motivos-demora/{motivosDemora}/toggle', [\App\Http\Controllers\Admin\MotivoDemoraReparacionController::class, 'toggle'])->name('admin.motivos-demora.toggle');
        
        // Categorías de Gasto (para módulo de gastos)
        Route::resource('categorias-gasto', \App\Http\Controllers\Admin\CategoriaGastoController::class);
        Route::patch('categorias-gasto/{categorias_gasto}/toggle-activo', [\App\Http\Controllers\Admin\CategoriaGastoController::class, 'toggleActivo'])->name('admin.categorias-gasto.toggle-activo');
    });
});

require __DIR__.'/auth.php';

// --- RUTAS API ---
Route::get('/api/provincias/{provincia}/localidades', [LocalidadController::class, 'getLocalidadesByProvincia'])
    ->name('api.localidades.por-provincia');

// API de Modelos por Marca
Route::get('/api/marcas/{marca}/modelos', [\App\Http\Controllers\API\ModeloController::class, 'index'])
    ->name('api.modelos.por-marca');

// --- API CONFIGURABLE SELECTS (CRUD RÁPIDO) ---
Route::middleware(['auth'])->prefix('api')->group(function () {
    // Tipos de Cliente
    Route::get('/tipos-cliente', [\App\Http\Controllers\Api\TipoClienteApiController::class, 'index']);
    Route::post('/tipos-cliente', [\App\Http\Controllers\Api\TipoClienteApiController::class, 'store']);
    Route::delete('/tipos-cliente/{tipoCliente}', [\App\Http\Controllers\Api\TipoClienteApiController::class, 'destroy']);
    
    // Estados de Cliente
    Route::get('/estados-cliente', [\App\Http\Controllers\Api\EstadoClienteApiController::class, 'index']);
    Route::post('/estados-cliente', [\App\Http\Controllers\Api\EstadoClienteApiController::class, 'store']);
    Route::delete('/estados-cliente/{estadoCliente}', [\App\Http\Controllers\Api\EstadoClienteApiController::class, 'destroy']);
    
    // Provincias
    Route::get('/provincias', [\App\Http\Controllers\Api\ProvinciaApiController::class, 'index']);
    Route::post('/provincias', [\App\Http\Controllers\Api\ProvinciaApiController::class, 'store']);
    Route::delete('/provincias/{provincia}', [\App\Http\Controllers\Api\ProvinciaApiController::class, 'destroy']);
    
    // Localidades
    Route::get('/localidades', [\App\Http\Controllers\Api\LocalidadApiController::class, 'index']);
    Route::post('/localidades', [\App\Http\Controllers\Api\LocalidadApiController::class, 'store']);
    Route::delete('/localidades/{localidad}', [\App\Http\Controllers\Api\LocalidadApiController::class, 'destroy']);
    
    // Marcas
    Route::get('/marcas', [\App\Http\Controllers\Api\MarcaApiController::class, 'index']);
    Route::post('/marcas', [\App\Http\Controllers\Api\MarcaApiController::class, 'store']);
    Route::delete('/marcas/{marca}', [\App\Http\Controllers\Api\MarcaApiController::class, 'destroy']);
    
    // Modelos (por marca)
    Route::get('/modelos', [\App\Http\Controllers\Api\ModeloController::class, 'indexAll']);
    Route::post('/modelos', [\App\Http\Controllers\Api\ModeloController::class, 'store']);
    Route::delete('/modelos/{modelo}', [\App\Http\Controllers\Api\ModeloController::class, 'destroy']);
    
    // Unidades de Medida
    Route::get('/unidades-medida', [\App\Http\Controllers\Api\UnidadMedidaApiController::class, 'index']);
    Route::post('/unidades-medida', [\App\Http\Controllers\Api\UnidadMedidaApiController::class, 'store']);
    Route::delete('/unidades-medida/{unidadMedida}', [\App\Http\Controllers\Api\UnidadMedidaApiController::class, 'destroy']);
    
    // Categorías de Producto
    Route::get('/categorias-producto', [\App\Http\Controllers\Api\CategoriaProductoApiController::class, 'index']);
    Route::post('/categorias-producto', [\App\Http\Controllers\Api\CategoriaProductoApiController::class, 'store']);
    Route::delete('/categorias-producto/{categoriaProducto}', [\App\Http\Controllers\Api\CategoriaProductoApiController::class, 'destroy']);
    
    // Estados de Producto
    Route::get('/estados-producto', [\App\Http\Controllers\Api\EstadoProductoApiController::class, 'index']);
    Route::post('/estados-producto', [\App\Http\Controllers\Api\EstadoProductoApiController::class, 'store']);
    Route::delete('/estados-producto/{estadoProducto}', [\App\Http\Controllers\Api\EstadoProductoApiController::class, 'destroy']);
    
    // Medios de Pago
    Route::get('/medios-pago', [\App\Http\Controllers\Api\MedioPagoApiController::class, 'index']);
    Route::post('/medios-pago', [\App\Http\Controllers\Api\MedioPagoApiController::class, 'store']);
    Route::delete('/medios-pago/{medioPago}', [\App\Http\Controllers\Api\MedioPagoApiController::class, 'destroy']);
    
    // Tipos de Movimiento de Stock
    Route::get('/tipos-movimiento-stock', [\App\Http\Controllers\Api\TipoMovimientoStockApiController::class, 'index']);
    Route::post('/tipos-movimiento-stock', [\App\Http\Controllers\Api\TipoMovimientoStockApiController::class, 'store']);
    Route::delete('/tipos-movimiento-stock/{tipoMovimientoStock}', [\App\Http\Controllers\Api\TipoMovimientoStockApiController::class, 'destroy']);
});

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

    // API de Alertas para Técnicos (CU-14)
    Route::get('/api/tecnico/alertas', function () {
        return \App\Models\AlertaReparacion::where('tecnicoID', auth()->id())
            ->where('leida', false)
            ->with(['reparacion.cliente', 'reparacion.modelo.marca', 'tipoAlerta'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();
    });
    
    // Marcar alerta individual como leída
    Route::patch('/api/tecnico/alertas/{id}/marcar-leida', function ($id) {
        $alerta = \App\Models\AlertaReparacion::where('alertaReparacionID', $id)
            ->where('tecnicoID', auth()->id())
            ->firstOrFail();
        $alerta->update(['leida' => true, 'fecha_lectura' => now()]);
        return response()->json(['success' => true]);
    });
    
    // Marcar todas las alertas como leídas
    Route::post('/api/tecnico/alertas/marcar-todas-leidas', function () {
        \App\Models\AlertaReparacion::where('tecnicoID', auth()->id())
            ->where('leida', false)
            ->update(['leida' => true, 'fecha_lectura' => now()]);
        return response()->json(['success' => true]);
    });

    // API de Motivos de Demora (para técnicos y admins)
    Route::get('/api/motivos-demora', function () {
        return \App\Models\MotivoDemoraReparacion::orderBy('orden')->get();
    });

    Route::post('/api/motivos-demora', function (\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'codigo' => 'nullable|string|max:50',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'requiere_bonificacion' => 'boolean',
            'pausa_sla' => 'boolean',
            'activo' => 'boolean',
        ]);

        // Generar código si no se proporciona
        if (empty($validated['codigo'])) {
            $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '_', substr($validated['nombre'], 0, 10)));
            $validated['codigo'] = 'MOT_' . $base . '_' . substr(time(), -4);
        }

        // Asegurar que el código sea único
        $baseCode = $validated['codigo'];
        $counter = 1;
        while (\App\Models\MotivoDemoraReparacion::where('codigo', $validated['codigo'])->exists()) {
            $validated['codigo'] = $baseCode . '_' . $counter++;
        }

        // Obtener el próximo orden disponible
        $maxOrden = \App\Models\MotivoDemoraReparacion::max('orden') ?? 0;
        $validated['orden'] = $maxOrden + 1;
        $validated['activo'] = $validated['activo'] ?? true;
        $validated['requiere_bonificacion'] = $validated['requiere_bonificacion'] ?? false;
        $validated['pausa_sla'] = $validated['pausa_sla'] ?? false;

        $motivo = \App\Models\MotivoDemoraReparacion::create($validated);

        return response()->json($motivo, 201);
    });

    Route::patch('/api/motivos-demora/{id}/toggle', function ($id) {
        $motivo = \App\Models\MotivoDemoraReparacion::findOrFail($id);
        $motivo->update(['activo' => !$motivo->activo]);
        return response()->json(['success' => true, 'activo' => $motivo->activo]);
    });

    Route::delete('/api/motivos-demora/{id}', function ($id) {
        $motivo = \App\Models\MotivoDemoraReparacion::findOrFail($id);
        // Soft delete: solo desactivar
        $motivo->update(['activo' => false]);
        return response()->json(['success' => true]);
    });
});

// --- RUTAS PROTEGIDAS (OPERATIVAS: VENTAS, PAGOS, ETC.) ---
Route::middleware(['auth'])->group(function () {
    // --- MÓDULO DE REPORTES Y ESTADÍSTICAS (CU-33 a CU-37) ---
    // Acceso restringido solo a Administradores (y Gerentes si existiera el rol)
    Route::prefix('reportes')->name('reportes.')->middleware(['role:administrador'])->group(function () {
        
        // CU-33: Reporte de Ventas
        Route::get('/ventas', [\App\Http\Controllers\Reportes\ReporteVentaController::class, 'index'])->name('ventas');
        Route::get('/ventas/exportar', [\App\Http\Controllers\Reportes\ReporteVentaController::class, 'exportar'])->name('ventas.exportar');

        // CU-34: Reporte de Reparaciones
        Route::get('/reparaciones', [\App\Http\Controllers\Reportes\ReporteReparacionController::class, 'index'])->name('reparaciones');
        Route::get('/reparaciones/exportar', [\App\Http\Controllers\Reportes\ReporteReparacionController::class, 'exportar'])->name('reparaciones.exportar');

        // CU-35: Reporte de Compras
        Route::get('/compras', [\App\Http\Controllers\Reportes\ReporteCompraController::class, 'index'])->name('compras');
        Route::get('/compras/exportar', [\App\Http\Controllers\Reportes\ReporteCompraController::class, 'exportar'])->name('compras.exportar');

        // CU-36 y CU-37: Reporte de Stock y Uso de Repuestos
        Route::get('/stock', [\App\Http\Controllers\Reportes\ReporteStockController::class, 'index'])->name('stock');
        Route::get('/stock/exportar', [\App\Http\Controllers\Reportes\ReporteStockController::class, 'exportar'])->name('stock.exportar');

        // Reporte Mensual Consolidado (Entradas, Salidas, Balance)
        Route::get('/mensual', [\App\Http\Controllers\Reportes\ReporteMensualController::class, 'index'])->name('mensual');
        Route::get('/mensual/exportar', [\App\Http\Controllers\Reportes\ReporteMensualController::class, 'exportar'])->name('mensual.exportar');
    });

    // --- MÓDULO DE GASTOS Y PÉRDIDAS ---
    Route::resource('gastos', \App\Http\Controllers\GastoController::class)->except(['show']);
    Route::patch('/gastos/{gasto}/anular', [\App\Http\Controllers\GastoController::class, 'anular'])->name('gastos.anular');

    // --- MÓDULO DE VENTAS ---
    Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])
        ->name('ventas.anular');
    
    Route::get('/ventas/{venta}/imprimir', [VentaController::class, 'imprimirComprobante'])
        ->name('ventas.imprimir');
    
    Route::get('/ventas/{venta}/imprimir-anulacion', [VentaController::class, 'imprimirComprobanteAnulacion'])
        ->name('ventas.imprimir-anulacion');
    
    Route::resource('ventas', VentaController::class);

    // --- MÓDULO DE DESCUENTOS (CU-08) ---
    Route::resource('descuentos', DescuentoController::class);

    // --- MÓDULO DE PAGOS (CU-10) ---
    Route::prefix('pagos')->name('pagos.')->group(function () {
        // Listado (Index)
        Route::get('/', [PagoController::class, 'index'])->name('index');
        // Formulario de Carga (Create)
        Route::get('/crear', [PagoController::class, 'create'])->name('create');
        // Obtener documentos pendientes de un cliente (CU-10 Paso 6)
        Route::get('/cliente/{cliente}/documentos-pendientes', [PagoController::class, 'obtenerDocumentosPendientes'])
            ->name('cliente.documentos');
        // Guardar Pago (Store)
        Route::post('/', [PagoController::class, 'store'])->name('store');
        // Ver Recibo (Show)
        Route::get('/{pago}', [PagoController::class, 'show'])->name('show');
        // Imprimir Recibo (CU-10 Paso 12 - Kendall)
        Route::get('/{pago}/imprimir', [PagoController::class, 'imprimirRecibo'])->name('imprimir');
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

    // --- MÓDULO DE PROVEEDORES (CU-16 a CU-19) ---
    // Ubicado aquí para acceso operativo (Admin/Compras)
    Route::resource('proveedores', ProveedorController::class)->parameters([
        'proveedores' => 'proveedor'
    ]);
    
    // Ruta adicional para actualizar calificación (CU-21)
    Route::patch('proveedores/{proveedor}/calificacion', [ProveedorController::class, 'actualizarCalificacion'])
        ->name('proveedores.actualizar-calificacion');
    
    // API Interna para buscar clientes (Buscador Asíncrono)
    Route::get('/api/clientes/buscar', [App\Http\Controllers\ClienteController::class, 'buscar'])
        ->name('api.clientes.buscar');

    // API: Buscar usuarios/técnicos (para autocomplete)
    Route::get('/api/usuarios/buscar', [App\Http\Controllers\Admin\UserController::class, 'buscar'])
        ->name('api.usuarios.buscar');

    // API: Buscar proveedores (para autocomplete)
    Route::get('/api/proveedores/buscar', [App\Http\Controllers\ProveedorController::class, 'buscar'])
        ->name('api.proveedores.buscar');

    // API: Buscar productos (para autocomplete)
    Route::get('/api/productos/buscar', [ProductoController::class, 'buscar'])
        ->name('api.productos.buscar');

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
    
    // --- MÓDULO DE COMPROBANTES INTERNOS (CU-32) ---
    Route::prefix('comprobantes')->name('comprobantes.')->group(function () {
        Route::get('/', [ComprobanteInternoController::class, 'index'])->name('index');
        Route::get('/{comprobante}', [ComprobanteInternoController::class, 'show'])->name('show');
        Route::get('/{comprobante}/pdf', [ComprobanteInternoController::class, 'verPdf'])->name('pdf');
        Route::get('/{comprobante}/descargar', [ComprobanteInternoController::class, 'descargarPdf'])->name('descargar');
        Route::post('/{comprobante}/anular', [ComprobanteInternoController::class, 'anular'])->name('anular');
        Route::post('/{comprobante}/reemitir', [ComprobanteInternoController::class, 'reemitir'])->name('reemitir');
    })->scopeBindings();
    
    // --- MÓDULO DE CONFIGURACIÓN ---
    Route::prefix('configuracion')->name('configuracion.')->group(function () {
        Route::get('/', [ConfiguracionController::class, 'index'])->name('index');
        Route::put('/', [ConfiguracionController::class, 'update'])->name('update');
    });

    // --- MÓDULO DE PLANTILLAS WHATSAPP (CU-30) ---
    Route::prefix('plantillas-whatsapp')->name('plantillas-whatsapp.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PlantillaWhatsappController::class, 'index'])->name('index');
        Route::get('/{plantilla}/edit', [\App\Http\Controllers\PlantillaWhatsappController::class, 'edit'])->name('edit');
        Route::put('/{plantilla}', [\App\Http\Controllers\PlantillaWhatsappController::class, 'update'])->name('update');
        Route::get('/{plantilla}/preview', [\App\Http\Controllers\PlantillaWhatsappController::class, 'preview'])->name('preview');
        Route::get('/{plantilla}/historial', [\App\Http\Controllers\PlantillaWhatsappController::class, 'historial'])->name('historial');
    });

    // --- MÓDULO DE REPARACIONES (CU-11, CU-12, CU-13) ---
    Route::prefix('reparaciones')->name('reparaciones.')->group(function () {
        Route::get('/', [ReparacionController::class, 'index'])->name('index');
        Route::get('/crear', [ReparacionController::class, 'create'])->name('create');
        Route::post('/', [ReparacionController::class, 'store'])->name('store');
        Route::get('/{reparacion}', [ReparacionController::class, 'show'])->name('show');
        Route::get('/{reparacion}/imprimir-ingreso', [ReparacionController::class, 'imprimirComprobanteIngreso'])->name('imprimir-ingreso');
        Route::get('/{reparacion}/imprimir-entrega', [ReparacionController::class, 'imprimirComprobanteEntrega'])->name('imprimir-entrega');
        Route::get('/{reparacion}/editar', [ReparacionController::class, 'edit'])->name('edit');
        Route::put('/{reparacion}', [ReparacionController::class, 'update'])->name('update');
        Route::delete('/{reparacion}', [ReparacionController::class, 'destroy'])->name('destroy');
    });

    // --- ALERTAS DE SLA (CU-14) - Para Técnicos ---
    Route::prefix('alertas')->name('alertas.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AlertaReparacionController::class, 'index'])->name('index');
        Route::get('/{alerta}', [\App\Http\Controllers\AlertaReparacionController::class, 'show'])->name('show');
        Route::post('/{alerta}/responder', [\App\Http\Controllers\AlertaReparacionController::class, 'responder'])->name('responder');
        Route::patch('/{alerta}/marcar-leida', [\App\Http\Controllers\AlertaReparacionController::class, 'marcarLeida'])->name('marcar-leida');
    });

    // --- BONIFICACIONES (CU-15) - Solo Admin ---
    Route::prefix('bonificaciones')->name('bonificaciones.')->middleware('role:administrador')->group(function () {
        Route::get('/', [\App\Http\Controllers\BonificacionReparacionController::class, 'index'])->name('index');
        Route::get('/historial', [\App\Http\Controllers\BonificacionReparacionController::class, 'historial'])->name('historial');
        Route::get('/{bonificacion}', [\App\Http\Controllers\BonificacionReparacionController::class, 'show'])->name('show');
        Route::post('/{bonificacion}/aprobar', [\App\Http\Controllers\BonificacionReparacionController::class, 'aprobar'])->name('aprobar');
        Route::post('/{bonificacion}/rechazar', [\App\Http\Controllers\BonificacionReparacionController::class, 'rechazar'])->name('rechazar');
    });

    // --- MÓDULO DE COMPRAS (CU-20 a CU-23) ---
    // MODELO SIMPLIFICADO: SolicitudCotizacion → CotizacionProveedor → OrdenCompra
    // (Tabla ofertas_compra eliminada - ver migración simplificar_modelo_compras)
    
    // CU-21: DEPRECADO - Las ofertas ahora son las cotizaciones del proveedor
    // Las rutas de /ofertas se mantienen comentadas por compatibilidad temporal
    // Route::prefix('ofertas')->name('ofertas.')->middleware('role:administrador')->group(function () {
    //     Route::get('/', [\App\Http\Controllers\OfertaCompraController::class, 'index'])->name('index');
    //     ... (rutas deprecadas)
    // });

    // CU-22: Órdenes de Compra (Generar OC desde cotizaciones elegidas)
    // CU-24: Consultar Órdenes de Compra (Historial)
    Route::prefix('ordenes')->name('ordenes.')->middleware('role:administrador')->group(function () {
        Route::get('/', [\App\Http\Controllers\OrdenCompraController::class, 'index'])->name('index'); // Lista cotizaciones elegidas listas para OC
        Route::get('/historial', [\App\Http\Controllers\OrdenCompraController::class, 'historial'])->name('historial'); // CU-24: Consultar OC generadas
        Route::get('/create', [\App\Http\Controllers\OrdenCompraController::class, 'create'])->name('create'); // Resumen + Motivo + Confirmación
        Route::post('/', [\App\Http\Controllers\OrdenCompraController::class, 'store'])->name('store'); // Resultado
        Route::get('/{orden}', [\App\Http\Controllers\OrdenCompraController::class, 'show'])->name('show')->whereNumber('orden');
        Route::get('/{orden}/descargar-pdf', [\App\Http\Controllers\OrdenCompraController::class, 'descargarPdf'])->name('descargar-pdf')->whereNumber('orden');
        Route::post('/{orden}/reenviar-whatsapp', [\App\Http\Controllers\OrdenCompraController::class, 'reenviarWhatsApp'])->name('reenviar-whatsapp')->whereNumber('orden');
        Route::post('/{orden}/reenviar-email', [\App\Http\Controllers\OrdenCompraController::class, 'reenviarEmail'])->name('reenviar-email')->whereNumber('orden');
        Route::post('/{orden}/regenerar-pdf', [\App\Http\Controllers\OrdenCompraController::class, 'regenerarPdf'])->name('regenerar-pdf')->whereNumber('orden');
        Route::post('/{orden}/confirmar', [\App\Http\Controllers\OrdenCompraController::class, 'confirmar'])->name('confirmar')->whereNumber('orden');
        Route::post('/{orden}/cancelar', [\App\Http\Controllers\OrdenCompraController::class, 'cancelar'])->name('cancelar')->whereNumber('orden');
    });

    // CU-23: Recepción de Mercadería
    Route::prefix('recepciones')->name('recepciones.')->middleware('role:administrador')->group(function () {
        Route::get('/', [\App\Http\Controllers\Compras\RecepcionMercaderiaController::class, 'index'])->name('index'); // P1: Seleccionar OC pendiente
        Route::get('/historial', [\App\Http\Controllers\Compras\RecepcionMercaderiaController::class, 'historial'])->name('historial'); // Historial de recepciones
        Route::get('/crear', [\App\Http\Controllers\Compras\RecepcionMercaderiaController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Compras\RecepcionMercaderiaController::class, 'store'])->name('store');
        Route::get('/{recepcion}', [\App\Http\Controllers\Compras\RecepcionMercaderiaController::class, 'show'])->name('show');
    });

    // CU-20: Solicitudes de Cotización
    Route::prefix('solicitudes-cotizacion')->name('solicitudes-cotizacion.')->middleware('role:administrador')->group(function () {
        Route::get('/', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'store'])->name('store');
        Route::get('/{solicitud}', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'show'])->name('show');
        Route::delete('/{solicitud}', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'destroy'])->name('destroy');
        Route::post('/{solicitud}/enviar', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'enviar'])->name('enviar');
        Route::post('/{solicitud}/agregar-proveedor', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'agregarProveedor'])->name('agregar-proveedor');
        Route::post('/{solicitud}/cerrar', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'cerrar'])->name('cerrar');
        Route::post('/{solicitud}/cancelar', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'cancelar'])->name('cancelar');
        Route::post('/{solicitud}/reenviar/{cotizacion}', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'reenviarRecordatorio'])->name('reenviar');
        Route::post('/{solicitud}/elegir/{cotizacion}', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'elegirCotizacion'])->name('elegir-cotizacion');
        Route::get('/{solicitud}/comparar', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'comparar'])->name('comparar');
        Route::post('/generar-automaticas', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'generarAutomaticas'])->name('generar-automaticas');
    });

    // CU-20: Monitoreo de Stock
    Route::get('/monitoreo-stock', [\App\Http\Controllers\Compras\SolicitudCotizacionController::class, 'monitoreoStock'])
        ->name('monitoreo-stock.index')
        ->middleware('role:administrador');

});

