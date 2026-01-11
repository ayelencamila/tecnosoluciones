<?php

namespace App\Services\Compras;

use App\Models\OfertaCompra;
use App\Models\Auditoria;
use App\Models\EstadoOferta; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Exception;

class RegistrarOfertaService
{
    public function ejecutar(array $datos, int $usuarioId): OfertaCompra
    {
        return DB::transaction(function () use ($datos, $usuarioId) {
            
            // 1. Manejo del Archivo Adjunto (Si existe)
            $rutaArchivo = null;
            if (isset($datos['archivo_adjunto']) && $datos['archivo_adjunto'] instanceof UploadedFile) {
                // Guardar en disco 'private' o 'public' según seguridad requerida
                $rutaArchivo = $datos['archivo_adjunto']->store('compras/ofertas', 'public');
            }

            // 2. Generar Código Único (Ej: OF-202310-X8J)
            // Usamos un hash corto para evitar colisiones
            $codigo = 'OF-' . now()->format('Ym') . '-' . strtoupper(Str::random(4));

            // 3. Crear Cabecera
            // Calculamos el total sumando los detalles (Regla de integridad)
            $totalEstimado = collect($datos['items'])->sum(function ($item) {
                return $item['cantidad'] * $item['precio_unitario'];
            });

            // Buscar ID del estado inicial (Pendiente)
            // Asumimos que los seeders corrieron correctamente o usamos fallback
            $estadoId = DB::table('estados_oferta')->where('nombre', 'Pendiente')->value('id') ?? 1;

            $oferta = OfertaCompra::create([
                'codigo_oferta' => $codigo,
                'proveedor_id' => $datos['proveedor_id'],
                'solicitud_id' => $datos['solicitud_id'] ?? null,
                'user_id' => $usuarioId,
                'fecha_recepcion' => $datos['fecha_recepcion'],
                'validez_hasta' => $datos['validez_hasta'] ?? null,
                'archivo_adjunto' => $rutaArchivo,
                'observaciones' => $datos['observaciones'], // Motivo del registro
                'estado_id' => $estadoId,
                'total_estimado' => $totalEstimado,
            ]);

            // 4. Crear Detalles (Iteración sobre items)
            foreach ($datos['items'] as $item) {
                $oferta->detalles()->create([
                    'producto_id' => $item['producto_id'],
                    'cantidad_ofrecida' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'disponibilidad_inmediata' => $item['disponibilidad_inmediata'] ?? true,
                    'dias_entrega' => $item['dias_entrega'] ?? 0,
                    'observaciones' => $item['observaciones'] ?? null,
                ]);
            }

            // 5. Auditoría (Registro en Historial de Operaciones)
            Auditoria::create([
                'accion' => 'Registrar Oferta',
                'tabla_afectada' => 'ofertas_compra',
                'registro_id' => $oferta->id,
                'user_id' => $usuarioId,
                'motivo' => $datos['observaciones'],
                'detalles_json' => json_encode([
                    'proveedor' => $datos['proveedor_id'],
                    'items_count' => count($datos['items']),
                    'total' => $totalEstimado
                ]),
                'fecha' => now(),
            ]);

            return $oferta;
        });
    }
}