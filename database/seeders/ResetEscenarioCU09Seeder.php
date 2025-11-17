<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;

class ResetEscenarioCU09Seeder extends Seeder
{
    /**
     * Resetea el estado de las cuentas corrientes a "Activa" 
     * para poder volver a ejecutar la demo del CU-09
     */
    public function run(): void
    {
        $this->command->info('🔄 Reseteando escenario CU-09...');
        
        $estadoActiva = EstadoCuentaCorriente::where('nombreEstado', 'Activa')->first();
        
        if (!$estadoActiva) {
            $this->command->error('❌ No se encontró el estado "Activa"');
            return;
        }

        // Buscar los clientes del escenario de prueba
        $clientesPrueba = Cliente::whereIn('DNI', ['40111111', '40222222', '40333333'])->get();
        
        $this->command->newLine();
        
        $reseteadas = 0;
        $yaActivas = 0;
        
        foreach ($clientesPrueba as $cliente) {
            if ($cliente->cuentaCorriente) {
                $estadoAnterior = $cliente->cuentaCorriente->estadoCuentaCorriente->nombreEstado;
                
                if ($estadoAnterior !== 'Activa') {
                    $cliente->cuentaCorriente->update([
                        'estadoCuentaCorrienteID' => $estadoActiva->estadoCuentaCorrienteID
                    ]);
                    
                    $this->command->warn("   🔓 {$cliente->apellido}, {$cliente->nombre} → Desbloqueado (era: {$estadoAnterior})");
                    $reseteadas++;
                } else {
                    $this->command->line("   ✓ {$cliente->apellido}, {$cliente->nombre} → Ya estaba Activa");
                    $yaActivas++;
                }
            }
        }
        
        $this->command->newLine();
        $this->command->info("📊 Resumen:");
        $this->command->line("   • Cuentas desbloqueadas: {$reseteadas}");
        $this->command->line("   • Cuentas ya activas: {$yaActivas}");
        $this->command->line("   • Total procesadas: " . ($reseteadas + $yaActivas));
        $this->command->newLine();
        $this->command->warn('🚀 Listo para demo! Ejecuta: sail artisan cuentas:check-vencidas');
    }
}
