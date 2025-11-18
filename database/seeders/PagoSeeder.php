<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pago;
use App\Models\Cliente;
use App\Models\User;
use Carbon\Carbon;

class PagoSeeder extends Seeder
{
    /**
     * Genera pagos de ejemplo para clientes con cuenta corriente.
     * 
     * Este seeder:
     * - Crea 20 pagos de ejemplo
     * - Solo para clientes que tienen cuenta corriente activa
     * - Usa montos aleatorios entre $500 y $50,000
     * - Distribuye entre los 4 métodos de pago disponibles
     * - Anula el último pago para testing
     * - Genera fechas retroactivas hasta 90 días atrás
     */
    public function run(): void
    {
        // Obtener solo clientes con cuenta corriente
        $clientes = Cliente::whereHas('cuentaCorriente')->take(5)->get();
        $usuario = User::first();

        if ($clientes->isEmpty() || !$usuario) {
            $this->command->warn('⚠️  No hay clientes con cuenta corriente o usuarios. Ejecuta sus seeders primero.');
            return;
        }

        $metodoPagoOpciones = ['efectivo', 'transferencia', 'tarjeta', 'cheque'];
        $pagosCreados = 0;

        // Crear 20 pagos de ejemplo
        for ($i = 0; $i < 20; $i++) {
            $cliente = $clientes->random();
            $fechaPago = Carbon::now()->subDays(rand(0, 90));
            $monto = rand(5, 500) * 100; // Entre $500 y $50,000
            $metodoPago = $metodoPagoOpciones[array_rand($metodoPagoOpciones)];

            // Crear el pago
            $pago = Pago::create([
                'clienteID' => $cliente->clienteID,
                'user_id' => $usuario->id,
                'monto' => $monto,
                'metodo_pago' => $metodoPago,
                'fecha_pago' => $fechaPago,
                'observaciones' => $i % 4 === 0 
                    ? 'Pago de ejemplo generado por seeder - ' . ($metodoPago === 'cheque' ? 'Cheque N° ' . rand(1000000, 9999999) : 'Sin observaciones adicionales')
                    : null,
                'anulado' => $i === 19 ? true : false, // El último está anulado
                // numero_recibo se genera automáticamente en el boot del modelo
            ]);

            $pagosCreados++;
        }

        $this->command->info("✅ Se crearon {$pagosCreados} pagos de ejemplo.");
        $this->command->info("   - {$clientes->count()} clientes con CC utilizados");
        $this->command->info("   - 1 pago anulado para testing");
        $this->command->info("   - Fechas distribuidas en los últimos 90 días");
    }
}
