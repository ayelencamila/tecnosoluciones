<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Política de retención del log de auditoría.
 *
 * Purga (o simula purgar) los registros con una antigüedad mayor a N meses,
 * para no degradar los índices ni saturar el almacenamiento (RNF6).
 *
 * La eliminación se hace a nivel de query builder a propósito: el modelo
 * Auditoria bloquea `deleting` (append-only, Sommerville), y la retención es un
 * proceso de infraestructura autorizado, no la edición de un registro individual.
 */
class PurgarAuditoria extends Command
{
    protected $signature = 'auditoria:purgar
                            {--meses=24 : Antigüedad mínima (en meses) para purgar}
                            {--dry-run : Solo informa cuántos registros se eliminarían, sin borrar}';

    protected $description = 'Aplica la política de retención del log de auditoría (purga registros antiguos)';

    public function handle(): int
    {
        $meses = max(1, (int) $this->option('meses'));
        $corte = now()->subMonths($meses);
        $dryRun = (bool) $this->option('dry-run');

        $cantidad = DB::table('auditorias')->where('created_at', '<', $corte)->count();

        $this->info("Registros con antigüedad mayor a {$meses} meses (anteriores a {$corte->format('d/m/Y')}): {$cantidad}");

        if ($cantidad === 0) {
            $this->info('No hay registros para purgar.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->comment('Modo --dry-run: no se eliminó nada.');

            return self::SUCCESS;
        }

        $eliminados = DB::table('auditorias')->where('created_at', '<', $corte)->delete();

        // Dejar rastro del propio barrido (el registro nuevo no entra en el corte).
        Auditoria::create([
            'accion' => Auditoria::ACCION_PURGAR_AUDITORIA,
            'tabla_afectada' => 'auditorias',
            'motivo' => 'Política de retención automática',
            'detalles' => "Se purgaron {$eliminados} registros anteriores a {$corte->format('Y-m-d')} (retención: {$meses} meses).",
        ]);

        $this->info("✅ Purga completada: {$eliminados} registros eliminados.");
        Log::channel('daily')->info("auditoria:purgar eliminó {$eliminados} registros (retención {$meses} meses).");

        return self::SUCCESS;
    }
}
