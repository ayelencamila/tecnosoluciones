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

// --- SCHEDULER PARA CU-20: Gestión Automática de Cotizaciones ---

// Verifica solicitudes pendientes y envía recordatorios cada 6 horas
Schedule::command('compras:verificar-solicitudes')
    ->everySixHours()
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('Verificación de solicitudes pendientes ejecutada con éxito.');
    })
    ->onFailure(function () {
        \Log::error('Error al ejecutar verificación de solicitudes pendientes.');
    });

// Envía recordatorios automáticos diariamente a las 09:00
// Modo INTELIGENTE: envía por WhatsApp Y Email según lo que tenga cada proveedor
Schedule::command('cotizaciones:enviar-recordatorios --canal=inteligente')
    ->dailyAt('09:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('Envío de recordatorios de cotización ejecutado con éxito (modo inteligente).');
    })
    ->onFailure(function () {
        \Log::error('Error al ejecutar envío de recordatorios de cotización.');
    });

// ============================================================================
// SCHEDULER PARA CU-20: Monitoreo Automático de Stock y Cotizaciones
// ============================================================================

// --- 1. Monitoreo de Stock (diario a las 8:00 AM) ---
// Detecta productos bajo stock / alta rotación
// Si automatización está ACTIVADA: genera y ENVÍA solicitudes automáticamente
// Si automatización está DESACTIVADA: no hace nada (usuario crea manualmente)
Schedule::command('stock:monitorear')
    ->dailyAt('08:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('✅ Monitoreo de stock ejecutado.');
    })
    ->onFailure(function () {
        \Log::error('❌ Error en monitoreo de stock.');
    });

// --- 2. Cierre de Solicitudes Vencidas (cada hora) ---
// Verifica qué solicitudes vencieron y las cierra
// Genera el ranking de ofertas para que el usuario elija
Schedule::command('cotizaciones:cerrar-vencidas')
    ->hourly()
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('✅ Verificación de vencimiento ejecutada.');
    })
    ->onFailure(function () {
        \Log::error('❌ Error al verificar vencimientos.');
    });
