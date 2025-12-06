<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;
use App\Models\TipoCliente;
use App\Models\EstadoCliente;
use App\Models\MovimientoCuentaCorriente;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DemoCU09Seeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔧 Generando Escenario de Prueba CU-09...');

        // 1. Validar datos base existentes
        $tipoMayorista = TipoCliente::where('nombreTipo', 'Mayorista')->first();
        $estadoClienteActivo = EstadoCliente::where('nombreEstado', 'Activo')->first();
        $estadoCCActiva = EstadoCuentaCorriente::where('nombreEstado', 'Activa')->first();

        if (!$tipoMayorista || !$estadoClienteActivo || !$estadoCCActiva) {
            $this->command->error('❌ Error: Faltan datos base (TipoCliente, EstadoCliente o EstadoCuentaCorriente).');
            $this->command->info('Ejecuta primero los seeders base del sistema.');
            return;
        }

        DB::transaction(function () use ($tipoMayorista, $estadoClienteActivo, $estadoCCActiva) {
            
            // 2. Verificar si ya existe el cliente de prueba
            $dniPrueba = '99999999';
            $clienteExistente = Cliente::where('dni', $dniPrueba)->first();
            
            if ($clienteExistente) {
                $this->command->warn("⚠️  Cliente con DNI {$dniPrueba} ya existe. Actualizando datos...");
                
                // Resetear estado del cliente y actualizar WhatsApp
                $clienteExistente->estadoClienteID = $estadoClienteActivo->estadoClienteID;
                $clienteExistente->whatsapp = '+5493754532041';
                $clienteExistente->telefono = '3754532041';
                $clienteExistente->save();
                
                // Limpiar y recrear movimientos CC
                if ($clienteExistente->cuentaCorriente) {
                    MovimientoCuentaCorriente::where('cuentaCorrienteID', $clienteExistente->cuentaCorriente->cuentaCorrienteID)->delete();
                    $cc = $clienteExistente->cuentaCorriente;
                    $cc->saldo = 0;
                    $cc->estadoCuentaCorrienteID = $estadoCCActiva->estadoCuentaCorrienteID;
                    $cc->save();
                } else {
                    $cc = CuentaCorriente::create([
                        'saldo' => 0,
                        'limiteCredito' => 50000,
                        'diasGracia' => 30,
                        'estadoCuentaCorrienteID' => $estadoCCActiva->estadoCuentaCorrienteID,
                    ]);
                    $clienteExistente->cuentaCorrienteID = $cc->cuentaCorrienteID;
                    $clienteExistente->save();
                }
                
                $cliente = $clienteExistente;
            } else {
                // 3. Crear Cuenta Corriente primero
                $cc = CuentaCorriente::create([
                    'saldo' => 0, // Se actualizará con el movimiento
                    'limiteCredito' => 50000,
                    'diasGracia' => 30, // 30 días de gracia
                    'estadoCuentaCorrienteID' => $estadoCCActiva->estadoCuentaCorrienteID,
                ]);

                // 4. Crear Cliente Mayorista
                $cliente = Cliente::create([
                    'nombre' => 'Juan',
                    'apellido' => 'Pérez Moroso',
                    'DNI' => $dniPrueba,
                    'telefono' => '3754532041',
                    'mail' => 'juan.moroso@test.com',
                    'whatsapp' => '+5493754532041',
                    'tipoClienteID' => $tipoMayorista->tipoClienteID,
                    'estadoClienteID' => $estadoClienteActivo->estadoClienteID,
                    'cuentaCorrienteID' => $cc->cuentaCorrienteID,
                    'direccionID' => null,
                ]);
            }

            // 5. Crear movimiento CC con deuda VENCIDA (hace 35 días)
            $montoDeuda = 25000;
            $fechaVencimiento = Carbon::now()->subDays(35); // Vencido hace 35 días
            $fechaEmision = Carbon::now()->subDays(60); // Compra hace 60 días

            MovimientoCuentaCorriente::create([
                'cuentaCorrienteID' => $cc->cuentaCorrienteID,
                'tipoMovimiento' => 'Debito',
                'monto' => $montoDeuda,
                'saldoAlMomento' => $montoDeuda,
                'descripcion' => 'Venta vencida - Demo CU-09',
                'fechaEmision' => $fechaEmision,
                'fechaVencimiento' => $fechaVencimiento,
                'referenciaTabla' => 'ventas',
                'referenciaID' => 999, // Simulado
                'created_at' => $fechaEmision,
                'updated_at' => $fechaEmision,
            ]);

            // 6. Actualizar saldo de CC
            $cc->saldo = $montoDeuda;
            $cc->save();

            // 7. Calcular saldo vencido para verificar
            $saldoVencido = $cc->calcularSaldoVencido();

            $this->command->info("✅ Cliente creado exitosamente:");
            $this->command->line("   Nombre: {$cliente->nombre} {$cliente->apellido}");
            $this->command->line("   DNI: {$cliente->dni}");
            $this->command->line("   Tipo: Mayorista");
            $this->command->line("   Estado: {$estadoClienteActivo->nombre}");
            $this->command->line("   Saldo CC: \${$cc->saldo}");
            $this->command->line("   Saldo Vencido: \${$saldoVencido}");
            $this->command->line("   Vencimiento: {$fechaVencimiento->format('d/m/Y')} (hace 35 días)");
            $this->command->info('');
            $this->command->info('📋 Para demostrar el CU-09, ejecuta:');
            $this->command->line('   ./vendor/bin/sail artisan cc:check-vencimientos');
        });
    }
}