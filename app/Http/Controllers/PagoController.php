<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Cliente;
use App\Models\MedioPago;
use App\Models\Venta;
use App\Models\Reparacion;
use App\Services\Pagos\AnularPagoService;
use App\Services\Pagos\RegistrarPagoService;
use App\Services\Comprobantes\ComprobanteService;
use App\Services\Comprobantes\RegistrarComprobanteService;
use App\Http\Requests\Pagos\StorePagoRequest;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Exception;

class PagoController extends Controller
{
    /**
     * Muestra el listado de pagos 
     */
    public function index(Request $request)
    {
        $pagos = Pago::query()
            ->with(['cliente:clienteID,nombre,apellido,DNI', 'cajero:id,name', 'medioPago']) 
            ->select('pagoID', 'clienteID', 'user_id', 'numero_recibo', 'fecha_pago', 'monto', 'medioPagoID', 'anulado')
            
            // Filtros
            ->when($request->input('search'), function (Builder $query, $search) {
                $query->where('numero_recibo', 'like', "%{$search}%")
                    ->orWhereHas('cliente', fn($q) => $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('DNI', 'like', "%{$search}%"));
            })
            ->when($request->input('cliente_id'), fn(Builder $q, $id) => $q->where('clienteID', $id))
            ->when($request->input('fecha_desde'), fn(Builder $q, $fecha) => $q->whereDate('fecha_pago', '>=', $fecha))
            ->when($request->input('fecha_hasta'), fn(Builder $q, $fecha) => $q->whereDate('fecha_pago', '<=', $fecha))
            
            ->orderBy('fecha_pago', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($p) => [
                'pagoID' => $p->pagoID, 
                'numero_recibo' => $p->numero_recibo,
                'fecha' => $p->fecha_pago->format('d/m/Y H:i'),
                'cliente' => $p->cliente ? "{$p->cliente->apellido}, {$p->cliente->nombre}" : 'Consumidor Final',
                'monto' => $p->monto,
                'medio_pago' => $p->medioPago->nombre ?? 'Desconocido', 
                'anulado' => $p->anulado,
            ]);

        // --- ¡AQUÍ FALTABA EL RETURN! ---
        return Inertia::render('Pagos/ListadoPagos', [
            'pagos' => $pagos,
            'filters' => $request->only(['search', 'cliente_id', 'fecha_desde', 'fecha_hasta']),
            'clientes_filtro' => Cliente::select('clienteID', 'nombre', 'apellido')->orderBy('apellido')->get()
        ]);
    }

    /**
     * Muestra el formulario para registrar un nuevo pago (CU-10).
     */
    public function create()
    {
        // Clientes activos Y morosos pueden registrar pagos (los morosos necesitan pagar para regularizarse)
        $clientes = Cliente::whereHas('estadoCliente', function($q) {
                $q->whereIn('nombreEstado', ['Activo', 'Moroso']);
            })
            ->select('clienteID', 'nombre', 'apellido', 'DNI', 'cuentaCorrienteID')
            ->with('cuentaCorriente:cuentaCorrienteID,saldo,estadoCuentaCorrienteID') 
            ->orderBy('apellido')
            ->get();
        
        $mediosPago = MedioPago::where('activo', true)
            ->orderBy('nombre')
            ->get(['medioPagoID', 'nombre', 'recargo_porcentaje']);

        return Inertia::render('Pagos/FormularioPago', [
            'clientes' => $clientes,
            'mediosPago' => $mediosPago
        ]);
    }

    /**
     * Obtiene los documentos pendientes (ventas) de un cliente (CU-10 Paso 6)
     */
    public function obtenerDocumentosPendientes(Cliente $cliente)
    {
        // Ventas pendientes
        $ventas = Venta::where('clienteID', $cliente->clienteID)
            ->whereHas('estado', fn($q) => $q->where('nombreEstado', '!=', 'Anulada'))
            ->with(['estado:estadoVentaID,nombreEstado'])
            ->select('venta_id', 'numero_comprobante', 'fecha_venta', 'total', 'estado_venta_id')
            ->orderBy('fecha_venta', 'asc')
            ->get()
            ->map(function ($venta) {
                return [
                    'tipo' => 'venta',
                    'id' => $venta->venta_id,
                    'venta_id' => $venta->venta_id, // Compatibilidad con frontend existente
                    'numero_comprobante' => $venta->numero_comprobante,
                    'fecha' => $venta->fecha_venta->format('d/m/Y'),
                    'fecha_venta' => $venta->fecha_venta->format('d/m/Y'), // Compatibilidad
                    'total' => $venta->total,
                    'saldo_pendiente' => $venta->saldo_pendiente,
                    'estado' => $venta->estado->nombreEstado ?? 'Desconocido',
                ];
            })
            ->filter(fn($v) => $v['saldo_pendiente'] > 0);

        // Reparaciones cobradas a cuenta corriente (pendientes de pago)
        $reparaciones = Reparacion::where('clienteID', $cliente->clienteID)
            ->where('estado_pago', 'cuenta_corriente')
            ->where('anulada', false)
            ->with('pagosImputados') // Para calcular saldo_pendiente
            ->orderBy('fecha_cobro', 'asc')
            ->get()
            ->map(function ($rep) {
                return [
                    'tipo' => 'reparacion',
                    'id' => $rep->reparacionID,
                    'reparacion_id' => $rep->reparacionID,
                    'venta_id' => null,
                    'numero_comprobante' => $rep->codigo_reparacion,
                    'fecha' => $rep->fecha_cobro?->format('d/m/Y') ?? $rep->fecha_ingreso->format('d/m/Y'),
                    'fecha_venta' => $rep->fecha_cobro?->format('d/m/Y') ?? $rep->fecha_ingreso->format('d/m/Y'),
                    'total' => (float) $rep->monto_cobrado,
                    'saldo_pendiente' => $rep->saldo_pendiente,
                    'estado' => 'Reparación (CC)',
                ];
            })
            ->filter(fn($r) => $r['saldo_pendiente'] > 0);

        $documentos = $ventas->merge($reparaciones)->values();

        return response()->json([
            'documentos' => $documentos
        ]);
    }

    /**
     * Almacena un nuevo pago en la base de datos 
     */
    public function store(StorePagoRequest $request, RegistrarPagoService $registrarPagoService)
    {
        try {
            $datosValidados = $request->validated();
            
            $pago = $registrarPagoService->handle($datosValidados, auth()->id());
            
            return to_route('pagos.show', $pago->pagoID)
                   ->with('success', '¡Pago registrado e imputado con éxito!');

        } catch (Exception $e) {
            Log::error("Error al registrar pago: " . $e->getMessage());
            return back()->withErrors(['message' => 'Error inesperado al registrar el pago. Contacte a soporte.'])->withInput();
        }
    }

    /**
     * Muestra el detalle de un pago (el Recibo).
     */
    public function show(Pago $pago)
    {
        $pago->load(['cliente', 'cajero', 'ventasImputadas', 'reparacionesImputadas', 'medioPago']);

        return Inertia::render('Pagos/DetallePago', [
            'pago' => $pago
        ]);
    }

    /**
     * Anula un pago y revierte la imputación.
     */
    public function anular(Pago $pago, AnularPagoService $anularPagoService)
    {
        try {
            $anularPagoService->handle($pago, auth()->id());

            return redirect()
                   ->route('pagos.show', $pago->pagoID)
                   ->with('success', '¡Pago anulado con éxito! Se revirtió el saldo en la cuenta corriente.');

        } catch (Exception $e) {
            Log::error("Error al anular pago: " . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Imprime el recibo de pago (CU-10 Paso 12 - Lineamientos de Kendall)
     * 
     * Objetivos de Kendall aplicados:
     * - Servir al propósito previsto: Comprobante de pago recibido
     * - Proveer a tiempo: Generación inmediata después del registro
     * - Método correcto: Vista optimizada para impresión (window.print)
     * 
     * @param Pago $pago
     * @param ComprobanteService $comprobanteService
     * @return \Illuminate\View\View
     */
    public function imprimirRecibo(Pago $pago, ComprobanteService $comprobanteService)
    {
        // El comprobante ya se registra automáticamente en RegistrarPagoService
        // Aquí solo preparamos los datos para la vista de impresión

        // Control (BCE): El servicio prepara los datos de la entidad
        $datos = $comprobanteService->prepararDatosReciboPago($pago);
        
        // Boundary (BCE): La vista Blade renderiza el comprobante
        return view('comprobantes.recibo-pago', $datos);
    }
}