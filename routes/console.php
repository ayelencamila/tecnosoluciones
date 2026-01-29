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
// Cierra cotizaciones vencidas diariamente a las 00:00
Schedule::command('cotizaciones:cerrar-vencidas --force')
    ->dailyAt('00:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('Cierre automático de cotizaciones vencidas ejecutado con éxito.');
    })
    ->onFailure(function () {
        \Log::error('Error al ejecutar cierre de cotizaciones vencidas.');
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
// SCHEDULER PARA CU-20: Monitoreo Automático de Stock
// ============================================================================

// --- 1. Monitoreo de Stock (diario a las 8:00 AM) ---
// Detecta productos bajo stock y alta rotación, genera y envía solicitudes
// Modo INTELIGENTE: envía por WhatsApp Y Email según lo que tenga cada proveedor
Schedule::command('stock:monitorear --generar --enviar --canal=inteligente')
    ->dailyAt('08:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('✅ Monitoreo de stock ejecutado con éxito - Solicitudes generadas y enviadas (modo inteligente).');
    })
    ->onFailure(function () {
        \Log::error('❌ Error al ejecutar monitoreo de stock.');
    });
