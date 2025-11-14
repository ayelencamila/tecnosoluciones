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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia; // Necesario para los filtros avanzados

class VentaController extends Controller
{
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
        $clientes = Cliente::select('clienteID', 'nombre', 'apellido', 'DNI')->get();

        $productos = Producto::whereHas('estado', function ($query) {
            $query->where('nombreEstado', 'Activo');
        })
            ->select('id', 'codigo', 'nombre', 'stockActual') // OJO: Asegúrate que 'stockActual' es el accessor que definimos
            ->with('precios')
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
        // FIX 4: Autorización explícita (aunque StoreVentaRequest ya tiene true)
        $this->authorize('create', Venta::class);

        $datosValidados = $request->validated();
        $datosValidados['userID'] = auth()->id();

        try {
            $venta = $registrarVentaService->handle($datosValidados);

            return to_route('ventas.show', $venta->venta_id)
                ->with('success', '¡Venta registrada con éxito!');

        } catch (SinStockException|LimiteCreditoExcedidoException $e) {
            return back()->with('error', $e->getMessage());

        } catch (\Exception $e) {
            Log::error('Error catastrófico al registrar venta: '.$e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except('password', 'password_confirmation'),
            ]);

            return back()->with('error', 'Error inesperado al procesar la venta. Contacte a soporte.');
        }
    }

    /**
     * Anula una venta específica (CU-06).
     */
    public function anular(AnularVentaRequest $request, Venta $venta, AnularVentaService $anularVentaService)
    {
        // FIX 4: Autorización explícita
        $this->authorize('anular', $venta);

        $datosValidados = $request->validated();
        $motivoAnulacion = $datosValidados['motivo_anulacion'];
        $userID = auth()->id();

        try {
            $anularVentaService->handle($venta, $motivoAnulacion, $userID);

            return to_route('ventas.show', $venta->venta_id)
                ->with('success', '¡Venta anulada con éxito!');

        } catch (VentaYaAnuladaException $e) {
            return back()->with('error', $e->getMessage());

        } catch (\Exception $e) {
            Log::error("Error catastrófico al anular venta {$venta->venta_id}: ".$e->getMessage(), [
                'exception' => $e,
                'venta_id' => $venta->venta_id,
                'user_id' => $userID,
                'motivo_anulacion' => $motivoAnulacion,
            ]);

            return back()->with('error', 'Error inesperado al anular la venta. Contacte a soporte.');
        }
    }
}
