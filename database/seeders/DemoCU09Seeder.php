<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;
use App\Models\TipoCliente;
use App\Models\MovimientoCuentaCorriente;
use App\Models\Direccion; 
use App\Models\Localidad; 
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DemoCU09Seeder extends Seeder
{
    public function run(): void
    {
        // 1. Buscamos los datos base que YA existen en tu BD
        $tipoMayorista = TipoCliente::where('nombreTipo', 'Mayorista')->first();
        $estadoActiva = EstadoCuentaCorriente::where('nombreEstado', 'Activa')->first();
        $localidad = Localidad::first(); // Agarramos la primera localidad que haya

        // Validamos que existan para no fallar
        if (!$tipoMayorista || !$estadoActiva || !$localidad) {
            $this->command->error('❌ Error: No se encontraron los datos base (Tipos de Cliente, Estados o Localidades).');
            $this->command->info('Asegúrate de haber corrido los seeders base previamente.');
            return;
        }

        $this->command->info('--- Generando Escenario de Prueba CU-09 (Sin borrar nada) ---');

        DB::transaction(function () use ($tipoMayorista, $estadoActiva, $localidad) {
            
            // 2. Crear una Dirección nueva para este cliente
            $direccion = Direccion::create([
                'calle' => 'Calle Deuda',
                'altura' => '999',
                'localidadID' => $localidad->localidadID,
                'codigoPostal' => '1000'
            ]);

            // 3. Crear CC con Saldo Alto
            $cc = CuentaCorriente::create([
                'saldo' => 500000, 
                'limiteCredito' => 400000, 
                'diasGracia' => 7,
                'estadoCuentaCorrienteID' => $estadoActiva->estadoCuentaCorrienteID,
            ]);

            // 4. Crear Cliente
            // Usamos un DNI/CUIT random o fijo para evitar duplicados si corres esto varias veces
            $dni = '30' . rand(10000000, 99999999) . '9';
            
            $cliente = Cliente::create([
                'nombre' => 'Distribuidora',
                'apellido' => 'El Vencido S.A.',
                'DNI' => $dni,
                'telefono' => '111222333',
                'tipoClienteID' => $tipoMayorista->tipoClienteID,
                'cuentaCorrienteID' => $cc->cuentaCorrienteID,
                'estadoClienteID' => 1, // Asumimos ID 1 = Activo
                'direccionID' => $direccion->direccionID,
            ]);

            // 5. Generar Movimiento Viejo (Vencido)
            MovimientoCuentaCorriente::create([
                'cuentaCorrienteID' => $cc->cuentaCorrienteID,
                'tipoMovimiento' => 'Debito',
                'monto' => 500000,
                'descripcion' => 'Venta Antigua Impaga (Simulada)',
                'fechaEmision' => Carbon::now()->subDays(40), 
                'fechaVencimiento' => Carbon::now()->subDays(10), 
                'saldoAlMomento' => 500000,
                'referenciaTabla' => 'ventas', 
                'referenciaID' => 999, 
            ]);

            $this->command->info("✅ Cliente creado: {$cliente->nombre} {$cliente->apellido} (DNI: $dni)");
        });
    }
}