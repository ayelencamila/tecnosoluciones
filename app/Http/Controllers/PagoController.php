<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Cliente;
use App\Services\Pagos\AnularPagoService;
use App\Services\Pagos\RegistrarPagoService;
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
            ->with(['cliente:clienteID,nombre,apellido,DNI', 'cajero:id,name'])
            ->select('pago_id', 'clienteID', 'user_id', 'numero_recibo', 'fecha_pago', 'monto', 'metodo_pago', 'anulado')
            // --- FILTROS QUE FALTABAN ---
            ->when($request->input('search'), function (Builder $query, $search) {
                $query->where('numero_recibo', 'like', "%{$search}%")
                    ->orWhereHas('cliente', fn($q) => $q->where('nombre', 'like', "%{$search}%")->orWhere('apellido', 'like', "%{$search}%")->orWhere('DNI', 'like', "%{$search}%"));
            })
            ->when($request->input('cliente_id'), fn(Builder $q, $id) => $q->where('clienteID', $id))
            ->when($request->input('fecha_desde'), fn(Builder $q, $fecha) => $q->whereDate('fecha_pago', '>=', $fecha))
            ->when($request->input('fecha_hasta'), fn(Builder $q, $fecha) => $q->whereDate('fecha_pago', '<=', $fecha))
            // -----------------------------
            ->orderBy('fecha_pago', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Pagos/ListadoPagos', [
            'pagos' => $pagos,
            'filters' => $request->only(['search', 'cliente_id', 'fecha_desde', 'fecha_hasta']),
            // ESTA ES LA VARIABLE QUE FALTABA:
            'clientes_filtro' => Cliente::select('clienteID', 'nombre', 'apellido')->orderBy('apellido')->get()
        ]);
    }

    /**
     * Muestra el formulario para registrar un nuevo pago (CU-10).
     */
    public function create()
    {
        // Solo mostramos clientes con Cuenta Corriente activa para imputar pagos
        $clientes = Cliente::whereHas('cuentaCorriente')
            ->select('clienteID', 'nombre', 'apellido', 'DNI')
            ->with('cuentaCorriente:cuentaCorrienteID,saldo,estadoCuentaCorrienteID') // Traer saldo para mostrar
            ->orderBy('apellido')
            ->get();

        return Inertia::render('Pagos/FormularioPago', [
            'clientes' => $clientes
        ]);
    }

    /**
     * Almacena un nuevo pago en la base de datos 
     */
    public function store(StorePagoRequest $request, RegistrarPagoService $registrarPagoService)
    {
        try {
            // El StorePagoRequest ya validó los datos
            $datosValidados = $request->validated();
            
            // Llamamos al cerebro (Servicio)
            $pago = $registrarPagoService->handle($datosValidados, auth()->id());

            // Redirigimos al recibo (show)
            return to_route('pagos.show', $pago->pago_id)
                   ->with('success', '¡Pago registrado e imputado con éxito!');

        } catch (Exception $e) {
            Log::error("Error al registrar pago: " . $e->getMessage());
            return back()->withErrors(['message' => 'Error inesperado al registrar el pago. Contacte a soporte.']);
        }
    }

    /**
     * Muestra el detalle de un pago (el Recibo).
     */
    public function show(Pago $pago)
    {
        // Cargamos las relaciones para mostrar el recibo completo
        $pago->load(['cliente', 'cajero', 'ventasImputadas']);

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
                   ->route('pagos.show', $pago->pago_id)
                   ->with('success', '¡Pago anulado con éxito! Se revirtió el saldo en la cuenta corriente.');

        } catch (Exception $e) {
            Log::error("Error al anular pago: " . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }
}