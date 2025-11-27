<?php

namespace Database\Seeders;

use App\Models\MedioPago;
use Illuminate\Database\Seeder;

class MedioPagoSeeder extends Seeder
{
    public function run(): void
    {
        $medios = [
            ['nombre' => 'Efectivo', 'recargo_porcentaje' => 0, 'instrucciones' => 'Contar el dinero frente al cliente.'],
            ['nombre' => 'Transferencia Bancaria', 'recargo_porcentaje' => 0, 'instrucciones' => 'Verificar acreditación en cuenta.'],
            ['nombre' => 'Tarjeta de Débito', 'recargo_porcentaje' => 0, 'instrucciones' => 'Pasar por posnet.'],
            ['nombre' => 'Tarjeta de Crédito (1 pago)', 'recargo_porcentaje' => 10, 'instrucciones' => 'Aplicar recargo del 10%.'],
            ['nombre' => 'Cuenta Corriente', 'recargo_porcentaje' => 0, 'instrucciones' => 'Solo clientes autorizados.'],
        ];

        foreach ($medios as $m) {
            MedioPago::firstOrCreate(['nombre' => $m['nombre']], $m);
        }
    }
}
