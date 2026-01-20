<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate; 
use App\Models\Venta;                
use App\Policies\VentaPolicy;

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
        
        // Registrar observer para rastrear cambios de estado en reparaciones
        \App\Models\Reparacion::observe(\App\Observers\ReparacionObserver::class);

        // Forzar HTTPS solo cuando se accede desde ngrok
        if (request()->getHost() !== 'localhost' && config('app.force_https')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
