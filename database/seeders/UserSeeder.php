<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener rol_id desde tabla roles
        $rolAdmin = DB::table('roles')->where('nombre', 'admin')->value('rol_id');
        $rolVendedor = DB::table('roles')->where('nombre', 'vendedor')->value('rol_id');
        $rolTecnico = DB::table('roles')->where('nombre', 'tecnico')->value('rol_id');

        // Admin
        User::updateOrCreate(
            ['email' => 'admin@tecnosoluciones.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('1234'),
                'rol_id' => $rolAdmin,
            ]
        );

        // Vendedor
        User::updateOrCreate(
            ['email' => 'vendedor@tecnosoluciones.com'],
            [
                'name' => 'Vendedor',
                'password' => Hash::make('1234'),
                'rol_id' => $rolVendedor,
            ]
        );

        // Técnico
        User::updateOrCreate(
            ['email' => 'tecnico@tecnosoluciones.com'],
            [
                'name' => 'Técnico',
                'password' => Hash::make('1234'),
                'rol_id' => $rolTecnico,
            ]
        );
    }
}
