<?php

namespace Tests\Feature\CuentasCorrientes;

use App\Events\PagoRegistrado;
use App\Events\VentaRegistrada;
use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\CuentaCorriente;
use App\Models\EstadoCuentaCorriente;
use App\Models\EstadoVenta;
use App\Models\FormaPago;
use App\Models\Pago;
use App\Models\TipoCliente;
use App\Models\User;
use App\Models\Venta;
use App\Jobs\NotificarIncumplimientoCC;
use App\Listeners\ActualizarCuentaCorrientePorVenta;
use App\Listeners\VerificarNormalizacionCC;
use App\Services\CuentasCorrientes\VerificarEstadoCuentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcesoAutomaticoTest extends TestCase
{
    use RefreshDatabase;

    private EstadoCuentaCorriente $estadoActiva;
    private EstadoCuentaCorriente $estadoBloqueada;
    private EstadoVenta $estadoPendiente;
    private FormaPago $formaPagoCuentaCorriente;
    private TipoCliente $tipoMayorista;
    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear estados
        $this->estadoActiva = EstadoCuentaCorriente::factory()->create([
            'nombreEstado' => 'Activa',
        ]);
        
        $this->estadoBloqueada = EstadoCuentaCorriente::factory()->create([
            'nombreEstado' => 'Bloqueada',
        ]);
        
        $this->estadoPendiente = EstadoVenta::factory()->create([
            'nombreEstado' => 'Pendiente',
        ]);
        
        // Forma de pago
        $this->formaPagoCuentaCorriente = FormaPago::factory()->create([
            'nombreFormaPago' => 'Cuenta Corriente',
        ]);
        
        // Tipo cliente
        $this->tipoMayorista = TipoCliente::factory()->create([
            'nombreTipo' => 'Mayorista',
        ]);
        
        // Usuario vendedor
        $this->vendedor = User::factory()->create([
            'name' => 'Vendedor Test',
            'email' => 'vendedor@test.com',
            'role' => 'vendedor',
        ]);
        
        // Admin para notificaciones
        User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'role' => 'admin',
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
    public function flujo_completo_venta_vencimiento_bloqueo_pago_normalizacion()
    {
        // ========================================
        // FASE 1: REGISTRO DE VENTA
        // ========================================
        
        $cliente = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cc = CuentaCorriente::factory()->create([
            'clienteID' => $cliente->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 0.00,
        ]);
        
        // Simular venta que genera deuda
        $venta = Venta::factory()->create([
            'clienteID' => $cliente->clienteID,
            'estadoVentaID' => $this->estadoPendiente->estadoVentaID,
            'formaPagoID' => $this->formaPagoCuentaCorriente->formaPagoID,
            'total' => 60000.00, // Supera el límite
            'usuarioID' => $this->vendedor->usuarioID,
        ]);
        
        // Disparar evento de venta
        Event::dispatch(new VentaRegistrada($venta));
        
        // Ejecutar listener manualmente (en prod se hace automáticamente)
        $listener = new ActualizarCuentaCorrientePorVenta();
        $listener->handle(new VentaRegistrada($venta));
        
        // Assert: Saldo actualizado
        $cc->refresh();
        $this->assertEquals(60000.00, $cc->saldo);
        
        // Assert: Auditoría registrada
        $this->assertDatabaseHas('auditoria', [
            'accion' => Auditoria::ACCION_REGISTRAR_VENTA,
            'tablaAfectada' => 'cuenta_corriente',
        ]);
        
        // ========================================
        // FASE 2: VERIFICACIÓN AUTOMÁTICA (Detecta superación de límite)
        // ========================================
        
        $service = new VerificarEstadoCuentaService();
        $service->ejecutar();
        
        // Assert: CC bloqueada
        $cc->refresh();
        $this->assertEquals('Bloqueada', $cc->estadoCuentaCorriente->nombreEstado);
        
        // Assert: Notificaciones enviadas
        Queue::assertPushed(NotificarIncumplimientoCC::class);
        Notification::assertSentTo(
            User::where('role', 'admin')->get(),
            \App\Notifications\IncumplimientoCCNotification::class
        );
        
        // Assert: Auditoría de bloqueo
        $this->assertDatabaseHas('auditoria', [
            'accion' => Auditoria::ACCION_BLOQUEAR_CC,
            'tablaAfectada' => 'cuenta_corriente',
        ]);
        
        // ========================================
        // FASE 3: INTENTO DE NUEVA VENTA (Debe fallar)
        // ========================================
        
        // Verificar que no puede vender con CC bloqueada
        $ccBloqueada = CuentaCorriente::where('clienteID', $cliente->clienteID)
            ->whereHas('estadoCuentaCorriente', function ($q) {
                $q->where('nombreEstado', 'Bloqueada');
            })
            ->exists();
        
        $this->assertTrue($ccBloqueada);
        
        // ========================================
        // FASE 4: REGISTRO DE PAGO
        // ========================================
        
        $pago = Pago::factory()->create([
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
            'monto' => 45000.00, // Paga parte de la deuda
            'fechaPago' => now(),
        ]);
        
        // Actualizar saldo manualmente (en prod lo haría el service de pagos)
        $cc->saldo -= $pago->monto;
        $cc->save();
        
        // Disparar evento de pago
        Event::dispatch(new PagoRegistrado($pago, $this->vendedor->usuarioID));
        
        // Ejecutar listener de normalización
        $listenerNormalizacion = new VerificarNormalizacionCC();
        $listenerNormalizacion->handle(new PagoRegistrado($pago, $this->vendedor->usuarioID));
        
        // Assert: Saldo reducido pero aún por encima del límite
        $cc->refresh();
        $this->assertEquals(15000.00, $cc->saldo);
        
        // Assert: NO se normaliza porque sigue superando límite
        $this->assertEquals('Bloqueada', $cc->estadoCuentaCorriente->nombreEstado);
        
        // ========================================
        // FASE 5: SEGUNDO PAGO (Normaliza completamente)
        // ========================================
        
        $pago2 = Pago::factory()->create([
            'cuentaCorrienteID' => $cc->cuentaCorrienteID,
            'monto' => 15000.00, // Salda completamente
            'fechaPago' => now(),
        ]);
        
        $cc->saldo -= $pago2->monto;
        $cc->save();
        
        Event::dispatch(new PagoRegistrado($pago2, $this->vendedor->usuarioID));
        $listenerNormalizacion->handle(new PagoRegistrado($pago2, $this->vendedor->usuarioID));
        
        // Assert: Normalizada automáticamente
        $cc->refresh();
        $this->assertEquals(0.00, $cc->saldo);
        $this->assertEquals('Activa', $cc->estadoCuentaCorriente->nombreEstado);
        
        // Assert: Auditoría de desbloqueo
        $this->assertDatabaseHas('auditoria', [
            'accion' => Auditoria::ACCION_DESBLOQUEAR_CC,
            'tablaAfectada' => 'cuenta_corriente',
        ]);
        
        // ========================================
        // VERIFICACIÓN FINAL: Puede vender nuevamente
        // ========================================
        
        $ccActiva = CuentaCorriente::where('clienteID', $cliente->clienteID)
            ->whereHas('estadoCuentaCorriente', function ($q) {
                $q->where('nombreEstado', 'Activa');
            })
            ->exists();
        
        $this->assertTrue($ccActiva);
    }

    /** @test */
    public function flujo_con_bloqueo_desactivado_pone_en_revision()
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
            'saldo' => 75000.00, // Ya supera límite
        ]);
        
        // Act
        $service = new VerificarEstadoCuentaService();
        $service->ejecutar();
        
        // Assert: En revisión, no bloqueada
        $cc->refresh();
        $this->assertEquals('Pendiente de Aprobación', $cc->estadoCuentaCorriente->nombreEstado);
        
        // Assert: Auditoría correspondiente
        $this->assertDatabaseHas('auditoria', [
            'accion' => Auditoria::ACCION_PENDIENTE_APROBACION_CC,
            'tablaAfectada' => 'cuenta_corriente',
        ]);
    }

    /** @test */
    public function multiples_cuentas_procesadas_correctamente()
    {
        // Arrange: 3 cuentas en diferentes estados
        $cliente1 = Cliente::factory()->create(['tipoClienteID' => $this->tipoMayorista->tipoClienteID]);
        $cliente2 = Cliente::factory()->create(['tipoClienteID' => $this->tipoMayorista->tipoClienteID]);
        $cliente3 = Cliente::factory()->create(['tipoClienteID' => $this->tipoMayorista->tipoClienteID]);
        
        // CC1: Normal
        $cc1 = CuentaCorriente::factory()->create([
            'clienteID' => $cliente1->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'saldo' => 5000.00,
        ]);
        
        // CC2: Debe bloquearse
        $cc2 = CuentaCorriente::factory()->create([
            'clienteID' => $cliente2->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 75000.00,
        ]);
        
        // CC3: Bloqueada pero debe normalizarse
        $cc3 = CuentaCorriente::factory()->create([
            'clienteID' => $cliente3->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoBloqueada->estadoCuentaCorrienteID,
            'saldo' => 1000.00, // Bajo el límite
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
        $cliente = Cliente::factory()->create([
            'tipoClienteID' => $this->tipoMayorista->tipoClienteID,
        ]);
        
        $cc = CuentaCorriente::factory()->create([
            'clienteID' => $cliente->clienteID,
            'estadoCuentaCorrienteID' => $this->estadoActiva->estadoCuentaCorrienteID,
            'limiteCredito' => 50000.00,
            'saldo' => 0.00,
        ]);
        
        // Act: Simular ciclo completo
        
        // 1. Venta
        $venta = Venta::factory()->create([
            'clienteID' => $cliente->clienteID,
            'total' => 60000.00,
            'formaPagoID' => $this->formaPagoCuentaCorriente->formaPagoID,
            'estadoVentaID' => $this->estadoPendiente->estadoVentaID,
            'usuarioID' => $this->vendedor->usuarioID,
        ]);
        
        $cc->saldo = 60000.00;
        $cc->save();
        
        Auditoria::registrar(
            Auditoria::ACCION_REGISTRAR_VENTA,
            'cuenta_corriente',
            $cc->cuentaCorrienteID,
            ['saldo_anterior' => 0, 'saldo_nuevo' => 60000],
            $this->vendedor->usuarioID
        );
        
        // 2. Bloqueo
        $cc->estadoCuentaCorrienteID = $this->estadoBloqueada->estadoCuentaCorrienteID;
        $cc->save();
        
        Auditoria::registrar(
            Auditoria::ACCION_BLOQUEAR_CC,
            'cuenta_corriente',
            $cc->cuentaCorrienteID,
            ['motivo' => 'Supera límite'],
            null // Sistema automático
        );
        
        // 3. Pago
        $cc->saldo = 0.00;
        $cc->save();
        
        // 4. Desbloqueo
        $cc->estadoCuentaCorrienteID = $this->estadoActiva->estadoCuentaCorrienteID;
        $cc->save();
        
        Auditoria::registrar(
            Auditoria::ACCION_DESBLOQUEAR_CC,
            'cuenta_corriente',
            $cc->cuentaCorrienteID,
            ['motivo' => 'Normalización automática'],
            null
        );
        
        // Assert: Verificar todas las entradas de auditoría
        $registros = Auditoria::where('tablaAfectada', 'cuenta_corriente')
            ->where('registroID', $cc->cuentaCorrienteID)
            ->orderBy('fechaHora')
            ->get();
        
        $this->assertCount(3, $registros);
        $this->assertEquals(Auditoria::ACCION_REGISTRAR_VENTA, $registros[0]->accion);
        $this->assertEquals(Auditoria::ACCION_BLOQUEAR_CC, $registros[1]->accion);
        $this->assertEquals(Auditoria::ACCION_DESBLOQUEAR_CC, $registros[2]->accion);
        
        // Verificar que el sistema es el actor en acciones automáticas
        $this->assertNull($registros[1]->usuarioID); // Bloqueo automático
        $this->assertNull($registros[2]->usuarioID); // Normalización automática
    }
}
