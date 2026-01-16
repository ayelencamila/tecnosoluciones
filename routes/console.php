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

// ============================================================================
// SCHEDULER PARA CU-20: Monitoreo Automático de Stock y Cotizaciones
// ============================================================================

// --- 1. Monitoreo de Stock (diario a las 8:00 AM) ---
// Detecta productos bajo stock y alta rotación, genera y envía solicitudes
Schedule::command('stock:monitorear --generar --enviar')
    ->dailyAt('08:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('✅ Monitoreo de stock ejecutado con éxito - Solicitudes generadas y enviadas.');
    })
    ->onFailure(function () {
        \Log::error('❌ Error al ejecutar monitoreo de stock.');
    });

// --- 2. Cerrar Solicitudes Vencidas (cada hora) ---
// Marca como vencidas las solicitudes que superaron su fecha límite
Schedule::command('cotizaciones:cerrar-vencidas')
    ->hourly()
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('✅ Verificación de solicitudes vencidas ejecutada con éxito.');
    })
    ->onFailure(function () {
        \Log::error('❌ Error al verificar solicitudes vencidas.');
    });

// --- 3. Enviar Recordatorios a Proveedores (diario a las 9:00 AM) ---
// Recuerda a proveedores que no han respondido (después de 2 días, máximo 3 recordatorios)
Schedule::command('cotizaciones:enviar-recordatorios --dias=2 --canal=ambos')
    ->dailyAt('09:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('✅ Recordatorios de cotización enviados con éxito.');
    })
    ->onFailure(function () {
        \Log::error('❌ Error al enviar recordatorios de cotización.');
    });
