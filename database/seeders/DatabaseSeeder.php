<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder principal del sistema TecnoSoluciones
 * 
 * Este seeder ejecuta todos los seeders necesarios para inicializar
 * la base de datos con los datos básicos del sistema de gestión.
 * 
 * Orden de ejecución:
 * 1. Datos geográficos (Provincias y Localidades)
 * 2. Tipos y estados de clientes
 * 3. Estados de cuenta corriente
 * 4. Usuario administrador (opcional)
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta todos los seeders del sistema
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando población de base de datos...');
        
        // 1. Datos geográficos primero (requeridos por direcciones)
        $this->call([
            ProvinciaSeeder::class,
        ]);
        
        // 2. Datos de configuración del sistema
        $this->call([
            TipoClienteSeeder::class,
            EstadoClienteSeeder::class,
            EstadoCuentaCorrienteSeeder::class,
        ]);
        
        // 3. Datos del módulo de Productos
        $this->call([
            CategoriaProductoSeeder::class,
            EstadoProductoSeeder::class,
            ProductoSeeder::class,
        ]);
        
        // 4. Datos de ejemplo - Clientes
        $this->call([
            ClienteSeeder::class,
        ]);
        
        // 3. Usuario administrador para testing (opcional)
        // Descomenta si necesitas un usuario para pruebas
        /*
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@tecnosoluciones.com',
            'password' => bcrypt('password'),
        ]);
        */
        
        $this->command->info(' Base de datos poblada exitosamente');
        $this->command->info(' Datos disponibles:');
        $this->command->info('   • Provincias argentinas con localidades principales');
        $this->command->info('   • Tipos de cliente: Mayorista y Minorista');
        $this->command->info('   • Estados de cliente: Activo, Inactivo, Suspendido, Moroso');
        $this->command->info('   • Estados de cuenta corriente: Activa, Bloqueada, Vencida, Cerrada');
        $this->command->info('   • Categorías de productos: Equipos, Accesorios, Repuestos, Servicios Técnicos');
        $this->command->info('   • Estados de producto: Activo, Inactivo, Descontinuado');
        $this->command->info('   • 12 productos de ejemplo');
        $this->command->info('   • 30 clientes de ejemplo');
        $this->command->info(' El sistema está listo para operar');
    }
}
