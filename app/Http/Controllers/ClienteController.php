<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\Direccion;
use App\Models\EstadoCliente;
use App\Models\EstadoCuentaCorriente;
use App\Models\Provincia;
use App\Models\TipoCliente;
use App\Models\Auditoria; // Para registrar las acciones
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Para transacciones de base de datos
use Illuminate\Support\Facades\Log; // Para logging de advertencias
use Illuminate\Validation\Rule; // Para reglas de validación complejas
use Illuminate\Support\Facades\Auth; // Para obtener el usuario autenticado
use Inertia\Inertia;

class ClienteController extends Controller
{
    /**
     * Muestra una lista de clientes.
     */
    public function index(Request $request)
    {
        $query = Cliente::query()
            ->with(['tipoCliente', 'estadoCliente', 'direccion.localidad.provincia']); // Cargar relaciones necesarias

        // --- Aplicar filtros de búsqueda ---
        if ($request->has('search') && $request->input('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%' . $searchTerm . '%')
                    ->orWhere('apellido', 'like', '%' . $searchTerm . '%')
                    ->orWhere('DNI', 'like', '%' . $searchTerm . '%')
                    ->orWhere('whatsapp', 'like', '%' . $searchTerm . '%')
                    ->orWhere('mail', 'like', '%' . $searchTerm . '%');
            });
        }

        if ($request->has('tipo_cliente_id') && $request->input('tipo_cliente_id')) {
            $query->where('tipoClienteID', $request->input('tipo_cliente_id'));
        }

        if ($request->has('estado_cliente_id') && $request->input('estado_cliente_id')) {
            $query->where('estadoClienteID', $request->input('estado_cliente_id'));
        }

        // --- Nuevo filtro por provincia ---
        if ($request->has('provincia_id') && $request->input('provincia_id')) {
            $provinciaId = $request->input('provincia_id');
            $query->whereHas('direccion.localidad.provincia', function ($q) use ($provinciaId) {
                $q->where('provinciaID', $provinciaId);
            });
        }

        // --- Contadores para estadísticas ---
        $activeClientsCount = Cliente::whereHas('estadoCliente', function ($q) {
            $q->where('nombreEstado', 'Activo');
        })->count();

        $morosoClientsCount = Cliente::whereHas('estadoCliente', function ($q) {
            $q->where('nombreEstado', 'Moroso');
        })->count();

        $suspendidoClientsCount = Cliente::whereHas('estadoCliente', function ($q) {
            $q->where('nombreEstado', 'Suspendido');
        })->count();

        $tiposClienteCounts = TipoCliente::withCount('clientes')->get()->mapWithKeys(function ($tipo) {
            return [strtolower(str_replace(' ', '', $tipo->nombreTipo)) => $tipo->clientes_count];
        });

        // --- Aplicar ordenamiento ---
        $sortColumn = $request->input('sort_column', 'nombre'); // Columna por defecto
        $sortDirection = $request->input('sort_direction', 'asc'); // Dirección por defecto

        // Validar que la columna de ordenamiento sea segura y exista
        $allowedSortColumns = ['nombre', 'apellido', 'DNI', 'mail', 'created_at'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'nombre'; // Volver a un valor seguro si no es válido
        }

        $clientes = $query->orderBy($sortColumn, $sortDirection)->paginate(10);

        // Preparar datos para Vue - mantener los nombres de columnas originales
        $estadosClienteFormateados = EstadoCliente::all(['estadoClienteID', 'nombreEstado'])->values()->all();
        $tiposClienteFormateados = TipoCliente::all(['tipoClienteID', 'nombreTipo'])->values()->all();
        $provinciasFormateadas = Provincia::all(['provinciaID', 'nombre'])->values()->all();

        return Inertia::render('Clientes/index', [
            'clientes' => $clientes,
            'estadosCliente' => $estadosClienteFormateados,
            'tiposCliente' => $tiposClienteFormateados,
            'provincias' => $provinciasFormateadas,
            'filters' => $request->only(['search', 'tipo_cliente_id', 'estado_cliente_id', 'provincia_id', 'sort_column', 'sort_direction']),
            'counts' => [
                'total' => Cliente::count(),
                'activos' => $activeClientsCount,
                'morosos' => $morosoClientsCount,
                'suspendidos' => $suspendidoClientsCount,
                'mayoristas' => $tiposClienteCounts->get('mayorista', 0),
                'minoristas' => $tiposClienteCounts->get('minorista', 0) + $tiposClienteCounts->get('consumidorfinal', 0),
            ],
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo cliente.
     */
    public function create(Request $request)
    {
        $provincias = Provincia::orderBy('nombre')->get();
        $tiposCliente = TipoCliente::where('activo', true)->orderBy('nombreTipo')->get();
        $estadosCliente = EstadoCliente::orderBy('nombreEstado')->get();
        $estadosCuentaCorriente = EstadoCuentaCorriente::orderBy('nombreEstado')->get();

        return Inertia::render('Clientes/Create', [
            'provincias' => $provincias,
            'tiposCliente' => $tiposCliente,
            'estadosCliente' => $estadosCliente,
            'estadosCuentaCorriente' => $estadosCuentaCorriente,
        ]);
    }

    /**
     * Almacena un cliente recién creado en la base de datos.
     * Corresponde a la operación 'confirmarRegistro()' del contrato.
     */
    public function store(Request $request)
    {
        // ... (Tu código store, no necesita cambios aquí) ...
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'DNI' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes')->ignore($request->clienteID, 'clienteID'), // Valida unicidad DNI
            ],
            'mail' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'calle' => 'required|string|max:255',
            'altura' => 'required|string|max:50',
            'pisoDepto' => 'nullable|string|max:50',
            'barrio' => 'nullable|string|max:255',
            'codigoPostal' => 'required|string|max:10',
            'provincia_id' => 'required|exists:provincias,provinciaID',
            'localidad_id' => 'required|exists:localidades,localidadID',
            'tipo_cliente_id' => 'required|exists:tipos_cliente,tipoClienteID',
            'estado_cliente_id' => 'required|exists:estados_cliente,estadoClienteID',
            'limiteCredito' => 'nullable|numeric|min:0',
            'diasGracia' => 'nullable|integer|min:0',
            'estado_cuenta_corriente_id' => 'nullable|exists:estados_cuenta_corriente,estadoCuentaCorrienteID',
        ]);

        $tipoCliente = TipoCliente::find($request->tipo_cliente_id);

        DB::beginTransaction();
        $auditoria = null; 
        try {
            $direccion = Direccion::create([
                'calle' => $request->calle,
                'altura' => $request->altura,
                'pisoDepto' => $request->pisoDepto,
                'barrio' => $request->barrio,
                'codigoPostal' => $request->codigoPostal,
                'localidadID' => $request->localidad_id,
            ]);

            $cuentaCorrienteID = null;
            if ($tipoCliente && $tipoCliente->nombreTipo === 'Mayorista') {
                $estadoCCPorDefecto = EstadoCuentaCorriente::first(); 
                if (!$estadoCCPorDefecto) {
                    throw new \Exception('No se encontró un estado de cuenta corriente por defecto.');
                }

                $cuentaCorriente = CuentaCorriente::create([
                    'saldo' => 0.00,
                    'limiteCredito' => $request->limiteCredito ?? 0.00,
                    'diasGracia' => $request->diasGracia ?? 0,
                    'estadoCuentaCorrienteID' => $request->estado_cuenta_corriente_id ?? $estadoCCPorDefecto->estadoCuentaCorrienteID,
                ]);
                $cuentaCorrienteID = $cuentaCorriente->cuentaCorrienteID;
            }

            $cliente = Cliente::create([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'DNI' => $request->DNI,
                'mail' => $request->mail ?: null,
                'whatsapp' => $request->whatsapp ?: null,
                'telefono' => $request->telefono ?: null,
                'tipoClienteID' => $request->tipo_cliente_id,
                'estadoClienteID' => $request->estado_cliente_id,
                'direccionID' => $direccion->direccionID,
                'cuentaCorrienteID' => $cuentaCorrienteID,
            ]);

            if (Auth::check()) {
                Auditoria::registrar(
                    Auditoria::ACCION_CREAR_CLIENTE,
                    'clientes',
                    $cliente->clienteID,
                    null,
                    $cliente->toArray(),
                    'Registro de nuevo cliente',
                    'Cliente registrado: ' . $cliente->nombre . ' ' . $cliente->apellido,
                    Auth::id()
                );
            } else {
                Auditoria::registrar(
                    Auditoria::ACCION_CREAR_CLIENTE,
                    'clientes',
                    $cliente->clienteID,
                    null,
                    $cliente->toArray(),
                    'Registro de nuevo cliente (sistema)',
                    'Cliente registrado: ' . $cliente->nombre . ' ' . $cliente->apellido,
                    null
                );
                Log::info('Cliente registrado sin usuario autenticado. Cliente ID: ' . $cliente->clienteID);
            }

            DB::commit(); 
            return redirect()->route('clientes.index')->with('success', 'Cliente registrado exitosamente. ID: ' . $cliente->clienteID);

        } catch (\Exception $e) {
            DB::rollBack(); 
            if ($auditoria && $e->getMessage() === 'No se pudo registrar la auditoría: usuario no autenticado.') {
                return back()->with('warning', 'Cliente registrado, pero hubo un problema al registrar la auditoría: ' . $e->getMessage());
            }
            return back()->withInput()->withErrors(['error' => 'Error al registrar el cliente: ' . $e->getMessage()]);
        }
    }

    /**
     * Muestra los detalles de un cliente específico.
     */
    public function show(Request $request, Cliente $cliente)
    {
        $cliente->load(['tipoCliente', 'estadoCliente', 'direccion.localidad.provincia', 'cuentaCorriente.estadoCuentaCorriente']);
        
        // Asegúrate de que el cliente.id sea cliente.clienteID
        $cliente->id = $cliente->clienteID;

        // Formatear las relaciones para que Vue las use fácilmente
        $cliente->tipo_cliente = ['id' => $cliente->tipoClienteID, 'nombre' => $cliente->tipoCliente->nombreTipo ?? 'N/A'];
        $cliente->estado_cliente = ['id' => $cliente->estadoClienteID, 'nombre' => $cliente->estadoCliente->nombreEstado ?? 'N/A'];
        $cliente->provincia_nombre = $cliente->direccion->localidad->provincia->nombre ?? 'N/A';

        $historialAuditoria = Auditoria::historialCliente($cliente->clienteID);

        return Inertia::render('Clientes/Show', [
            'cliente' => $cliente,
            'historialAuditoria' => $historialAuditoria,
        ]);
    }

    /**
     * Muestra el formulario para editar un cliente existente.
     */
    public function edit(Request $request, Cliente $cliente)
    {
        $provincias = Provincia::orderBy('nombre')->get()->map(function ($provincia) {
            return ['id' => $provincia->provinciaID, 'nombre' => $provincia->nombre];
        });
        $tiposCliente = TipoCliente::where('activo', true)->orderBy('nombreTipo')->get()->map(function ($tipo) {
            return ['id' => $tipo->tipoClienteID, 'nombre' => $tipo->nombreTipo];
        });
        $estadosCliente = EstadoCliente::orderBy('nombreEstado')->get()->map(function ($estado) {
            return ['id' => $estado->estadoClienteID, 'nombre' => $estado->nombreEstado];
        });
        $estadosCuentaCorriente = EstadoCuentaCorriente::orderBy('nombreEstado')->get()->map(function ($estado) {
            return ['id' => $estado->estadoCuentaCorrienteID, 'nombre' => $estado->nombreEstado];
        });

        $cliente->load(['tipoCliente', 'estadoCliente', 'direccion.localidad.provincia', 'cuentaCorriente.estadoCuentaCorriente']);
        
        // Ajustar el ID para Vue
        $cliente->id = $cliente->clienteID;

        return Inertia::render('Clientes/Edit', [
            'cliente' => $cliente,
            'provincias' => $provincias,
            'tiposCliente' => $tiposCliente,
            'estadosCliente' => $estadosCliente,
            'estadosCuentaCorriente' => $estadosCuentaCorriente,
        ]);
    }

    /**
     * Actualiza un cliente existente en la base de datos.
     */
    public function update(Request $request, Cliente $cliente)
    {
        // ... (Tu código update, no necesita cambios aquí) ...
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'DNI' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes')->ignore($cliente->clienteID, 'clienteID'),
            ],
            'mail' => 'nullable|email|max:255',
            'whatsapp' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'calle' => 'required|string|max:255',
            'altura' => 'required|string|max:50',
            'pisoDepto' => 'nullable|string|max:50',
            'barrio' => 'nullable|string|max:255',
            'codigoPostal' => 'required|string|max:10',
            'provincia_id' => 'required|exists:provincias,provinciaID',
            'localidad_id' => 'required|exists:localidades,localidadID',
            'tipo_cliente_id' => 'required|exists:tipos_cliente,tipoClienteID',
            'estado_cliente_id' => 'required|exists:estados_cliente,estadoClienteID',
            'limiteCredito' => 'nullable|numeric|min:0',
            'diasGracia' => 'nullable|integer|min:0',
            'estado_cuenta_corriente_id' => 'nullable|exists:estados_cuenta_corriente,estadoCuentaCorrienteID',
        ]);

        $tipoCliente = TipoCliente::find($request->tipo_cliente_id);

        DB::beginTransaction();
        try {
            $datosAnteriores = $cliente->toArray();
            
            $direccion = $cliente->direccion;
            if (!$direccion) {
                $direccion = Direccion::create([
                    'calle' => $request->calle,
                    'altura' => $request->altura,
                    'pisoDepto' => $request->pisoDepto,
                    'barrio' => $request->barrio,
                    'codigoPostal' => $request->codigoPostal,
                    'localidadID' => $request->localidad_id,
                ]);
                $cliente->direccionID = $direccion->direccionID;
            } else {
                $direccion->update([
                    'calle' => $request->calle,
                    'altura' => $request->altura,
                    'pisoDepto' => $request->pisoDepto,
                    'barrio' => $request->barrio,
                    'codigoPostal' => $request->codigoPostal,
                    'localidadID' => $request->localidad_id,
                ]);
            }

            $cuentaCorrienteID = $cliente->cuentaCorrienteID;
            if ($tipoCliente && $tipoCliente->nombreTipo === 'Mayorista') {
                if (!$cliente->cuentaCorriente) {
                    $estadoCCPorDefecto = EstadoCuentaCorriente::first();
                    if (!$estadoCCPorDefecto) {
                        throw new \Exception('No se encontró un estado de cuenta corriente por defecto.');
                    }
                    $cuentaCorriente = CuentaCorriente::create([
                        'saldo' => 0.00,
                        'limiteCredito' => $request->limiteCredito ?? 0.00,
                        'diasGracia' => $request->diasGracia ?? 0,
                        'estadoCuentaCorrienteID' => $request->estado_cuenta_corriente_id ?? $estadoCCPorDefecto->estadoCuentaCorrienteID,
                    ]);
                    $cuentaCorrienteID = $cuentaCorriente->cuentaCorrienteID;
                } else {
                    $cliente->cuentaCorriente->update([
                        'limiteCredito' => $request->limiteCredito ?? 0.00,
                        'diasGracia' => $request->diasGracia ?? 0,
                        'estadoCuentaCorrienteID' => $request->estado_cuenta_corriente_id ?? $cliente->cuentaCorriente->estadoCuentaCorrienteID,
                    ]);
                    $cuentaCorrienteID = $cliente->cuentaCorriente->cuentaCorrienteID;
                }
            } else {
                if ($cliente->cuentaCorriente) {
                    $cliente->cuentaCorriente->delete(); 
                    $cuentaCorrienteID = null;
                }
            }

            $cliente->update([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'DNI' => $request->DNI,
                'mail' => $request->mail ?: null,
                'whatsapp' => $request->whatsapp ?: null,
                'telefono' => $request->telefono ?: null,
                'tipoClienteID' => $request->tipo_cliente_id,
                'estadoClienteID' => $request->estado_cliente_id,
                'direccionID' => $direccion->direccionID,
                'cuentaCorrienteID' => $cuentaCorrienteID,
            ]);

            if (Auth::check()) {
                Auditoria::registrar(
                    Auditoria::ACCION_MODIFICAR_CLIENTE,
                    'clientes',
                    $cliente->clienteID,
                    $datosAnteriores,
                    $cliente->fresh()->toArray(),
                    'Actualización de cliente',
                    'Cliente actualizado: ' . $cliente->nombre . ' ' . $cliente->apellido,
                    Auth::id()
                );
            } else {
                Auditoria::registrar(
                    Auditoria::ACCION_MODIFICAR_CLIENTE,
                    'clientes',
                    $cliente->clienteID,
                    $datosAnteriores,
                    $cliente->fresh()->toArray(),
                    'Actualización de cliente (sistema)',
                    'Cliente actualizado: ' . $cliente->nombre . ' ' . $cliente->apellido,
                    null
                );
                Log::info('Cliente actualizado sin usuario autenticado. Cliente ID: ' . $cliente->clienteID);
            }

            DB::commit();
            return redirect()->route('clientes.index')->with('success', 'Cliente actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al actualizar el cliente: ' . $e->getMessage()]);
        }
    }

    /**
     * Muestra el formulario para dar de baja un cliente.
     */
    public function confirmDelete(Request $request, Cliente $cliente)
    {
        $operacionesPendientes = [];
        $cliente->load(['tipoCliente', 'estadoCliente', 'direccion.localidad.provincia']);
        
        // Ajustar el ID para Vue
        $cliente->id = $cliente->clienteID;

        return Inertia::render('Clientes/ConfirmDelete', [
            'cliente' => $cliente,
            'operacionesPendientes' => $operacionesPendientes,
        ]);
    }

    /**
     * Da de baja un cliente específico con motivo (CU-04).
     */
    public function darDeBaja(Request $request, Cliente $cliente)
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $operacionesPendientes = false; 
            
            if ($operacionesPendientes) {
                return back()->withErrors([
                    'error' => 'No es posible dar de baja al cliente ' . $cliente->nombre . ' ' . $cliente->apellido . 
                                     ' porque tiene operaciones activas pendientes. Por favor, complete o cancele estas operaciones antes de continuar.'
                ]);
            }

            $datosAnteriores = $cliente->toArray();
            
            $estadoInactivo = EstadoCliente::where('nombreEstado', 'Inactivo')->first();
            if (!$estadoInactivo) {
                throw new \Exception('No se encontró el estado "Inactivo" en el sistema.');
            }
            
            $cliente->update([
                'estadoClienteID' => $estadoInactivo->estadoClienteID
            ]);
            
            if ($cliente->cuentaCorriente) {
                $estadoCCInactivo = EstadoCuentaCorriente::where('nombreEstado', 'Inactiva')->first();
                if ($estadoCCInactivo) {
                    $cliente->cuentaCorriente->update([
                        'estadoCuentaCorrienteID' => $estadoCCInactivo->estadoCuentaCorrienteID
                    ]);
                }
            }

            if (Auth::check()) {
                Auditoria::registrar(
                    Auditoria::ACCION_BAJA_CLIENTE,
                    'clientes',
                    $cliente->clienteID,
                    $datosAnteriores,
                    $cliente->fresh()->toArray(),
                    $request->motivo,
                    'Cliente dado de baja: ' . $cliente->nombre . ' ' . $cliente->apellido . '. Motivo: ' . $request->motivo,
                    Auth::id()
                );
            } else {
                Auditoria::registrar(
                    Auditoria::ACCION_BAJA_CLIENTE,
                    'clientes',
                    $cliente->clienteID,
                    $datosAnteriores,
                    $cliente->fresh()->toArray(),
                    $request->motivo,
                    'Cliente dado de baja por el sistema: ' . $cliente->nombre . ' ' . $cliente->apellido . '. Motivo: ' . $request->motivo,
                    null
                );
            }

            DB::commit();
            return redirect()->route('clientes.index')->with('success', 'Cliente dado de baja exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al dar de baja el cliente: ' . $e->getMessage()]);
        }
    }

    /**
     * Elimina un cliente existente de la base de datos (soft delete).
     * NOTA: Este método ahora cambia el estado del cliente a "Inactivo" en lugar de un soft delete físico
     * para alinearse con la funcionalidad de "darDeBaja". Si necesitas un soft delete real, renombra o
     * crea un nuevo método.
     */
    public function destroy(Cliente $cliente)
    {
        DB::beginTransaction();
        try {
            // Guardar datos del cliente antes de cambiar su estado para auditoría
            $datosAnteriores = $cliente->toArray();
            
            // Obtener el estado 'Inactivo'
            $estadoInactivo = EstadoCliente::where('nombreEstado', 'Inactivo')->first();
            if (!$estadoInactivo) {
                throw new \Exception('No se encontró el estado "Inactivo" en el sistema. Asegúrese de que existe.');
            }

            // Actualizar el estado del cliente a Inactivo
            $cliente->update([
                'estadoClienteID' => $estadoInactivo->estadoClienteID
            ]);

            // Si tiene cuenta corriente, también cambiar su estado a Inactiva
            if ($cliente->cuentaCorriente) {
                $estadoCCInactivo = EstadoCuentaCorriente::where('nombreEstado', 'Inactiva')->first();
                if ($estadoCCInactivo) {
                    $cliente->cuentaCorriente->update([
                        'estadoCuentaCorrienteID' => $estadoCCInactivo->estadoCuentaCorrienteID
                    ]);
                } else {
                    Log::warning("No se encontró el estado 'Inactiva' para cuentas corrientes. ClienteID: {$cliente->clienteID}");
                }
            }

            // Registrar Auditoría como "Dar de baja" (que es lo que realmente hace)
            if (Auth::check()) {
                Auditoria::registrar(
                    Auditoria::ACCION_BAJA_CLIENTE, // Usamos BAJA_CLIENTE ya que es un cambio de estado a inactivo
                    'clientes',
                    $cliente->clienteID,
                    $datosAnteriores,
                    $cliente->fresh()->toArray(), // Obtener el cliente recién actualizado
                    'Cliente puesto en estado Inactivo (equivalente a soft delete)',
                    'Cliente en estado inactivo: ' . $cliente->nombre . ' ' . $cliente->apellido,
                    Auth::id()
                );
            } else {
                Auditoria::registrar(
                    Auditoria::ACCION_BAJA_CLIENTE,
                    'clientes',
                    $cliente->clienteID,
                    $datosAnteriores,
                    $cliente->fresh()->toArray(),
                    'Cliente puesto en estado Inactivo (sistema)',
                    'Cliente en estado inactivo: ' . $cliente->nombre . ' ' . $cliente->apellido,
                    null
                );
                Log::info('Cliente puesto en estado inactivo sin usuario autenticado. Cliente ID: ' . $cliente->clienteID);
            }

            DB::commit();
            return redirect()->route('clientes.index')->with('success', 'Cliente puesto en estado inactivo exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al cambiar el estado del cliente a inactivo: ' . $e->getMessage(), ['clienteID' => $cliente->clienteID]);
            return redirect()->back()->withErrors(['error' => 'Error al dar de baja el cliente: ' . $e->getMessage()]);
        }
    }

    /**
     * Alternar el estado activo de un cliente (el booleano 'activo').
     * Esto es diferente a cambiar su estado a 'Activo'/'Inactivo' a través de estadoClienteID.
     * Si 'activo' se refiere al campo booleano, mantenemos este método.
     * Si no existe un campo 'activo' booleano, este método debería eliminarse o modificarse para usar estadoClienteID.
     */
    public function toggleActivo(Cliente $cliente, Request $request)
    {
        // NOTA: Si 'activo' se refiere a la columna `estadoClienteID`
        // y quieres cambiar entre 'Activo' e 'Inactivo' de `estados_cliente`,
        // este método necesita ser modificado.
        // Asumiendo que existe una columna booleana 'activo' en la tabla 'clientes'.
        // Si no existe, este método no tiene sentido tal cual.

        // Supongamos que tu tabla 'clientes' tiene una columna `activo` (boolean)
        // Y que un cliente con `estadoClienteID` que apunta a "Inactivo" no puede estar 'activo' = true.
        // O que el `estadoClienteID` DEBE ser el que indica si está activo o no.

        // Si `activo` es un campo booleano que determina si el cliente está "activado" dentro de un estado,
        // (por ejemplo, un cliente moroso puede estar "activo" para recibir notificaciones)
        // entonces este método es correcto.

        // Si `activo` en la request significa cambiar el estadoClienteID a 'Activo' o 'Inactivo'
        // ENTONCES ESTE MÉTODO ES INCORRECTO Y DEBE CAMBIARSE A:
        /*
        try {
            DB::beginTransaction();
            $estadoDeseado = $request->boolean('activo')
                ? EstadoCliente::where('nombreEstado', 'Activo')->firstOrFail()
                : EstadoCliente::where('nombreEstado', 'Inactivo')->firstOrFail();

            $datosAnteriores = $cliente->toArray();

            $cliente->update(['estadoClienteID' => $estadoDeseado->estadoClienteID]);

            if (Auth::check()) {
                Auditoria::registrar(
                    Auditoria::ACCION_MODIFICAR_CLIENTE,
                    'clientes',
                    $cliente->clienteID,
                    $datosAnteriores,
                    $cliente->fresh()->toArray(),
                    'Cambio de estado a ' . $estadoDeseado->nombreEstado,
                    'Cliente ' . $cliente->nombre . ' ' . $cliente->apellido . ' puesto en estado: ' . $estadoDeseado->nombreEstado,
                    Auth::id()
                );
            } else {
                Log::info('Estado del cliente cambiado sin usuario autenticado. Cliente ID: ' . $cliente->clienteID . ', Nuevo estado: ' . $estadoDeseado->nombreEstado);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Estado del cliente actualizado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar el estado del cliente: ' . $e->getMessage(), ['clienteID' => $cliente->clienteID]);
            return redirect()->back()->withErrors(['error' => 'Error al actualizar el estado del cliente: ' . $e->getMessage()]);
        }
        */
        // Si tienes una columna `activo` (booleana) y quieres mantenerla:
        try {
            $nuevoEstado = $request->boolean('activo');
            
            DB::beginTransaction();

            $datosAnteriores = $cliente->toArray(); // Para auditoría

            $cliente->update(['activo' => $nuevoEstado]);

            if (Auth::check()) {
                Auditoria::registrar(
                    Auditoria::ACCION_MODIFICAR_CLIENTE,
                    'clientes',
                    $cliente->clienteID,
                    $datosAnteriores,
                    $cliente->fresh()->toArray(),
                    'Estado "activo" booleano cambiado',
                    'Cliente ' . $cliente->nombre . ' ' . $cliente->apellido . ' : ' . ($nuevoEstado ? 'Activado' : 'Desactivado'),
                    Auth::id()
                );
            } else {
                Log::info('Estado activo booleano cambiado sin usuario autenticado. Cliente ID: ' . $cliente->clienteID . ', Nuevo estado: ' . ($nuevoEstado ? 'activo' : 'inactivo'));
            }

            DB::commit();
            
            return redirect()->back()->with('success', 'Estado de "activo" del cliente actualizado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar el estado "activo" del cliente: ' . $e->getMessage(), ['clienteID' => $cliente->clienteID]);
            return redirect()->back()->withErrors(['error' => 'Error al actualizar el estado de "activo" del cliente: ' . $e->getMessage()]);
        }
    }
}