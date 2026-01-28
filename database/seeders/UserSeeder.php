<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Recuperamos los IDs de roles (asumiendo que la tabla roles ya tiene datos)
        $rolAdmin = DB::table('roles')->where('nombre', 'admin')->value('rol_id');
        $rolVendedor = DB::table('roles')->where('nombre', 'vendedor')->value('rol_id');
        $rolTecnico = DB::table('roles')->where('nombre', 'tecnico')->value('rol_id');

        // Validamos que existan los roles antes de seguir para evitar errores
        if (!$rolAdmin || !$rolVendedor || !$rolTecnico) {
            $this->command->error('¡Error! Los roles no existen en la base de datos. Ejecuta primero RoleSeeder.');
            return;
        }

        // 2. Usuarios  (Activos)
        
        // Admin
        User::updateOrCreate(
            ['email' => 'admin@tecnosoluciones.com'],
            [
                'name' => 'Admin Sistema',
                'password' => Hash::make('1234'),
                'rol_id' => $rolAdmin,
                'activo' => true, // Explicito
                'bloqueado_hasta' => null,
            ]
        );

        // Vendedor
        User::updateOrCreate(
            ['email' => 'vendedor@tecnosoluciones.com'],
            [
                'name' => 'Vendedor Mostrador',
                'password' => Hash::make('1234'),
                'rol_id' => $rolVendedor,
                'activo' => true,
                'bloqueado_hasta' => null,
            ]
        );

        // Técnico
        User::updateOrCreate(
            ['email' => 'tecnico@tecnosoluciones.com'],
            [
                'name' => 'Técnico Taller',
                'password' => Hash::make('1234'),
                'rol_id' => $rolTecnico,
                'activo' => true,
                'bloqueado_hasta' => null,
            ]
        );

        // 3. Usuario "Caso Negativo" (Para probar RF14 - Usuario despedido/inactivo)
        User::updateOrCreate(
            ['email' => 'ex-empleado@tecnosoluciones.com'],
            [
                'name' => 'Ex Empleado',
                'password' => Hash::make('1234'),
                'rol_id' => $rolVendedor, 
                'activo' => false,       
                'bloqueado_hasta' => null,
            ]
        );
    }
}