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

class UpdateClienteService
{
    public function handle(Cliente $cliente, array $validatedData): Cliente
    {
        return DB::transaction(function () use ($cliente, $validatedData) {
            
            // 1. Actualizar Dirección
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

            // 2. Lógica de Cuenta Corriente (CU-02)
            $tipoCliente = TipoCliente::find($validatedData['tipo_cliente_id']);
            $cuentaCorrienteID = $cliente->cuentaCorrienteID;

            // Verificamos si es Mayorista (usando el método experto del modelo)
            if ($tipoCliente && $tipoCliente->esMayorista()) {
                
                if ($cliente->cuentaCorriente) {
                    // Actualizar existente
                    $cliente->cuentaCorriente->update([
                        'limiteCredito' => $validatedData['limiteCredito'] ?? $cliente->cuentaCorriente->limiteCredito,
                        'diasGracia' => $validatedData['diasGracia'] ?? $cliente->cuentaCorriente->diasGracia,
                    ]);
                } else {
                    // Crear nueva (SOLUCIÓN BLINDADA: Buscamos el ID o usamos 1 por defecto)
                    $idEstadoActivo = EstadoCuentaCorriente::where('nombreEstado', 'Activa')->value('estadoCuentaCorrienteID') ?? 1;
                    
                    // Valores por defecto (puedes usar Configuracion si existe, sino 0)
                    $limiteDefault = 0; 
                    $diasGraciaDefault = 0;

                    $cuenta = CuentaCorriente::create([
                        'saldo' => 0,
                        'limiteCredito' => $validatedData['limiteCredito'] ?? $limiteDefault,
                        'diasGracia' => $validatedData['diasGracia'] ?? $diasGraciaDefault,
                        'estadoCuentaCorrienteID' => $idEstadoActivo,
                    ]);
                    $cuentaCorrienteID = $cuenta->cuentaCorrienteID;
                }
            } else {
                // Es Minorista: Desvincular y borrar cuenta (SoftDelete)
                if ($cliente->cuentaCorriente) {
                    $cliente->cuentaCorriente->delete();
                    $cuentaCorrienteID = null;
                }
            }

            // 3. Actualizar Cliente
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

            return $cliente->fresh();
        });
    }
}