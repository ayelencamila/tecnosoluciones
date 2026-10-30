<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Venta;
use App\Policies\VentaPolicy;
use App\Support\TenantContext;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Gate::policy(Venta::class, VentaPolicy::class);

        // Nivel de plataforma: el super usuario pasa cualquier Gate y Policy.
        // Devolver null (y no false) es importante: deja que la autorizacion
        // siga su curso normal para el resto de los usuarios en lugar de
        // denegarla. Mismo patron que `is_superuser` en Django.
        Gate::before(function ($user) {
            return $user->esSuperAdmin() ? true : null;
        });

        $this->propagarTenantALaCola();

        // Registrar observer para rastrear cambios de estado en reparaciones
        \App\Models\Reparacion::observe(\App\Observers\ReparacionObserver::class);

        // Forzar HTTPS solo cuando se accede desde ngrok
        if (request()->getHost() !== 'localhost' && config('app.force_https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }

    /**
     * Propaga la empresa activa a los trabajos en cola.
     *
     * Un worker corre sin usuario autenticado, por lo que TenantContext no
     * tendria tenant y el Global Scope no restringiria nada. Para un job eso
     * es peligroso: `PlantillaWhatsapp::where('tipo_evento', ...)` tomaria la
     * plantilla de una empresa cualquiera y le escribiria al cliente de otra
     * con el texto equivocado; lo mismo con los parametros de `configuracion`.
     *
     * La solucion se aplica en un unico lugar en lugar de job por job: se
     * guarda la empresa activa en el payload al despachar y se restaura en el
     * worker antes de ejecutar. Asi queda cubierto todo job existente y
     * cualquiera que se agregue despues, sin que su autor tenga que acordarse.
     *
     * El contexto se limpia al terminar (y al fallar) porque el worker es un
     * proceso de larga vida: sin limpiarlo, un job heredaria la empresa del
     * job anterior.
     */
    private function propagarTenantALaCola(): void
    {
        Queue::createPayloadUsing(function () {
            $empresaId = TenantContext::empresaId();

            return $empresaId !== null ? ['empresa_id' => $empresaId] : [];
        });

        Queue::before(function (JobProcessing $event) {
            TenantContext::establecer($event->job->payload()['empresa_id'] ?? null);
        });

        Queue::after(fn () => TenantContext::limpiar());
        Queue::failing(fn () => TenantContext::limpiar());
    }
}
