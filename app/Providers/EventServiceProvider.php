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
        \App\Events\VentaRegistrada::class => [
            \App\Listeners\ActualizarStockPorVenta::class,
            
            // ¡CORREGIDO! Comentamos esta línea para evitar el doble cobro.
            // \App\Listeners\ActualizarCuentaCorrientePorVenta::class, 
            
            \App\Listeners\RegistrarVentaEnAuditoria::class,
        ],

        // Flujo de Fallo de Stock
        \App\Events\StockUpdateFailed::class => [
            \App\Listeners\SendStockErrorNotification::class,
        ],

        // Flujo de Anulación de Venta (CU-06)
        \App\Events\VentaAnulada::class => [
            \App\Listeners\RevertirStockPorAnulacion::class,
            \App\Listeners\RevertirCuentaCorrientePorAnulacion::class,
            \App\Listeners\RegistrarAnulacionEnAuditoria::class,
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
