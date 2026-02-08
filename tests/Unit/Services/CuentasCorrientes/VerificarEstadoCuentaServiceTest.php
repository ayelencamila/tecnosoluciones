<?php

namespace Tests\Unit\Services\CuentasCorrientes;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;
use App\Models\Rol;
use App\Models\TipoCliente;
use App\Models\User;
use App\Jobs\NotificarIncumplimientoCC;
use App\Notifications\IncumplimientoCCNotification;
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
            'descripcion' => 'Cuenta activa',
        ]);

        $this->estadoBloqueada = EstadoCuentaCorriente::factory()->create([
            'nombreEstado' => 'Bloqueada',
            'descripcion' => 'Cuenta bloqueada',
        ]);

        $this->estadoRevision = EstadoCuentaCorriente::factory()->create([
            'nombreEstado' => 'Pendiente de Aprobación',
            'descripcion' => 'Pendiente de revisión',
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

        // Crear usuario admin (el servicio busca rol 'administrador')
        $rolAdmin = Rol::firstOrCreate(
            ['nombre' => 'administrador'],
            ['descripcion' => 'Administrador', 'activo' => true]
        );
        User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'rol_id' => $rolAdmin->rol_id,
        ]);

        Queue::fake();
        Notification::fake();
        Log::spy();
    }

    /** @test */
    public function cuenta_normal_no_requiere_accion()
    {
        // Arrange: CC con saldo bajo (limiteCredito=0 usa global 100000)
        $cc = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 0,
            'saldo' => 5000.00,
        ]);

        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
        ]);

        // Act
        $this->service->ejecutar();

        // Assert: No debe bloquearse ni enviarse notificaciones
        Queue::assertNotPushed(NotificarIncumplimientoCC::class);

        $cc->refresh();
        $this->assertEquals('Activa', $cc->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function cuenta_que_supera_limite_global_se_bloquea()
    {
        // Arrange: CC con saldo que supera el límite global (100000)
        $cc = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 0,
            'saldo' => 150000.00,
        ]);

        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
        ]);

        // Act
        $this->service->ejecutar();

        // Assert
        Queue::assertPushed(NotificarIncumplimientoCC::class);

        $cc->refresh();
        $this->assertEquals('Bloqueada', $cc->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function cuenta_que_supera_limite_especifico_se_bloquea()
    {
        // Arrange: CC que supera límite de crédito específico
        $cc = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 75000.00,
        ]);

        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
        ]);

        // Act
        $this->service->ejecutar();

        // Assert
        Queue::assertPushed(NotificarIncumplimientoCC::class);

        $cc->refresh();
        $this->assertEquals('Bloqueada', $cc->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function cuenta_se_pone_en_revision_si_bloqueo_automatico_desactivado()
    {
        // Arrange: Desactivar bloqueo automático
        Configuracion::set('bloqueo_automatico_cc', false);

        $cc = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 75000.00,
        ]);

        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
        ]);

        // Act
        $this->service->ejecutar();

        // Assert: Debe ir a revisión, no bloquearse
        Queue::assertPushed(NotificarIncumplimientoCC::class);

        $cc->refresh();
        $this->assertEquals('Pendiente de Aprobación', $cc->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function cuenta_bloqueada_se_normaliza_automaticamente()
    {
        // Arrange: CC bloqueada pero que ahora está normalizada
        $cc = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoBloqueada->estadoCuentaCorrienteID,
            'limiteCredito' => 100000.00,
            'saldo' => 5000.00,
        ]);

        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
        ]);

        // Act
        $this->service->ejecutar();

        // Assert: Debe normalizarse
        $cc->refresh();
        $this->assertEquals('Activa', $cc->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function cuenta_en_revision_se_normaliza_automaticamente()
    {
        // Arrange: CC en revisión que ya cumple condiciones
        $cc = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoRevision->estadoCuentaCorrienteID,
            'limiteCredito' => 100000.00,
            'saldo' => 10000.00,
        ]);

        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
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
        $cc = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 75000.00,
        ]);

        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
        ]);

        // Act
        $this->service->ejecutar();

        // Assert: Notificación al panel
        $admin = User::whereHas('rol', fn ($q) => $q->where('nombre', 'administrador'))->first();
        Notification::assertSentTo($admin, IncumplimientoCCNotification::class);

        // Assert: Job de WhatsApp (admin_alert antes del bloqueo + admin_alert del notificarAdministradores + bloqueo)
        Queue::assertPushed(NotificarIncumplimientoCC::class);
    }

    /** @test */
    public function maneja_errores_sin_detener_proceso()
    {
        // Arrange: Crear CCs normales
        $estadoCliente = \App\Models\EstadoCliente::firstOrCreate(
            ['nombreEstado' => 'Activo'],
            ['descripcion' => 'Cliente activo']
        );

        $cc1 = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 0,
            'saldo' => 5000.00,
        ]);

        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc1->cuentaCorrienteID,
            'estadoClienteID' => $estadoCliente->estadoClienteID,
        ]);

        $cc2 = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 0,
            'saldo' => 5000.00,
        ]);

        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc2->cuentaCorrienteID,
            'estadoClienteID' => $estadoCliente->estadoClienteID,
        ]);

        // Act
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
