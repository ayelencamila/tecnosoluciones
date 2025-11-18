<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Cliente;
use App\Services\Pagos\AnularPagoService;
use App\Services\Pagos\RegistrarPagoService;
use App\Http\Requests\Pagos\StorePagoRequest;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Exception;

class PagoController extends Controller
{
    /**
     * Muestra el listado de pagos (CU-10).
     */
    public function index(Request $request)
    {
        $pagos = Pago::query()
            ->with(['cliente:clienteID,nombre,apellido,DNI', 'cajero:id,name'])
            ->select('pago_id', 'clienteID', 'user_id', 'numero_recibo', 'fecha_pago', 'monto', 'metodo_pago', 'anulado')
            ->when($request->input('search'), function (Builder $query, $search) {
                $query->where('numero_recibo', 'like', "%{$search}%")
                    ->orWhereHas('cliente', fn($q) => $q->where('nombre', 'like', "%{$search}%")->orWhere('apellido', 'like', "%{$search}%"));
            })
            ->when($request->input('cliente_id'), fn(Builder $q, $id) => $q->where('clienteID', $id))
            ->when($request->input('fecha_desde'), fn(Builder $q, $fecha) => $q->whereDate('fecha_pago', '>=', $fecha))
            ->when($request->input('fecha_hasta'), fn(Builder $q, $fecha) => $q->whereDate('fecha_pago', '<=', $fecha))
            ->orderBy('fecha_pago', 'desc')
            ->paginate(15)
            ->withQueryString();

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
        // Pasamos solo clientes que tengan cuenta corriente
        $clientes = Cliente::whereHas('cuentaCorriente')
                            ->select('clienteID', 'nombre', 'apellido', 'DNI')
                            ->orderBy('apellido')
                            ->get();

        return Inertia::render('Pagos/FormularioPago', [
            'clientes' => $clientes
        ]);
    }

    /**
     * Almacena un nuevo pago en la base de datos (CU-10).
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
                   ->with('success', '¡Pago registrado con éxito!');

        } catch (Exception $e) {
            Log::error("Error al registrar pago: " . $e->getMessage());
            return back()->with('error', 'Error inesperado al registrar el pago. Contacte a soporte.');
        }
    }

    /**
     * Muestra el detalle de un pago (el Recibo).
     */
    public function show(Pago $pago)
    {
        // Cargamos las relaciones para mostrar el recibo completo
        $pago->load(['cliente', 'cajero']);

        return Inertia::render('Pagos/DetallePago', [
            'pago' => $pago
        ]);
    }
    public function anular(Pago $pago, AnularPagoService $anularPagoService)
    {
        // Opcional: Agregar una Policy de seguridad aquí
        // $this->authorize('anular', $pago); 

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