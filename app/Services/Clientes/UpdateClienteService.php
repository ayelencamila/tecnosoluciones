<?php

namespace App\Services\Clientes;

use App\Models\Cliente;
use App\Models\Direccion;
use App\Models\CuentaCorriente;
use App\Models\TipoCliente;
use App\Models\EstadoCuentaCorriente;
use App\Models\Configuracion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Clase de Servicio para manejar la lógica de actualización de Clientes.
 * Cumple con el Caso de Uso CU-02.
 */
class UpdateClienteService
{
    /**
     * Orquesta la actualización de un cliente y sus dependencias.
     *
     * @param Cliente $cliente El cliente a actualizar.
     * @param array $validatedData Los datos validados del UpdateClienteRequest.
     * @return Cliente El cliente actualizado.
     */
    public function handle(Cliente $cliente, array $validatedData): Cliente
    {
        return DB::transaction(function () use ($cliente, $validatedData) {
            
            // 1. Actualizar/Crear Dirección
            $direccion = $cliente->direccion ?? new Direccion();
            $direccion->fill([
                'calle' => $validatedData['calle'],
                'altura' => $validatedData['altura'],
                'pisoDepto' => $validatedData['pisoDepto'] ?? null,
                'barrio' => $validatedData['barrio'] ?? null,
                'codigoPostal' => $validatedData['codigoPostal'],
                'localidadID' => $validatedData['localidad_id'],
            ]);
            $direccion->save();

            // 2. Lógica de Negocio (CU-02 Postcondición: Habilitar/Deshabilitar CC)
            $tipoCliente = TipoCliente::find($validatedData['tipo_cliente_id']);
            $cuentaCorrienteID = $cliente->cuentaCorrienteID;

            if ($tipoCliente && $tipoCliente->esMayorista()) {
                // Es Mayorista, debe tener CC
                if ($cliente->cuentaCorriente) {
                    // Ya tiene CC, la actualizamos
                    $cliente->cuentaCorriente->update([
                        'limiteCredito' => $validatedData['limiteCredito'] ?? $cliente->cuentaCorriente->limiteCredito,
                        'diasGracia' => $validatedData['diasGracia'] ?? $cliente->cuentaCorriente->diasGracia,
                        'estadoCuentaCorrienteID' => $validatedData['estado_cuenta_corriente_id'] ?? $cliente->cuentaCorriente->estadoCuentaCorrienteID,
                    ]);
                } else {
                    // No tenía CC, la creamos usando el método helper
                    $estadoCCDefault = EstadoCuentaCorriente::activa();
                    $limiteDefault = Configuracion::get('limite_credito_global', 0);
                    $diasGraciaDefault = Configuracion::getInt('dias_gracia_global', 0);

                    $cuentaCorriente = CuentaCorriente::create([
                        'saldo' => 0.00,
                        'limiteCredito' => $validatedData['limiteCredito'] ?? $limiteDefault,
                        'diasGracia' => $validatedData['diasGracia'] ?? $diasGraciaDefault,
                        'estadoCuentaCorrienteID' => $validatedData['estado_cuenta_corriente_id'] ?? $estadoCCDefault->estadoCuentaCorrienteID,
                    ]);
                    $cuentaCorrienteID = $cuentaCorriente->cuentaCorrienteID;
                }
            } else {
                // Es Minorista, NO debe tener CC
                if ($cliente->cuentaCorriente) {
                    // Tenía CC, la borramos (Soft Delete)
                    $cliente->cuentaCorriente->delete();
                    $cuentaCorrienteID = null;
                }
            }

            // 3. Actualizar el Cliente
            $cliente->update([
                'nombre' => $validatedData['nombre'],
                'apellido' => $validatedData['apellido'],
                'DNI' => $validatedData['DNI'],
                'mail' => $validatedData['mail'] ?? null,
                'whatsapp' => $validatedData['whatsapp'] ?? null,
                'telefono' => $validatedData['telefono'] ?? null,
                'tipoClienteID' => $validatedData['tipo_cliente_id'],
                'estadoClienteID' => $validatedData['estado_cliente_id'],
                'direccionID' => $direccion->direccionID,
                'cuentaCorrienteID' => $cuentaCorrienteID,
            ]);

            // 4. Auditoría (CU-02 Paso 9)
            // Tu modelo Cliente ya lo hace automáticamente en el evento 'boot()' (static::updated).
            
            Log::info("Cliente actualizado por Servicio. ID: {$cliente->clienteID}");
            
            return $cliente->fresh(); // Devolvemos el modelo actualizado
        });
    }
}