<?php

namespace Tests\Unit\Services\CuentasCorrientes;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;
use App\Models\TipoCliente;
use App\Models\User;
use App\Jobs\NotificarIncumplimientoCC;
use App\Services\CuentasCorrientes\VerificarEstadoCuentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VerificarEstadoCuentaServiceTest extends TestCase
{
    use RefreshDatabase;

    private VerificarEstadoCuentaService $service;
    private EstadoCuentaCorriente $estadoActiva;
    private EstadoCuentaCorriente $estadoBloqueada;
    private EstadoCuentaCorriente $estadoRevision;
    private TipoCliente $tipoMayorista;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new VerificarEstadoCuentaService();
        
        // Crear estados necesarios
        $this->estadoActiva = EstadoCuentaCorriente::factory()->create([
            'nombreEstado' => 'Activa',
            'descripcionEstado' => 'Cuenta activa',
        ]);
        
        $this->estadoBloqueada = EstadoCuentaCorriente::factory()->create([
            'nombreEstado' => 'Bloqueada',
            'descripcionEstado' => 'Cuenta bloqueada',
        ]);
        
        $this->estadoRevision = EstadoCuentaCorriente::factory()->create([
            'nombreEstado' => 'Pendiente de Aprobación',
            'descripcionEstado' => 'Pendiente de revisión',
        ]);
        
        // Crear tipo cliente
        $this->tipoMayorista = TipoCliente::factory()->create([
            'nombreTipo' => 'Mayorista',
        ]);
        
        // Configurar parámetros
        Configuracion::set('bloqueo_automatico_cc', true);
        Configuracion::set('limite_credito_global', 100000.00);
        Configuracion::set('dias_gracia_global', 30);
        Configuracion::set('whatsapp_admin_notificaciones', '+5491112345678');
        
        // Crear usuario admin para notificaciones
        User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);
        
        Queue::fake();
        Notification::fake();
        Log::spy();
    }

    /** @test */
    public function cuenta_normal_no_requiere_accion()
    {
        // Arrange: CC con saldo bajo y sin vencidos
        $cliente = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cc = CuentaCorriente::factory()->create([
            'clienteID' => $cliente->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => null, // Usará global
            'saldo' => 5000.00, // Muy por debajo del límite
        ]);

        // Act
        $this->service->ejecutar();

        // Assert: No debe bloquearse ni enviarse notificaciones
        Queue::assertNotPushed(NotificarIncumplimientoCC::class);
        
        $cc->refresh();
        $this->assertEquals('Activa', $cc->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function cuenta_con_saldo_vencido_se_bloquea_automaticamente()
    {
        // Arrange: CC con saldo vencido
        $cliente = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cc = CuentaCorriente::factory()->create([
            'clienteID' => $cliente->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => null,
            'saldo' => 50000.00,
        ]);
        
        // Simular saldo vencido usando mock
        $this->mock(CuentaCorriente::class, function ($mock) use ($cc) {
            $mock->shouldReceive('calcularSaldoVencido')
                 ->andReturn(10000.00); // Tiene saldo vencido
        });

        // Act
        $this->service->ejecutar();

        // Assert: Debe bloquearse y notificar
        Queue::assertPushed(NotificarIncumplimientoCC::class);
        
        Log::shouldHaveReceived('warning')
           ->with(\Mockery::on(function ($message) use ($cc) {
               return str_contains($message, 'BLOQUEADA') 
                   && str_contains($message, (string)$cc->cuentaCorrienteID);
           }));
    }

    /** @test */
    public function cuenta_que_supera_limite_se_bloquea()
    {
        // Arrange: CC que supera límite de crédito
        $cliente = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cc = CuentaCorriente::factory()->create([
            'clienteID' => $cliente->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 75000.00, // Supera el límite específico
        ]);

        // Act
        $this->service->ejecutar();

        // Assert
        Queue::assertPushed(NotificarIncumplimientoCC::class, function ($job) use ($cc) {
            return $job->cuentaCorriente->cuentaCorrienteID === $cc->cuentaCorrienteID;
        });
        
        $cc->refresh();
        $this->assertEquals('Bloqueada', $cc->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function cuenta_se_pone_en_revision_si_bloqueo_automatico_desactivado()
    {
        // Arrange: Desactivar bloqueo automático
        Configuracion::set('bloqueo_automatico_cc', false);
        
        $cliente = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cc = CuentaCorriente::factory()->create([
            'clienteID' => $cliente->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 75000.00,
        ]);

        // Act
        $this->service->ejecutar();

        // Assert: Debe ir a revisión, no bloquearse
        Queue::assertPushed(NotificarIncumplimientoCC::class);
        
        $cc->refresh();
        $this->assertEquals('Pendiente de Aprobación', $cc->estadoCuentaCorriente->nombreEstado);
        
        Log::shouldHaveReceived('info')
           ->with(\Mockery::on(function ($message) {
               return str_contains($message, 'REVISIÓN');
           }));
    }

    /** @test */
    public function cuenta_bloqueada_se_normaliza_automaticamente()
    {
        // Arrange: CC bloqueada pero que ahora está normalizada
        $cliente = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cc = CuentaCorriente::factory()->create([
            'clienteID' => $cliente->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoBloqueada->estadoCuentaCorrienteID,
            'limiteCredito' => 100000.00,
            'saldo' => 5000.00, // Bajo el límite
        ]);

        // Act
        $this->service->ejecutar();

        // Assert: Debe normalizarse
        $cc->refresh();
        $this->assertEquals('Activa', $cc->estadoCuentaCorriente->nombreEstado);
        
        Log::shouldHaveReceived('info')
           ->with(\Mockery::on(function ($message) use ($cc) {
               return str_contains($message, 'NORMALIZADA') 
                   && str_contains($message, (string)$cc->cuentaCorrienteID);
           }));
    }

    /** @test */
    public function cuenta_en_revision_se_normaliza_automaticamente()
    {
        // Arrange: CC en revisión que ya cumple condiciones
        $cliente = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cc = CuentaCorriente::factory()->create([
            'clienteID' => $cliente->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoRevision->estadoCuentaCorrienteID,
            'limiteCredito' => 100000.00,
            'saldo' => 10000.00,
        ]);

        // Act
        $this->service->ejecutar();

        // Assert
        $cc->refresh();
        $this->assertEquals('Activa', $cc->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function envia_notificaciones_a_administradores()
    {
        // Arrange
        $cliente = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cc = CuentaCorriente::factory()->create([
            'clienteID' => $cliente->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 75000.00,
        ]);

        // Act
        $this->service->ejecutar();

        // Assert: Notificación al panel
        Notification::assertSentTo(
            User::where('role', 'admin')->get(),
            \App\Notifications\IncumplimientoCCNotification::class
        );
        
        // Assert: Job de WhatsApp
        Queue::assertPushed(NotificarIncumplimientoCC::class, 2); // bloqueo + admin_alert
    }

    /** @test */
    public function maneja_errores_sin_detener_proceso()
    {
        // Arrange: Crear múltiples CCs, una problemática
        $cliente1 = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cliente2 = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cc1 = CuentaCorriente::factory()->create([
            'clienteID' => $cliente1->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => null,
            'saldo' => 5000.00,
        ]);
        
        $cc2 = CuentaCorriente::factory()->create([
            'clienteID' => $cliente2->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => null,
            'saldo' => 5000.00,
        ]);

        // Act: El proceso debe completarse aunque haya un error
        $this->service->ejecutar();

        // Assert: Log de inicio y fin debe estar presente
        Log::shouldHaveReceived('info')
           ->with(\Mockery::on(function ($message) {
               return str_contains($message, 'INICIO PROCESO');
           }));
        
        Log::shouldHaveReceived('info')
           ->with(\Mockery::on(function ($message) {
               return str_contains($message, 'FIN PROCESO');
           }));
    }
}
