<?php

use App\Http\Controllers\Admin\CategoriaProductoController;
use App\Http\Controllers\Api\ClienteBonificacionController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ComprobanteInternoController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DescuentoController;
use App\Http\Controllers\LocalidadController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\Plataforma\EmpresaPlataformaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReparacionController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;
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

/*
|--------------------------------------------------------------------------
| TEXTOS LEGALES (publicos)
|--------------------------------------------------------------------------
| Deliberadamente fuera de `auth`: tienen que poder leerse ANTES de crear
| una cuenta, porque el registro exige aceptarlos, y seguir accesibles
| despues para quien quiera volver a consultarlos.
*/
Route::prefix('legal')->name('legal.')->group(function () {
    // `borrador` y `responsable` viajan desde el config para que el texto de
    // ejemplo no se pueda confundir con uno definitivo y para no repetir los
    // datos del responsable en cada vista. Ver config/legal.php.
    $datosLegales = fn () => [
        'borrador' => ! config('legal.textos_definitivos'),
        'actualizado' => config('legal.actualizado'),
        'responsable' => config('legal.responsable'),
    ];

    Route::get('/privacidad', fn () => Inertia::render('Legal/Privacidad', $datosLegales()))
        ->name('privacidad');
    Route::get('/terminos', fn () => Inertia::render('Legal/Terminos', $datosLegales()))
        ->name('terminos');
});

// Ruta de inicio - Redirigir al login o dashboard
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| NIVEL DE PLATAFORMA (super usuario)
|--------------------------------------------------------------------------
| Gestion de los clientes del sistema. Es el unico grupo que trabaja por
| encima del tenant, de modo que queda deliberadamente FUERA del middleware
| `tenant`: si lo tuviera, el operador no podria llegar a elegir empresa.
*/
Route::middleware(['auth', 'super'])->prefix('super')->name('super.')->group(function () {
    Route::get('/empresas', [EmpresaPlataformaController::class, 'index'])->name('empresas.index');
    Route::post('/empresas', [EmpresaPlataformaController::class, 'store'])->name('empresas.store');
    Route::get('/empresas/{empresa}', [EmpresaPlataformaController::class, 'show'])->name('empresas.show');
    Route::put('/empresas/{empresa}', [EmpresaPlataformaController::class, 'update'])->name('empresas.update');
    Route::post('/empresas/{empresa}/acceso', [EmpresaPlataformaController::class, 'toggleActiva'])->name('empresas.acceso');

    // Devuelve el acceso al administrador de un cliente que quedó afuera de su
    // propio sistema (contraseña perdida o bloqueo por intentos fallidos).
    // Nadie dentro de esa empresa puede resolverlo si el administrador es uno
    // solo, así que la acción vive en el nivel de plataforma.
    Route::post('/empresas/{empresa}/usuarios/{usuario}/restablecer-acceso', [EmpresaPlataformaController::class, 'restablecerAccesoAdmin'])
        ->name('empresas.restablecer-acceso');
    Route::post('/empresas/{empresa}/entrar', [EmpresaPlataformaController::class, 'entrar'])->name('empresas.entrar');
    Route::post('/salir-de-empresa', [EmpresaPlataformaController::class, 'salir'])->name('empresas.salir');

    /*
    | Catálogos GLOBALES compartidos por todas las empresas.
    |
    | La escritura queda a nivel de plataforma porque una fila de estas tablas
    | la usan todos los clientes: si el administrador de una empresa borrara
    | la provincia "Misiones" o el estado "Activo", rompería los datos de las
    | demás. Los administradores de empresa los ven en modo lectura desde
    | /admin (ver el grupo de maestros).
    |
    | Los `estados_*` además están acoplados al código: el parámetro
    | `estados_pausa_sla` referencia sus nombres, de modo que renombrarlos
    | altera el cálculo del SLA de reparaciones.
    */
    Route::resource('provincias', \App\Http\Controllers\Admin\ProvinciaController::class)
        ->except(['index', 'show'])->names('catalogos.provincias');
    Route::resource('localidades', \App\Http\Controllers\Admin\LocalidadController::class)
        ->except(['index', 'show'])->names('catalogos.localidades');
    Route::resource('estados-producto', \App\Http\Controllers\Admin\EstadoProductoController::class)
        ->except(['index', 'show'])->names('catalogos.estados-producto');
    Route::resource('estados-cliente', \App\Http\Controllers\Admin\EstadoClienteController::class)
        ->except(['index', 'show'])->names('catalogos.estados-cliente');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// --- RUTAS VERIFICADAS Y DE ADMINISTRACIÓN ---
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
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

        // --- Catálogos PROPIOS de cada empresa (CRUD completo) ---
        // Llevan empresa_id y Global Scope: cada empresa ve y edita solo los
        // suyos. Son decisiones de negocio de cada cliente.
        Route::resource('categorias', CategoriaProductoController::class);
        Route::resource('depositos', \App\Http\Controllers\Admin\DepositoController::class);
        Route::resource('unidades-medida', \App\Http\Controllers\Admin\UnidadMedidaController::class);
        Route::resource('tipos-cliente', \App\Http\Controllers\Admin\TipoClienteController::class);
        Route::resource('medios-pago', \App\Http\Controllers\Admin\MedioPagoController::class);

        // --- Catálogos GLOBALES: solo lectura para el admin de empresa ---
        // La geografía argentina es la misma para todas las empresas y los
        // estados están acoplados al código (el parámetro `estados_pausa_sla`
        // referencia sus nombres). Son tablas compartidas entre clientes
        // distintos, así que permitir escritura dejaría que una empresa
        // rompiera el sistema de otra. La escritura vive más abajo, bajo
        // el middleware `super`.
        Route::resource('provincias', \App\Http\Controllers\Admin\ProvinciaController::class)
            ->only(['index', 'show']);
        Route::resource('localidades', \App\Http\Controllers\Admin\LocalidadController::class)
            ->only(['index', 'show']);
        Route::resource('estados-producto', \App\Http\Controllers\Admin\EstadoProductoController::class)
            ->only(['index', 'show']);
        Route::resource('estados-cliente', \App\Http\Controllers\Admin\EstadoClienteController::class)
            ->only(['index', 'show']);
        // NOTA: estados-reparacion ELIMINADO - Los estados son FIJOS (no parametrizables)
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

/*
| --- RUTAS API ---
|
| Estas dos alimentan selects dependientes (provincia -> localidad,
| marca -> modelo) y estaban declaradas sin middleware, o sea abiertas sin
| autenticar. En el caso de los modelos eso exponía datos de empresa: el
| scope de Modelo hereda el tenant de la marca, pero sin usuario autenticado
| TenantContext no resuelve ninguna empresa y por lo tanto no restringe, asi
| que devolvia los modelos de todas. Van dentro de `auth` + `tenant`.
*/
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/api/provincias/{provincia}/localidades', [LocalidadController::class, 'getLocalidadesByProvincia'])
        ->name('api.localidades.por-provincia');

    // API de Modelos por Marca
    Route::get('/api/marcas/{marca}/modelos', [\App\Http\Controllers\API\ModeloController::class, 'index'])
        ->name('api.modelos.por-marca');
});

// --- API CONFIGURABLE SELECTS (CRUD RÁPIDO) ---
Route::middleware(['auth', 'tenant'])->prefix('api')->group(function () {
    // Tipos de Cliente
    Route::get('/tipos-cliente', [\App\Http\Controllers\Api\TipoClienteApiController::class, 'index']);
    Route::post('/tipos-cliente', [\App\Http\Controllers\Api\TipoClienteApiController::class, 'store']);
    Route::delete('/tipos-cliente/{tipoCliente}', [\App\Http\Controllers\Api\TipoClienteApiController::class, 'destroy']);

    // Estados de Cliente: GLOBALES y acoplados al código, solo lectura.
    Route::get('/estados-cliente', [\App\Http\Controllers\Api\EstadoClienteApiController::class, 'index']);

    // Provincias y Localidades: GLOBALES, solo lectura.
    // La escritura exige nivel de plataforma (ver el grupo `super` más abajo),
    // porque son tablas compartidas por todas las empresas.
    Route::get('/provincias', [\App\Http\Controllers\Api\ProvinciaApiController::class, 'index']);
    Route::get('/localidades', [\App\Http\Controllers\Api\LocalidadApiController::class, 'index']);

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

    // Estados de Producto: GLOBALES y acoplados al código, solo lectura.
    Route::get('/estados-producto', [\App\Http\Controllers\Api\EstadoProductoApiController::class, 'index']);

    // Medios de Pago
    Route::get('/medios-pago', [\App\Http\Controllers\Api\MedioPagoApiController::class, 'index']);
    Route::post('/medios-pago', [\App\Http\Controllers\Api\MedioPagoApiController::class, 'store']);
    Route::delete('/medios-pago/{medioPago}', [\App\Http\Controllers\Api\MedioPagoApiController::class, 'destroy']);

    // Tipos de Movimiento de Stock: GLOBALES y acoplados al código
    // (los servicios de stock los buscan por nombre), solo lectura.
    Route::get('/tipos-movimiento-stock', [\App\Http\Controllers\Api\TipoMovimientoStockApiController::class, 'index']);
});

/*
| Escritura de los catálogos GLOBALES vía API, a nivel de plataforma.
|
| Son los mismos endpoints que antes vivían en el grupo de empresa: se
| movieron acá para que una empresa no pueda alterar tablas que usan todas
| las demás. Quedan disponibles para el operador del sistema.
*/
Route::middleware(['auth', 'super'])->prefix('api/plataforma')->group(function () {
    Route::post('/provincias', [\App\Http\Controllers\Api\ProvinciaApiController::class, 'store']);
    Route::delete('/provincias/{provincia}', [\App\Http\Controllers\Api\ProvinciaApiController::class, 'destroy']);

    Route::post('/localidades', [\App\Http\Controllers\Api\LocalidadApiController::class, 'store']);
    Route::delete('/localidades/{localidad}', [\App\Http\Controllers\Api\LocalidadApiController::class, 'destroy']);

    Route::post('/estados-cliente', [\App\Http\Controllers\Api\EstadoClienteApiController::class, 'store']);
    Route::delete('/estados-cliente/{estadoCliente}', [\App\Http\Controllers\Api\EstadoClienteApiController::class, 'destroy']);

    Route::post('/estados-producto', [\App\Http\Controllers\Api\EstadoProductoApiController::class, 'store']);
    Route::delete('/estados-producto/{estadoProducto}', [\App\Http\Controllers\Api\EstadoProductoApiController::class, 'destroy']);

    Route::post('/tipos-movimiento-stock', [\App\Http\Controllers\Api\TipoMovimientoStockApiController::class, 'store']);
    Route::delete('/tipos-movimiento-stock/{tipoMovimientoStock}', [\App\Http\Controllers\Api\TipoMovimientoStockApiController::class, 'destroy']);
});

// --- API DE NOTIFICACIONES ---
Route::middleware(['auth', 'tenant'])->group(function () {
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
            $validated['codigo'] = 'MOT_'.$base.'_'.substr(time(), -4);
        }

        // Asegurar que el código sea único
        $baseCode = $validated['codigo'];
        $counter = 1;
        while (\App\Models\MotivoDemoraReparacion::where('codigo', $validated['codigo'])->exists()) {
            $validated['codigo'] = $baseCode.'_'.$counter++;
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
        $motivo->update(['activo' => ! $motivo->activo]);

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
Route::middleware(['auth', 'tenant'])->group(function () {
    /*
    | --- MÓDULO DE REPORTES Y ESTADÍSTICAS (CU-33 a CU-37) ---
    |
    | Antes todo el prefijo estaba detrás de `role:administrador`, lo que dejaba
    | los permisos `reportes.*` del config como casillas que no hacían nada: se
    | asignaban en la pantalla de roles y nunca se verificaban. Ahora se valida
    | por permiso, igual que el resto del sistema.
    |
    | `reportes.ver` es solo la puerta de la sección, y cada reporte exige
    | ADEMÁS el suyo. Son dos middleware encadenados y no uno con dos
    | parámetros, porque `permiso:a,b` significa "alcanza con uno" y acá hacen
    | falta los dos. Si se gateara todo con `reportes.ver` a secas, dar acceso
    | al reporte de reparaciones abriría también el de facturación.
    |
    | Exportar exige el permiso del reporte MÁS `reportes.exportar`: mirar una
    | pantalla y llevarse el archivo son dos cosas distintas.
    */
    Route::prefix('reportes')->name('reportes.')->middleware('permiso:reportes.ver')->group(function () {

        /*
        | Ruta base /reportes → al primer reporte que el usuario pueda ver.
        |
        | Antes mandaba siempre al mensual, que ahora tiene su propio permiso:
        | un técnico con `reportes.ver` y `reportes.reparaciones` entraría a
        | /reportes y rebotaría contra un 403 sin haber hecho nada mal.
        */
        Route::get('/', function () {
            $destinos = [
                'reportes.mensual' => 'reportes.mensual',
                'reportes.ventas' => 'reportes.ventas',
                'reportes.reparaciones' => 'reportes.reparaciones',
                'reportes.compras' => 'reportes.compras',
                'reportes.stock' => 'reportes.stock',
            ];

            foreach ($destinos as $permiso => $ruta) {
                if (auth()->user()?->hasPermission($permiso)) {
                    return redirect()->route($ruta);
                }
            }

            // Tiene `reportes.ver` pero ningún reporte concreto habilitado.
            abort(403);
        });

        // CU-33: Reporte de Ventas
        Route::get('/ventas', [\App\Http\Controllers\Reportes\ReporteVentaController::class, 'index'])
            ->middleware('permiso:reportes.ventas')->name('ventas');
        Route::get('/ventas/exportar', [\App\Http\Controllers\Reportes\ReporteVentaController::class, 'exportar'])
            ->middleware(['permiso:reportes.ventas', 'permiso:reportes.exportar'])->name('ventas.exportar');

        // CU-34: Reporte de Reparaciones
        Route::get('/reparaciones', [\App\Http\Controllers\Reportes\ReporteReparacionController::class, 'index'])
            ->middleware('permiso:reportes.reparaciones')->name('reparaciones');
        Route::get('/reparaciones/exportar', [\App\Http\Controllers\Reportes\ReporteReparacionController::class, 'exportar'])
            ->middleware(['permiso:reportes.reparaciones', 'permiso:reportes.exportar'])->name('reparaciones.exportar');

        // CU-35: Reporte de Compras
        Route::get('/compras', [\App\Http\Controllers\Reportes\ReporteCompraController::class, 'index'])
            ->middleware('permiso:reportes.compras')->name('compras');
        Route::get('/compras/exportar', [\App\Http\Controllers\Reportes\ReporteCompraController::class, 'exportar'])
            ->middleware(['permiso:reportes.compras', 'permiso:reportes.exportar'])->name('compras.exportar');

        // CU-36 y CU-37: Reporte de Stock
        Route::get('/stock', [\App\Http\Controllers\Reportes\ReporteStockController::class, 'index'])
            ->middleware('permiso:reportes.stock')->name('stock');
        Route::get('/stock/exportar', [\App\Http\Controllers\Reportes\ReporteStockController::class, 'exportar'])
            ->middleware(['permiso:reportes.stock', 'permiso:reportes.exportar'])->name('stock.exportar');

        // CU-36: Reporte de Uso de Repuestos (consumo de stock, va con stock)
        Route::get('/repuestos', [\App\Http\Controllers\Reportes\ReporteRepuestoController::class, 'index'])
            ->middleware('permiso:reportes.stock')->name('repuestos');
        Route::get('/repuestos/exportar', [\App\Http\Controllers\Reportes\ReporteRepuestoController::class, 'exportar'])
            ->middleware(['permiso:reportes.stock', 'permiso:reportes.exportar'])->name('repuestos.exportar');

        // Reporte Mensual Consolidado (Entradas, Salidas, Balance). Tiene su
        // propio permiso porque es el balance del negocio, el dato más
        // sensible del sistema después de los datos personales.
        Route::get('/mensual', [\App\Http\Controllers\Reportes\ReporteMensualController::class, 'index'])
            ->middleware('permiso:reportes.mensual')->name('mensual');
        Route::get('/mensual/exportar', [\App\Http\Controllers\Reportes\ReporteMensualController::class, 'exportar'])
            ->middleware(['permiso:reportes.mensual', 'permiso:reportes.exportar'])->name('mensual.exportar');
    });

    // --- MÓDULO DE GASTOS Y PÉRDIDAS ---
    Route::resource('gastos', \App\Http\Controllers\GastoController::class)->except(['show']);
    Route::patch('/gastos/{gasto}/anular', [\App\Http\Controllers\GastoController::class, 'anular'])->name('gastos.anular');

    // --- MÓDULO DE VENTAS ---
    // Enforcement por permiso. Se definen rutas explícitas (solo las acciones que
    // el controller implementa): index/create/store/show. Antes había un resource
    // que registraba edit/update/destroy MUERTAS (sin método en el controller).
    Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])
        ->middleware('permiso:ventas.anular')->name('ventas.anular');

    Route::get('/ventas/{venta}/imprimir', [VentaController::class, 'imprimirComprobante'])
        ->middleware('permiso:ventas.ver')->name('ventas.imprimir');

    Route::get('/ventas/{venta}/imprimir-anulacion', [VentaController::class, 'imprimirComprobanteAnulacion'])
        ->middleware('permiso:ventas.ver')->name('ventas.imprimir-anulacion');

    Route::get('/ventas', [VentaController::class, 'index'])
        ->middleware('permiso:ventas.ver')->name('ventas.index');
    Route::get('/ventas/create', [VentaController::class, 'create'])
        ->middleware('permiso:ventas.crear')->name('ventas.create');
    Route::post('/ventas', [VentaController::class, 'store'])
        ->middleware('permiso:ventas.crear')->name('ventas.store');
    Route::get('/ventas/{venta}', [VentaController::class, 'show'])
        ->middleware('permiso:ventas.ver')->name('ventas.show');

    // --- MÓDULO DE DESCUENTOS (CU-08) ---
    Route::resource('descuentos', DescuentoController::class);

    // --- MÓDULO DE PAGOS (CU-10) ---
    Route::prefix('pagos')->name('pagos.')->group(function () {
        // Listado (Index)
        Route::get('/', [PagoController::class, 'index'])
            ->middleware('permiso:pagos.ver')->name('index');
        // Formulario de Carga (Create)
        Route::get('/crear', [PagoController::class, 'create'])
            ->middleware('permiso:pagos.crear')->name('create');
        // Obtener documentos pendientes de un cliente (CU-10 Paso 6) — parte del alta
        Route::get('/cliente/{cliente}/documentos-pendientes', [PagoController::class, 'obtenerDocumentosPendientes'])
            ->middleware('permiso:pagos.ver')->name('cliente.documentos');
        // Guardar Pago (Store)
        Route::post('/', [PagoController::class, 'store'])
            ->middleware('permiso:pagos.crear')->name('store');
        // Ver Recibo (Show)
        Route::get('/{pago}', [PagoController::class, 'show'])
            ->middleware('permiso:pagos.ver')->name('show');
        // Imprimir Recibo (CU-10 Paso 12 - Kendall)
        Route::get('/{pago}/imprimir', [PagoController::class, 'imprimirRecibo'])
            ->middleware('permiso:pagos.ver')->name('imprimir');
        // Anular Pago (solo quien tiene el permiso; el vendedor no lo tiene)
        Route::delete('/{pago}', [PagoController::class, 'anular'])
            ->middleware('permiso:pagos.anular')->name('anular');
    });

    // --- MÓDULO DE CLIENTES (CU-01) ---
    Route::prefix('clientes')->name('clientes.')->group(function () {
        Route::get('/', [ClienteController::class, 'index'])->middleware('permiso:clientes.ver')->name('index');
        Route::get('/crear', [ClienteController::class, 'create'])->middleware('permiso:clientes.crear')->name('create');
        Route::get('/{cliente}', [ClienteController::class, 'show'])->middleware('permiso:clientes.ver')->name('show');
        Route::get('/{cliente}/editar', [ClienteController::class, 'edit'])->middleware('permiso:clientes.editar')->name('edit');
        Route::post('/', [ClienteController::class, 'store'])->middleware('permiso:clientes.crear')->name('store');
        Route::put('/{cliente}', [ClienteController::class, 'update'])->middleware('permiso:clientes.editar')->name('update');
        // La baja lógica es una modificación → permiso 'editar'.
        Route::get('/{cliente}/verificar-baja', [ClienteController::class, 'verificarBaja'])->middleware('permiso:clientes.editar')->name('verificarBaja');
        Route::get('/{cliente}/confirmar-baja', [ClienteController::class, 'confirmDelete'])->middleware('permiso:clientes.editar')->name('confirmDelete');
        Route::post('/{cliente}/dar-de-baja', [ClienteController::class, 'darDeBaja'])->middleware('permiso:clientes.editar')->name('darDeBaja');
    });

    /*
    | --- MÓDULO DE PROVEEDORES (CU-16 a CU-19) ---
    | Ubicado aquí para acceso operativo (Admin/Compras).
    |
    | El resource estaba declarado SIN `permiso:`, de modo que los permisos
    | `proveedores.*` existían en el config y se asignaban en la UI de roles
    | pero nunca se verificaban: cualquier usuario autenticado podía listar,
    | crear, editar y borrar proveedores con su CUIT y su domicilio. Cada
    | verbo queda asociado al permiso que le corresponde.
    */
    Route::resource('proveedores', ProveedorController::class)
        ->parameters(['proveedores' => 'proveedor'])
        ->middlewareFor(['index', 'show'], 'permiso:proveedores.ver')
        ->middlewareFor(['create', 'store'], 'permiso:proveedores.crear')
        ->middlewareFor(['edit', 'update', 'destroy'], 'permiso:proveedores.editar');

    // Ruta adicional para actualizar calificación (CU-21)
    Route::patch('proveedores/{proveedor}/calificacion', [ProveedorController::class, 'actualizarCalificacion'])
        ->middleware('permiso:proveedores.editar')
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

    // --- MÓDULO DE PRODUCTOS ---
    Route::prefix('productos')->name('productos.')->group(function () {
        // CU-29: Consultar Stock
        Route::get('/consultar-stock', [ProductoController::class, 'stock'])->middleware('permiso:productos.ver')->name('stock');

        // CU-28: Catálogo (Index)
        Route::get('/', [ProductoController::class, 'index'])->middleware('permiso:productos.ver')->name('index');

        // CU-25: Registrar (Create & Store)
        Route::get('/crear', [ProductoController::class, 'create'])->middleware('permiso:productos.crear')->name('create');
        Route::post('/', [ProductoController::class, 'store'])->middleware('permiso:productos.crear')->name('store');

        // CU-28: Ver Detalle (Show)
        Route::get('/{producto}', [ProductoController::class, 'show'])->middleware('permiso:productos.ver')->name('show');

        // CU-26: Modificar (Edit & Update)
        Route::get('/{producto}/editar', [ProductoController::class, 'edit'])->middleware('permiso:productos.editar')->name('edit');
        Route::put('/{producto}', [ProductoController::class, 'update'])->middleware('permiso:productos.editar')->name('update');

        // CU-27: Dar de Baja
        Route::post('/{producto}/dar-de-baja', [ProductoController::class, 'darDeBaja'])->middleware('permiso:productos.eliminar')->name('darDeBaja');
    });

    // Ruta para CU-30: Ajuste de Stock
    Route::post('/stock/movimiento', [StockController::class, 'storeMovimiento'])
        ->name('stock.movimiento.store');

    // NUEVA RUTA: Actualizar Configuración de Stock (Mínimos)
    Route::put('/stock/{stock}', [StockController::class, 'update'])
        ->name('stock.update');

    // --- MÓDULO DE AUDITORÍA ---
    // Consulta restringida a administradores; scoping multi-tenant en el controller.
    Route::middleware('role:administrador')->group(function () {
        Route::get('/auditorias', [\App\Http\Controllers\AuditoriaController::class, 'index'])
            ->name('auditorias.index');
        Route::get('/auditorias/exportar/{formato}', [\App\Http\Controllers\AuditoriaController::class, 'exportar'])
            ->where('formato', 'csv|pdf')
            ->name('auditorias.exportar');
    });

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

    // --- MI EMPRESA (multi-tenant: edicion de la empresa del usuario) ---
    Route::prefix('empresa')->name('empresa.')->middleware(['role:administrador'])->group(function () {
        Route::get('/', [\App\Http\Controllers\EmpresaController::class, 'edit'])->name('edit');
        Route::put('/', [\App\Http\Controllers\EmpresaController::class, 'update'])->name('update');
        Route::delete('/logo', [\App\Http\Controllers\EmpresaController::class, 'deleteLogo'])->name('logo.destroy');
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
    // PILOTO de enforcement por PERMISO (el admin siempre pasa; ver PermisoMiddleware).
    Route::prefix('reparaciones')->name('reparaciones.')->group(function () {
        // Listado (ver)
        Route::get('/', [ReparacionController::class, 'index'])
            ->middleware('permiso:reparaciones.ver')->name('index');

        // Registrar ingreso — rutas LITERALES antes de las paramétricas
        // (recepción/vendedor; el técnico no hace ingresos).
        Route::get('/crear', [ReparacionController::class, 'create'])
            ->middleware('permiso:reparaciones.crear')->name('create');
        Route::post('/', [ReparacionController::class, 'store'])
            ->middleware('permiso:reparaciones.crear')->name('store');

        // Ver detalle / comprobantes (ver)
        Route::get('/{reparacion}', [ReparacionController::class, 'show'])
            ->middleware('permiso:reparaciones.ver')->name('show');
        Route::get('/{reparacion}/imprimir-ingreso', [ReparacionController::class, 'imprimirComprobanteIngreso'])
            ->middleware('permiso:reparaciones.ver')->name('imprimir-ingreso');
        Route::get('/{reparacion}/imprimir-entrega', [ReparacionController::class, 'imprimirComprobanteEntrega'])
            ->middleware('permiso:reparaciones.ver')->name('imprimir-entrega');

        // Editar datos / cambiar estado (basta uno de los dos permisos)
        Route::get('/{reparacion}/editar', [ReparacionController::class, 'edit'])
            ->middleware('permiso:reparaciones.editar,reparaciones.cambiar_estado')->name('edit');
        Route::put('/{reparacion}', [ReparacionController::class, 'update'])
            ->middleware('permiso:reparaciones.editar,reparaciones.cambiar_estado')->name('update');

        // Cobrar: solo quienes manejan plata (no el técnico).
        Route::post('/{reparacion}/cobrar', [ReparacionController::class, 'cobrar'])
            ->middleware('role:administrador,vendedor')->name('cobrar');
        // Anular: revierte stock → restringido al administrador.
        Route::delete('/{reparacion}', [ReparacionController::class, 'destroy'])
            ->middleware('role:administrador')->name('destroy');
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
        Route::get('/create-directo', [\App\Http\Controllers\OrdenCompraController::class, 'createDirecto'])->name('create-directo'); // OC sin cotización
        Route::post('/store-directo', [\App\Http\Controllers\OrdenCompraController::class, 'storeDirecto'])->name('store-directo'); // Guardar OC directa
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
        Route::get('/crear-directo', [\App\Http\Controllers\Compras\RecepcionMercaderiaController::class, 'createDirecto'])->name('create-directo'); // Sin OC
        Route::post('/store-directo', [\App\Http\Controllers\Compras\RecepcionMercaderiaController::class, 'storeDirecto'])->name('store-directo'); // Guardar sin OC
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

    // Egresos de Stock (robo, pérdida, defectuosos, etc.)
    Route::prefix('egresos-stock')->name('egresos-stock.')->middleware('role:administrador,deposito')->group(function () {
        Route::get('/', [\App\Http\Controllers\EgresoStockController::class, 'index'])->name('index');
        Route::get('/crear', [\App\Http\Controllers\EgresoStockController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\EgresoStockController::class, 'store'])->name('store');
        Route::get('/{egreso}', [\App\Http\Controllers\EgresoStockController::class, 'show'])->name('show');
    });

});
