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

// Envía recordatorios automáticos a proveedores que no respondieron
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

// --- SCHEDULER: Política de Retención del Log de Auditoría ---
// Purga registros con antigüedad mayor a la retención configurada (default 24 meses).
// Mensual, el día 1 a las 03:00 AR (horario de baja actividad).
Schedule::command('auditoria:purgar')
    ->monthlyOn(1, '03:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('✅ Política de retención de auditoría ejecutada.');
    })
    ->onFailure(function () {
        \Log::error('❌ Error al ejecutar la purga de auditoría.');
    });

/*
|--------------------------------------------------------------------------
| SCHEDULER: Respaldo de la base y de los archivos subidos
|--------------------------------------------------------------------------
|
| Es el único proceso de esta lista que protege contra pérdida de datos en
| lugar de contra acceso indebido. Si falla un disco, una migración sale mal
| o alguien ejecuta un DELETE sin WHERE, esto es lo único que permite volver
| atrás: ningún control de permisos sirve para recuperar lo que ya no está.
|
| También es lo que exige el art. 9 de la Ley 25.326, que pide garantizar la
| "seguridad" de los datos: eso incluye que no se pierdan, no solo que no se
| filtren.
|
| Orden de las tareas: limpiar primero (borra los respaldos viejos según la
| política de retención) y respaldar después, para que si el disco está al
| límite el espacio ya esté liberado cuando se escribe el nuevo.
*/

Schedule::command('backup:clean')
    ->daily()->at('01:30')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Log::error('❌ Falló la limpieza de respaldos antiguos.');
    });

Schedule::command('backup:run')
    ->daily()->at('02:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping()
    ->onSuccess(function () {
        \Log::info('✅ Respaldo diario completado.');
    })
    ->onFailure(function () {
        // Un respaldo que falla en silencio es peor que no tenerlo, porque
        // da una seguridad que no existe. Esto queda en el log; cuando haya
        // un canal de notificación configurado, el paquete además avisa.
        \Log::critical('❌ FALLÓ EL RESPALDO DIARIO. Revisar de inmediato.');
    });

// Avisa si el último respaldo es demasiado viejo o si el destino no responde.
// Sin este control, la falla recién se descubre el día que hace falta restaurar.
Schedule::command('backup:monitor')
    ->daily()->at('08:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->onFailure(function () {
        \Log::critical('❌ El monitoreo de respaldos detectó un problema.');
    });
