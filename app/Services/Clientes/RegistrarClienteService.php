<?php

namespace App\Services\Clientes;

use App\Models\Cliente;
use App\Models\Direccion;
use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;
use App\Models\TipoCliente;
use Illuminate\Support\Facades\DB;
use Exception;

class RegistrarClienteService
{
    /**
     * Ejecuta la lógica del CU-01: Registrar Cliente.
     */
    public function execute(array $data): Cliente
    {
        // Usamos una transacción atómica para garantizar la integridad (ACID).
        return DB::transaction(function () use ($data) {
            
            // 1. Registrar la Dirección
            $direccion = Direccion::create([
                'calle' => $data['calle'],
                'altura' => $data['altura'],
                'pisoDepto' => $data['pisoDepto'] ?? null,
                'barrio' => $data['barrio'] ?? null,
                'codigoPostal' => $data['codigoPostal'],
                'localidadID' => $data['localidad_id'],
            ]);

            $cuentaCorrienteId = null;
            $tipoCliente = TipoCliente::find($data['tipo_cliente_id']);

            // 2. Lógica Condicional: Cuenta Corriente (CU-01 Pasos 6 y 7)
            // Delegamos la pregunta al modelo TipoCliente (GRASP Experto).
            if ($tipoCliente && $tipoCliente->esMayorista()) { // <--- Uso del EXPERTO
                
                $estadoCuentaActiva = EstadoCuentaCorriente::where('nombreEstado', 'Activa')->first()->estadoCuentaCorrienteID ?? 1;

                $cuenta = CuentaCorriente::create([
                    'saldo' => 0,
                    'limiteCredito' => $data['limiteCredito'] ?? 0,
                    'diasGracia' => $data['diasGracia'] ?? 0,
                    'estadoCuentaCorrienteID' => $estadoCuentaActiva,
                ]);
                
                $cuentaCorrienteId = $cuenta->cuentaCorrienteID;
            }

            // 3. Registrar el Cliente
            $cliente = Cliente::create([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'DNI' => $data['DNI'],
                'mail' => $data['mail'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                
                'tipoClienteID' => $data['tipo_cliente_id'],
                'estadoClienteID' => $data['estado_cliente_id'],
                'direccionID' => $direccion->direccionID,
                'cuentaCorrienteID' => $cuentaCorrienteId,
            ]);

            return $cliente;
        });
    }
}