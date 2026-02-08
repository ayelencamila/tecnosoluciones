<?php

namespace Tests\Feature\CuentasCorrientes;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\CuentaCorriente;
use App\Models\EstadoCliente;
use App\Models\EstadoCuentaCorriente;
use App\Models\EstadoVenta;
use App\Models\MedioPago;
use App\Models\Pago;
use App\Models\Rol;
use App\Models\TipoCliente;
use App\Models\User;
use App\Models\Venta;
use App\Jobs\NotificarIncumplimientoCC;
use App\Services\CuentasCorrientes\VerificarEstadoCuentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcesoAutomaticoTest extends TestCase
{
    use RefreshDatabase;

    private EstadoCuentaCorriente $estadoActiva;
    private EstadoCuentaCorriente $estadoBloqueada;
    private EstadoVenta $estadoPendiente;
    private MedioPago $medioPagoCuentaCorriente;
    private TipoCliente $tipoMayorista;
    private EstadoCliente $estadoClienteActivo;
    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear estados CC
        $this->estadoActiva = EstadoCuentaCorriente::factory()->create([
            'nombreEstado' => 'Activa',
        ]);

        $this->estadoBloqueada = EstadoCuentaCorriente::factory()->create([
            'nombreEstado' => 'Bloqueada',
        ]);

        EstadoCuentaCorriente::factory()->create([
            'nombreEstado' => 'Pendiente de Aprobación',
        ]);

        // Estado de venta
        $this->estadoPendiente = EstadoVenta::factory()->pendiente()->create();

        // Medio de pago
        $this->medioPagoCuentaCorriente = MedioPago::create([
            'nombre' => 'Cuenta Corriente',
            'recargo_porcentaje' => 0,
            'activo' => true,
        ]);

        // Tipo cliente
        $this->tipoMayorista = TipoCliente::factory()->create([
            'nombreTipo' => 'Mayorista',
        ]);

        // Estado cliente (evitar duplicados con unique constraint)
        $this->estadoClienteActivo = EstadoCliente::firstOrCreate(
            ['nombreEstado' => 'Activo'],
            ['descripcion' => 'Cliente activo']
        );

        // Rol y usuario vendedor
        $rolVendedor = Rol::firstOrCreate(
            ['nombre' => 'vendedor'],
            ['descripcion' => 'Vendedor', 'activo' => true]
        );
        $this->vendedor = User::factory()->create([
            'name' => 'Vendedor Test',
            'email' => 'vendedor@test.com',
            'rol_id' => $rolVendedor->rol_id,
        ]);

        // Admin para notificaciones
        $rolAdmin = Rol::firstOrCreate(
            ['nombre' => 'administrador'],
            ['descripcion' => 'Administrador', 'activo' => true]
        );
        User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'rol_id' => $rolAdmin->rol_id,
        ]);

        // Configuración
        Configuracion::set('bloqueo_automatico_cc', true);
        Configuracion::set('limite_credito_global', 100000.00);
        Configuracion::set('dias_gracia_global', 30);
        Configuracion::set('whatsapp_admin_notificaciones', '+5491112345678');

        Queue::fake();
        Notification::fake();
    }

    /** @test */
    public function flujo_completo_venta_bloqueo_pago_normalizacion()
    {
        // ========================================
        // FASE 1: PREPARACIÓN
        // ========================================

        $cc = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 0.00,
        ]);

        $cliente = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
            'estadoClienteID' => $this->estadoClienteActivo->estadoClienteID,
        ]);

        // ========================================
        // FASE 2: Simular deuda que supera límite
        // ========================================

        $cc->saldo = 60000.00;
        $cc->save();

        // ========================================
        // FASE 3: Verificación automática detecta exceso de límite
        // ========================================

        $service = new VerificarEstadoCuentaService();
        $service->ejecutar();

        $cc->refresh();
        $this->assertEquals('Bloqueada', $cc->estadoCuentaCorriente->nombreEstado);

        // ========================================
        // FASE 4: Pago parcial - aún bloqueada
        // ========================================

        $cc->saldo = 15000.00; // Pagó 45000
        $cc->save();

        // Re-evaluar (sigue superando el límite de 50000 → NO, 15000 < 50000)
        // Pero el saldo vencido podría mantenerla bloqueada
        // Normalización: sin mora Y dentro del límite
        // Con calcularSaldoVencido() = 0 (no hay movimientos) y 15000 < 50000 → normaliza
        $service->ejecutar();

        $cc->refresh();
        $this->assertEquals('Activa', $cc->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function flujo_con_bloqueo_desactivado_pone_en_revision()
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
            'estadoClienteID' => $this->estadoClienteActivo->estadoClienteID,
        ]);

        // Act
        $service = new VerificarEstadoCuentaService();
        $service->ejecutar();

        // Assert: En revisión, no bloqueada
        $cc->refresh();
        $this->assertEquals('Pendiente de Aprobación', $cc->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function multiples_cuentas_procesadas_correctamente()
    {
        // Arrange: 3 cuentas en diferentes estados

        // CC1: Normal - permanece activa
        $cc1 = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 100000.00,
            'saldo' => 5000.00,
        ]);
        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc1->cuentaCorrienteID,
            'estadoClienteID' => $this->estadoClienteActivo->estadoClienteID,
        ]);

        // CC2: Debe bloquearse (supera límite)
        $cc2 = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 75000.00,
        ]);
        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc2->cuentaCorrienteID,
            'estadoClienteID' => $this->estadoClienteActivo->estadoClienteID,
        ]);

        // CC3: Bloqueada pero debe normalizarse (bajo el límite)
        $cc3 = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoBloqueada->estadoCuentaCorrienteID,
            'limiteCredito' => 100000.00,
            'saldo' => 1000.00,
        ]);
        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc3->cuentaCorrienteID,
            'estadoClienteID' => $this->estadoClienteActivo->estadoClienteID,
        ]);

        // Act
        $service = new VerificarEstadoCuentaService();
        $service->ejecutar();

        // Assert
        $cc1->refresh();
        $cc2->refresh();
        $cc3->refresh();

        $this->assertEquals('Activa', $cc1->estadoCuentaCorriente->nombreEstado);
        $this->assertEquals('Bloqueada', $cc2->estadoCuentaCorriente->nombreEstado);
        $this->assertEquals('Activa', $cc3->estadoCuentaCorriente->nombreEstado);
    }

    /** @test */
    public function auditoria_completa_de_ciclo_vida()
    {
        // Arrange
        $cc = CuentaCorriente::factory()->create([
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 0.00,
        ]);

        Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
            'estadoClienteID' => $this->estadoClienteActivo->estadoClienteID,
        ]);

        // Act: Simular ciclo completo

        // 1. Supera límite → Bloqueo
        $cc->saldo = 60000.00;
        $cc->save();

        $service = new VerificarEstadoCuentaService();
        $service->ejecutar();

        // 2. Pago normaliza → Desbloqueo
        $cc->refresh();
        $this->assertEquals('Bloqueada', $cc->estadoCuentaCorriente->nombreEstado);

        $cc->saldo = 0.00;
        $cc->save();

        $service->ejecutar();

        $cc->refresh();
        $this->assertEquals('Activa', $cc->estadoCuentaCorriente->nombreEstado);

        // Assert: Verificar entradas de auditoría
        $registros = Auditoria::where('tabla_afectada', 'cuentas_corriente')
            ->where('registro_id', $cc->cuentaCorrienteID)
            ->orderBy('created_at')
            ->get();

        // Debe tener al menos bloqueo y desbloqueo
        $acciones = $registros->pluck('accion')->toArray();
        $this->assertContains(Auditoria::ACCION_BLOQUEAR_CC, $acciones);
        $this->assertContains(Auditoria::ACCION_DESBLOQUEAR_CC, $acciones);
    }
}
