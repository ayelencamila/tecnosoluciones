<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepositoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('depositos')->insert([
            'deposito_id' => 1,
            'nombre' => 'Depósito Principal',
            'descripcion' => 'Depósito central de la sede principal',
            'direccion' => 'Sede principal',
            'activo' => true,
            'esPrincipal' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "✅ Depósito Principal creado exitosamente.\n";
    }
}
