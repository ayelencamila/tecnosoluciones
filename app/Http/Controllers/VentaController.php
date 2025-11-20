<?php

namespace App\Http\Controllers;

use App\Exceptions\Ventas\LimiteCreditoExcedidoException;
use App\Exceptions\Ventas\SinStockException;
use App\Exceptions\Ventas\VentaYaAnuladaException;
use App\Http\Requests\Ventas\AnularVentaRequest;
use App\Http\Requests\Ventas\StoreVentaRequest;
use App\Models\Cliente;
use App\Models\Descuento;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\Ventas\AnularVentaService;
use App\Services\Ventas\RegistrarVentaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia; 

class VentaController extends Controller
{
    use AuthorizesRequests; // Permite usar $this->authorize()

    /**
     * Muestra el listado de ventas con filtros (CU-07).
     * Boundary: ListadoVentas.vue
     */
    public function index(Request $request)
    {
        // 1. Iniciar Query
        $ventas = Venta::query()
            ->with(['cliente:clienteID,nombre,apellido,DNI', 'vendedor:id,name']) // Eager Loading optimizado
            ->select('venta_id', 'clienteID', 'user_id', 'numero_comprobante', 'fecha_venta', 'total', 'anulada', 'observaciones');

        // 2. Aplicar Filtros

        // Filtro por Rango de Fechas
        $ventas->when($request->input('fecha_desde'), function (Builder $query, $fecha) {
            $query->whereDate('fecha_venta', '>=', $fecha);
        });
        $ventas->when($request->input('fecha_hasta'), function (Builder $query, $fecha) {
            $query->whereDate('fecha_venta', '<=', $fecha);
        });

        // Filtro por Cliente
        $ventas->when($request->input('cliente_id'), function (Builder $query, $id) {
            $query->where('clienteID', $id);
        });

        // Filtro por Estado (Anulada / Activa)
        $ventas->when($request->input('estado'), function (Builder $query, $estado) {
            if ($estado === 'anulada') {
                $query->where('anulada', true);
            } elseif ($estado === 'activa') {
                $query->where('anulada', false);
            }
        });

        // Búsqueda general (Comprobante o Nombre Cliente)
        $ventas->when($request->input('search'), function (Builder $query, $search) {
            $query->where(function ($subQ) use ($search) {
                $subQ->where('numero_comprobante', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($qClient) use ($search) {
                        $qClient->where('nombre', 'like', "%{$search}%")
                            ->orWhere('apellido', 'like', "%{$search}%");
                    });
            });
        });

        // 3. Ordenamiento y Paginación
        $ventasPaginadas = $ventas->orderBy('fecha_venta', 'desc')
            ->paginate(10)
            ->withQueryString(); // Mantiene los filtros en la URL al cambiar de página

        // 4. Retornar Vista Inertia
        return Inertia::render('Ventas/ListadoVentas', [
            'ventas' => $ventasPaginadas,
            'filters' => $request->only(['fecha_desde', 'fecha_hasta', 'cliente_id', 'estado', 'search']),
            // Pasamos lista simple de clientes para el filtro desplegable
            'clientes_filtro' => Cliente::select('clienteID', 'nombre', 'apellido')->orderBy('apellido')->get(),
        ]);
    }

    /**
     * Muestra el detalle de una venta específica (CU-07).
     * Boundary: DetalleVenta.vue
     */
    public function show(Venta $venta)
    {
        // Cargar todas las relaciones necesarias para mostrar el detalle completo
        $venta->load([
            'cliente',
            'vendedor',
            'detalles.producto', // Carga producto dentro de cada detalle
            'descuentos',         // Descuentos globales
        ]);

        return Inertia::render('Ventas/DetalleVenta', [
            'venta' => $venta,
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva venta (Boundary: FormularioVenta).
     */
    public function create()
    {
        // FIX 1: Carga las relaciones de CC y tipoCliente para la validación en Vue
        $clientes = Cliente::select('clienteID', 'nombre', 'apellido', 'DNI', 'tipoClienteID')
             ->with([
                 // Usando el with optimizado para las relaciones de CC y estado
                 'cuentaCorriente:cuentaCorrienteID,limiteCredito,diasGracia,estadoCuentaCorrienteID', 
                 'cuentaCorriente.estadoCuentaCorriente:estadoCuentaCorrienteID,nombreEstado'
             ])
            ->get();

        $productos = Producto::whereHas('estado', function ($query) {
            $query->where('nombre', 'Activo');
        })
            // FIX 2: Quitamos 'stockActual' (obsoleto) y cargamos la relación 'stocks'
            // para que el Accessor 'stock_total' del modelo Producto funcione en Vue/Store.
            ->select('id', 'codigo', 'nombre') 
            ->with(['precios:id,productoID,tipoClienteID,precio', 'stocks']) 
            ->get();

        $descuentos = Descuento::where('activo', true)
            ->where(function ($query) {
                $query->where('valido_hasta', '>=', now())->orWhereNull('valido_hasta');
            })
            ->select('descuento_id', 'codigo', 'descripcion', 'tipo', 'valor', 'aplicabilidad')
            ->get();

        return Inertia::render('Ventas/FormularioVenta', [
            'clientes' => $clientes,
            'productos' => $productos,
            'descuentos' => $descuentos,
        ]);
    }

    /**
     * Guarda una nueva Venta en la base de datos (CU-05).
     */
    public function store(StoreVentaRequest $request, RegistrarVentaService $registrarVentaService)
    {
        // Aplicamos la Policy (RBAC) para esta acción.
        $this->authorize('create', Venta::class); 
        
        $datosValidados = $request->validated();
        $datosValidados['userID'] = auth()->id();

        try {
            $venta = $registrarVentaService->handle($datosValidados);

            // Paso 12: Confirma el registro exitoso
            return to_route('ventas.show', $venta->venta_id)
                ->with('success', '¡Venta registrada con éxito!');

        } catch (SinStockException|LimiteCreditoExcedidoException $e) {
            // FIX 3: Manejo consistente de errores de negocio para Inertia
            return back()->withErrors(['message' => $e->getMessage()]);

        } catch (\Exception $e) {
            Log::error('Error catastrófico al registrar venta: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except('password', 'password_confirmation'),
            ]);

            // FIX 3: Manejo consistente de errores de sistema
            return back()->withErrors(['message' => 'Error inesperado al procesar la venta. Contacte a soporte.']);
        }
    }

    /**
     * Anula una venta específica (CU-06).
     */
    public function anular(AnularVentaRequest $request, Venta $venta, AnularVentaService $anularVentaService)
    {
        // Aplicamos la Policy (RBAC) para esta acción.
        $this->authorize('anular', $venta);

        $datosValidados = $request->validated();
        $motivoAnulacion = $datosValidados['motivo_anulacion'];
        $userID = auth()->id();

        try {
            // Paso 8: Procesa la anulación (delegado al Service)
            $anularVentaService->handle($venta, $motivoAnulacion, $userID);

            // Paso 10: Confirma la anulación exitosa
            return to_route('ventas.show', $venta->venta_id)
                ->with('success', '¡Venta anulada con éxito!');

        } catch (VentaYaAnuladaException $e) {
             // Excepción de negocio (controlada)
            return back()->with('error', $e->getMessage()); 

        } catch (\Exception $e) {
            Log::error("Error catastrófico al anular venta {$venta->venta_id}: ".$e->getMessage(), [
                'exception' => $e,
                'venta_id' => $venta->venta_id,
                'user_id' => $userID,
                'motivo_anulacion' => $motivoAnulacion,
            ]);

            // Error de sistema (no controlado)
            return back()->with('error', 'Error inesperado al anular la venta. Contacte a soporte.');
        }
    }
}