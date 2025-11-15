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
 * Clase de Servicio para manejar la lógica de creación de Clientes.
 * Cumple con el Caso de Uso CU-01.
 */
class RegistrarClienteService
{
    /**
     * Orquesta la creación de un nuevo cliente y sus dependencias (Direccion, CuentaCorriente).
     *
     * @param array $validatedData Los datos validados del StoreClienteRequest.
     * @return Cliente El cliente recién creado.
     */
    public function handle(array $validatedData): Cliente
    {
        return DB::transaction(function () use ($validatedData) {
            
            // 1. Crear la Entidad dependiente: Direccion
            $direccion = Direccion::create([
                'calle' => $validatedData['calle'],
                'altura' => $validatedData['altura'],
                'pisoDepto' => $validatedData['pisoDepto'] ?? null,
                'barrio' => $validatedData['barrio'] ?? null,
                'codigoPostal' => $validatedData['codigoPostal'],
                'localidadID' => $validatedData['localidad_id'],
            ]);

            // 2. Lógica de Negocio (CU-01 Paso 6 y 10: "Si es Mayorista...")
            $tipoCliente = TipoCliente::find($validatedData['tipo_cliente_id']);
            $cuentaCorrienteID = null;

            if ($tipoCliente && $tipoCliente->esMayorista()) {
                
                // Usar el método helper para obtener el estado 'Activa'
                $estadoCCDefault = EstadoCuentaCorriente::activa();
                
                // Usamos los parámetros globales que ya definimos (CU-31)
                $limiteDefault = Configuracion::get('limite_credito_global', 0);
                $diasGraciaDefault = Configuracion::getInt('dias_gracia_global', 0);

                // 3. Crear la Entidad dependiente: CuentaCorriente
                $cuentaCorriente = CuentaCorriente::create([
                    'saldo' => 0.00,
                    'limiteCredito' => $validatedData['limiteCredito'] ?? $limiteDefault,
                    'diasGracia' => $validatedData['diasGracia'] ?? $diasGraciaDefault,
                    'estadoCuentaCorrienteID' => $validatedData['estado_cuenta_corriente_id'] ?? $estadoCCDefault->estadoCuentaCorrienteID,
                ]);
                $cuentaCorrienteID = $cuentaCorriente->cuentaCorrienteID;
            }

            // 4. Crear la Entidad principal: Cliente
            $cliente = Cliente::create([
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

            Log::info("Nuevo cliente registrado por Servicio. ID: {$cliente->clienteID}");

            return $cliente;
        });
    }
}