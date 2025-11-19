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
     * Renombrado a 'handle' para consistencia con el Controlador.
     */
    public function handle(array $data): Cliente
    {
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

            // 2. Lógica de Cuenta Corriente (CU-01)
            if ($tipoCliente && $tipoCliente->esMayorista()) {
                
                // Buscamos el ID del estado 'Activa' de forma segura
                $estadoCuentaActivaID = EstadoCuentaCorriente::where('nombreEstado', 'Activa')
                    ->value('estadoCuentaCorrienteID') ?? 1;

                $cuenta = CuentaCorriente::create([
                    'saldo' => 0,
                    'limiteCredito' => $data['limiteCredito'] ?? 0,
                    'diasGracia' => $data['diasGracia'] ?? 0,
                    'estadoCuentaCorrienteID' => $estadoCuentaActivaID,
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