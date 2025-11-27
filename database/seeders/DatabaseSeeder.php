<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\Direccion; // <-- Agregado
use App\Models\EstadoCliente; // <-- Agregado
use App\Models\EstadoCuentaCorriente;
use App\Models\Localidad;
use App\Models\TipoCliente;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder principal del sistema TecnoSoluciones
 * * Este seeder ejecuta todos los seeders necesarios para inicializar
 * la base de datos con los datos básicos del sistema de gestión.
 * * Orden de ejecución:
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
        $this->call(UserSeeder::class);
        $this->command->info('🚀 Iniciando población de base de datos...');

        // 1. Datos geográficos primero (requeridos por direcciones)
        $this->call([
            ProvinciaSeeder::class,
            LocalidadSeeder::class,
        ]);

        // 2. Datos de configuración del sistema (incluye TipoCliente, EstadoCliente, EstadoCuentaCorriente)
        $this->call([
            TipoClienteSeeder::class,
            EstadoClienteSeeder::class,
            EstadoCuentaCorrienteSeeder::class,
            
        ]);

        // 3. Datos del módulo de Productos
        $this->call([
            DepositoSeeder::class,
            CategoriaProductoSeeder::class,
            EstadoProductoSeeder::class,
            ProductoSeeder::class,
            EstadoReparacionSeeder::class,
        ]);

        // 4. Datos de ejemplo - Clientes (aquí se llamará a tu ClienteSeeder que crea 30 clientes)
        $this->call([
            ClienteSeeder::class,
        ]);

        // Llama al seeder de Configuracion
        $this->call(ConfiguracionSeeder::class);

        // --- LÓGICA PARA EL CLIENTE "CONSUMIDOR FINAL" ---
        $this->command->info('Creando o verificando cliente "Consumidor Final"...');

        $this->call(MedioPagoSeeder::class);

        // 1. OBTENER DEPENDENCIAS (Asegurado que existen por los $this->call anteriores)
        $estadoActivaCC = EstadoCuentaCorriente::where('nombreEstado', 'Activa')->first();
        $estadoActivoCliente = EstadoCliente::where('nombreEstado', 'Activo')->first();
        $tipoClienteGeneral = TipoCliente::where('nombreTipo', 'Minorista')->first();
        $localidadGenerica = Localidad::first();

        // Si por alguna razón la localidad genérica no se encuentra (aunque no debería pasar con LocalidadSeeder)
        if (! $localidadGenerica) {
            $this->command->error('Error: No se encontró ninguna localidad para asignar al Consumidor Final. Revisa LocalidadSeeder.');

            return; // Detener el seeder si falta una dependencia crítica
        }

        // --- 2. CREAR DIRECCIÓN GENÉRICA (OBLIGATORIA) ---
        // Usamos firstOrCreate para evitar duplicar la dirección si el seeder se corre múltiples veces
        $direccionConsumidorFinal = Direccion::firstOrCreate(
            [
                'calle' => 'Consumidor Final',
                'altura' => '0',
                'localidadID' => $localidadGenerica->localidadID, // Asignar una localidad válida
            ],
            [
                'pisoDepto' => null,
                'barrio' => 'N/A',
                'codigoPostal' => '0000',
            ]
        );

        // --- 3. CREAR CUENTA CORRIENTE ---
        // Usaremos firstOrCreate en lugar de create para evitar duplicados si se corre varias veces.
        // Los campos 'limiteCredito' y 'diasGracia' con 0 son buenos candidatos para identificar esta CC genérica.
        $cuentaCorrienteConsumidorFinal = CuentaCorriente::firstOrCreate(
            [
                'limiteCredito' => 0,
                'diasGracia' => 0,
            ],
            [
                'saldo' => 0,
                'estadoCuentaCorrienteID' => $estadoActivaCC->estadoCuentaCorrienteID,
            ]
        );

        // --- 4. CREAR/ACTUALIZAR CLIENTE (Asignando Dirección y CC) ---
        $clienteConsumidorFinal = Cliente::firstOrCreate(
            [
                'DNI' => '0000000000',
            ],
            [
                'nombre' => 'Consumidor',
                'apellido' => 'Final',
                'telefono' => null,
                'mail' => null,
                'direccionID' => $direccionConsumidorFinal->direccionID, // <--- ¡ID VÁLIDO DE LA DIRECCIÓN CREADA!
                'tipoClienteID' => $tipoClienteGeneral->tipoClienteID,
                'estadoClienteID' => $estadoActivoCliente->estadoClienteID,
                'cuentaCorrienteID' => $cuentaCorrienteConsumidorFinal->cuentaCorrienteID, // Asignar la CC
            ]
        );

        $this->command->info('✅ Cliente "Consumidor Final" asegurado (ID: '.$clienteConsumidorFinal->clienteID.').');
        // --- FIN LÓGICA PARA EL CLIENTE "CONSUMIDOR FINAL" ---

        // 5. Usuario administrador para testing (opcional)
        // Descomenta si necesitas un usuario para pruebas
        /*
        User::factory()->create([
            'name' => 'Administrador',
            'mail' => 'admin@tecnosoluciones.com',
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
        $this->command->info('   • Cliente "Consumidor Final" (DNI: 0000000000)');
        $this->command->info(' El sistema está listo para operar');

    }
}
