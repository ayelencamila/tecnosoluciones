<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- SCHEDULER PARA CU-09: Control Automático de Cuentas Corrientes ---
// Ejecuta el comando diariamente a las 7:00 AM
Schedule::command('cc:check-vencimientos')
    ->dailyAt('07:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('Verificación de cuentas corrientes vencidas ejecutada con éxito.');
    })
    ->onFailure(function () {
        \Log::error('Error al ejecutar verificación de cuentas corrientes vencidas.');
    });

// --- SCHEDULER PARA CU-14: Monitoreo de SLA de Reparaciones ---
// Ejecuta el comando cada hora para verificar reparaciones con SLA excedido
Schedule::command('reparaciones:monitorear-sla')
    ->hourly()
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('Monitoreo de SLA de reparaciones ejecutado con éxito.');
    })
    ->onFailure(function () {
        \Log::error('Error al ejecutar monitoreo de SLA de reparaciones.');
    });
