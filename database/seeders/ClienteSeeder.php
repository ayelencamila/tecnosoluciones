<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\CuentaCorriente;
use App\Models\Direccion;
use App\Models\EstadoCliente;
use App\Models\EstadoCuentaCorriente;
use App\Models\Localidad;
use App\Models\TipoCliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener datos necesarios
        $tipoMayorista = TipoCliente::where('nombreTipo', 'Mayorista')->first();
        $tipoMinorista = TipoCliente::where('nombreTipo', 'Minorista')->first();

        $estadoActivo = EstadoCliente::where('nombreEstado', 'Activo')->first();
        $estadoInactivo = EstadoCliente::where('nombreEstado', 'Inactivo')->first();
        $estadoMoroso = EstadoCliente::where('nombreEstado', 'Moroso')->first();
        $estadoSuspendido = EstadoCliente::where('nombreEstado', 'Suspendido')->first();

        $estadoCuentaActiva = EstadoCuentaCorriente::where('nombreEstado', 'Activa')->first();
        $estadoCuentaBloqueada = EstadoCuentaCorriente::where('nombreEstado', 'Bloqueada')->first();

        $localidades = Localidad::all();

        // Datos de ejemplo
        $clientes = [
            // MAYORISTAS ACTIVOS (10)
            ['nombre' => 'Juan', 'apellido' => 'Pérez', 'DNI' => '20123456', 'whatsapp' => '3815123456', 'mail' => 'juan.perez@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],
            ['nombre' => 'María', 'apellido' => 'González', 'DNI' => '27234567', 'whatsapp' => '3815234567', 'mail' => 'maria.gonzalez@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],
            ['nombre' => 'Carlos', 'apellido' => 'Rodríguez', 'DNI' => '30345678', 'whatsapp' => '3815345678', 'mail' => 'carlos.rodriguez@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],
            ['nombre' => 'Ana', 'apellido' => 'Martínez', 'DNI' => '25456789', 'whatsapp' => '3815456789', 'mail' => 'ana.martinez@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],
            ['nombre' => 'Luis', 'apellido' => 'López', 'DNI' => '28567890', 'whatsapp' => '3815567890', 'mail' => 'luis.lopez@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],
            ['nombre' => 'Laura', 'apellido' => 'Fernández', 'DNI' => '31678901', 'whatsapp' => '3815678901', 'mail' => 'laura.fernandez@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],
            ['nombre' => 'Diego', 'apellido' => 'Sánchez', 'DNI' => '29789012', 'whatsapp' => '3815789012', 'mail' => 'diego.sanchez@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],
            ['nombre' => 'Sofía', 'apellido' => 'Romero', 'DNI' => '26890123', 'whatsapp' => '3815890123', 'mail' => 'sofia.romero@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],
            ['nombre' => 'Miguel', 'apellido' => 'Torres', 'DNI' => '32901234', 'whatsapp' => '3815901234', 'mail' => 'miguel.torres@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],
            ['nombre' => 'Valeria', 'apellido' => 'Díaz', 'DNI' => '24012345', 'whatsapp' => '3816012345', 'mail' => 'valeria.diaz@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],

            // MINORISTAS ACTIVOS (10)
            ['nombre' => 'Javier', 'apellido' => 'Ruiz', 'DNI' => '33123456', 'whatsapp' => '3816123456', 'mail' => 'javier.ruiz@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Camila', 'apellido' => 'Morales', 'DNI' => '22234567', 'whatsapp' => '3816234567', 'mail' => 'camila.morales@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Roberto', 'apellido' => 'Castro', 'DNI' => '35345678', 'whatsapp' => '3816345678', 'mail' => 'roberto.castro@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Florencia', 'apellido' => 'Vega', 'DNI' => '21456789', 'whatsapp' => '3816456789', 'mail' => 'florencia.vega@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Martín', 'apellido' => 'Mendoza', 'DNI' => '34567890', 'whatsapp' => '3816567890', 'mail' => 'martin.mendoza@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Lucía', 'apellido' => 'Silva', 'DNI' => '23678901', 'whatsapp' => '3816678901', 'mail' => 'lucia.silva@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Fernando', 'apellido' => 'Rojas', 'DNI' => '36789012', 'whatsapp' => '3816789012', 'mail' => 'fernando.rojas@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Paula', 'apellido' => 'Ortiz', 'DNI' => '20890123', 'whatsapp' => '3816890123', 'mail' => 'paula.ortiz@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Andrés', 'apellido' => 'Núñez', 'DNI' => '37901234', 'whatsapp' => '3816901234', 'mail' => 'andres.nunez@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Victoria', 'apellido' => 'Herrera', 'DNI' => '19012345', 'whatsapp' => '3817012345', 'mail' => 'victoria.herrera@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],

            // CLIENTES CON OTROS ESTADOS (10)
            ['nombre' => 'Gustavo', 'apellido' => 'Medina', 'DNI' => '38123456', 'whatsapp' => '3817123456', 'mail' => 'gustavo.medina@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoInactivo],
            ['nombre' => 'Daniela', 'apellido' => 'Campos', 'DNI' => '18234567', 'whatsapp' => '3817234567', 'mail' => 'daniela.campos@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoMoroso],
            ['nombre' => 'Pablo', 'apellido' => 'Ríos', 'DNI' => '39345678', 'whatsapp' => '3817345678', 'mail' => 'pablo.rios@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoSuspendido],
            ['nombre' => 'Gabriela', 'apellido' => 'Vargas', 'DNI' => '17456789', 'whatsapp' => '3817456789', 'mail' => 'gabriela.vargas@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoActivo],
            ['nombre' => 'Sergio', 'apellido' => 'Paredes', 'DNI' => '40567890', 'whatsapp' => '3817567890', 'mail' => 'sergio.paredes@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoMoroso],
            ['nombre' => 'Natalia', 'apellido' => 'Aguirre', 'DNI' => '16678901', 'whatsapp' => '3817678901', 'mail' => 'natalia.aguirre@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoInactivo],
            ['nombre' => 'Ricardo', 'apellido' => 'Molina', 'DNI' => '41789012', 'whatsapp' => '3817789012', 'mail' => 'ricardo.molina@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Carolina', 'apellido' => 'Benítez', 'DNI' => '15890123', 'whatsapp' => '3817890123', 'mail' => 'carolina.benitez@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoSuspendido],
            ['nombre' => 'Alejandro', 'apellido' => 'Luna', 'DNI' => '42901234', 'whatsapp' => '3817901234', 'mail' => 'alejandro.luna@email.com', 'tipo' => $tipoMinorista, 'estado' => $estadoActivo],
            ['nombre' => 'Mónica', 'apellido' => 'Suárez', 'DNI' => '14012345', 'whatsapp' => '3818012345', 'mail' => 'monica.suarez@email.com', 'tipo' => $tipoMayorista, 'estado' => $estadoMoroso],
        ];

        foreach ($clientes as $index => $clienteData) {
            // Seleccionar localidad aleatoria
            $localidad = $localidades->random();

            // Crear dirección
            $piso = rand(0, 10) > 7 ? rand(1, 20) : null;
            $depto = rand(0, 10) > 7 ? chr(65 + rand(0, 10)) : null;
            $pisoDepto = $piso ? ($depto ? "Piso {$piso} Dto {$depto}" : "Piso {$piso}") : null;

            $direccion = Direccion::create([
                'calle' => 'Calle '.($index + 1),
                'altura' => (string) rand(100, 9999),
                'pisoDepto' => $pisoDepto,
                'barrio' => 'Barrio '.($index % 5 + 1),
                'codigoPostal' => '4000',
                'localidadID' => $localidad->localidadID,
            ]);

            // Crear cuenta corriente primero
            $esMoroso = $clienteData['estado']->nombreEstado === 'Moroso';
            $estadoCuenta = $esMoroso ? $estadoCuentaBloqueada : $estadoCuentaActiva;

            $cuentaCorriente = CuentaCorriente::create([
                'saldo' => $esMoroso ? rand(-50000, -5000) : rand(-10000, 50000),
                'limiteCredito' => $clienteData['tipo']->nombreTipo === 'Mayorista' ? 500000 : 100000,
                'diasGracia' => $clienteData['tipo']->nombreTipo === 'Mayorista' ? 30 : 15,
                'estadoCuentaCorrienteID' => $estadoCuenta->estadoCuentaCorrienteID,
            ]);

            // Asegurar DNI único (evitar conflictos si ya existen clientes en la BD)
            $dni = (string) $clienteData['DNI'];
            $originalDni = $dni;
            $suffix = 1;
            while (Cliente::where('DNI', $dni)->exists()) {
                // agregar sufijo incremental hasta que quede único
                $dni = $originalDni.$suffix;
                $suffix++;
            }

            // Asegurar mail único (por si existe una restricción única en emails)
            $mail = $clienteData['mail'];
            $originalMail = $mail;
            $atPos = strrpos($originalMail, '@');
            $local = $atPos !== false ? substr($originalMail, 0, $atPos) : $originalMail;
            $domain = $atPos !== false ? substr($originalMail, $atPos) : '';
            $mailSuffix = 1;
            while (Cliente::where('mail', $mail)->exists()) {
                $mail = $local.'+'.$mailSuffix.$domain;
                $mailSuffix++;
            }

            // Crear cliente con referencia a cuenta corriente
            $cliente = Cliente::create([
                'nombre' => $clienteData['nombre'],
                'apellido' => $clienteData['apellido'],
                'DNI' => $dni,
                'whatsapp' => $clienteData['whatsapp'],
                'telefono' => null,
                'mail' => $mail,
                'tipoClienteID' => $clienteData['tipo']->tipoClienteID,
                'estadoClienteID' => $clienteData['estado']->estadoClienteID,
                'direccionID' => $direccion->direccionID,
                'cuentaCorrienteID' => $cuentaCorriente->cuentaCorrienteID,
            ]);
        }

        echo "✅ 30 clientes creados exitosamente\n";
        echo "   • 10 Mayoristas Activos\n";
        echo "   • 10 Minoristas Activos\n";
        echo "   • 10 con estados variados (Inactivos, Morosos, Suspendidos)\n";
    }
}
