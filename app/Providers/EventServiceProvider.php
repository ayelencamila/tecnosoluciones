<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Flujo de Venta (CU-05)
        // NOTA: El stock se gestiona directamente en RegistrarVentaService (Sección D)
        \App\Events\VentaRegistrada::class => [
            \App\Listeners\RegistrarVentaEnAuditoria::class,
        ],

        // Flujo de Anulación de Venta (CU-06)
        // NOTA: El stock se revierte directamente en AnularVentaService::revertirStock()
        \App\Events\VentaAnulada::class => [
            \App\Listeners\RevertirCuentaCorrientePorAnulacion::class,
            \App\Listeners\RegistrarAnulacionEnAuditoria::class,
        ],

        // Flujo de Pago (CU-09 Paso 7: Normalización)
        \App\Events\PagoRegistrado::class => [
            \App\Listeners\VerificarNormalizacionCC::class,
        ],

        // Flujo de Bonificación - Decisión del Cliente (CU-14/15)
        \App\Events\ClienteRespondioBonificacion::class => [
            \App\Listeners\ProcesarDecisionCliente::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
