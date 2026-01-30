<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar parámetros globales del sistema (CU-31)
 * 
 * Encapsula la lógica de negocio para:
 * - Actualizar configuraciones con validación
 * - Registrar auditoría de cambios
 * - Gestionar caché de configuraciones
 * - Garantizar coherencia entre parámetros relacionados
 */
class ConfiguracionService
{
    public function __construct(
        private ConfiguracionValidationService $validator
    ) {}

    /**
     * CU-31 Flujo Principal: Actualizar múltiples configuraciones
     * 
     * @param array $configuraciones ['clave' => 'valor', ...]
     * @param int $usuarioId Usuario que realiza los cambios
     * @param string $motivo Motivo de la modificación
     * @return array ['exito' => bool, 'cambios' => array, 'errores' => array]
     */
    public function actualizarConfiguraciones(
        array $configuraciones,
        int $usuarioId,
        string $motivo = 'Modificación desde panel de configuración'
    ): array {
        // CU-31 Paso 6: Validar valores
        $validacion = $this->validator->validarMultiples($configuraciones);
        
        if (!$validacion['valido']) {
            // CU-31 Excepción 6a: Valores inválidos
            return [
                'exito' => false,
                'errores' => $validacion['errores'],
                'cambios' => [],
            ];
        }

        DB::beginTransaction();
        
        try {
            $cambios = [];
            $valoresValidados = $validacion['valores'];
            
            foreach ($valoresValidados as $clave => $valorNuevo) {
                $cambio = $this->aplicarCambio($clave, $valorNuevo, $usuarioId, $motivo);
                
                if ($cambio['modificado']) {
                    $cambios[] = $cambio;
                }
            }
            
            // CU-31 Paso 8-9: Persistir y registrar
            DB::commit();
            
            // CU-31 Paso 10: Confirmar
            Log::info('Configuraciones actualizadas exitosamente', [
                'usuario_id' => $usuarioId,
                'cantidad_cambios' => count($cambios),
                'motivo' => $motivo,
            ]);
            
            return [
                'exito' => true,
                'cambios' => $cambios,
                'errores' => [],
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // CU-31 Excepción 8a: Error al guardar
            Log::error('Error al guardar configuraciones', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'exito' => false,
                'errores' => ['general' => ['Error al guardar la configuración, intente nuevamente.']],
                'cambios' => [],
            ];
        }
    }

    /**
     * CU-31: Aplicar un cambio individual de configuración
     * 
     * @return array ['modificado' => bool, 'clave' => string, 'valor_anterior' => mixed, 'valor_nuevo' => mixed]
     */
    private function aplicarCambio(
        string $clave,
        mixed $valorNuevo,
        int $usuarioId,
        string $motivo
    ): array {
        $config = Configuracion::where('clave', $clave)->first();
        
        if (!$config) {
            // Si no existe, crear nueva configuración
            Log::warning("Configuración '{$clave}' no existe, se creará", [
                'clave' => $clave,
                'valor' => $valorNuevo,
            ]);
            
            Configuracion::create([
                'clave' => $clave,
                'valor' => $this->convertirAString($valorNuevo),
                'descripcion' => "Creado automáticamente desde panel",
            ]);
            
            return [
                'modificado' => true,
                'clave' => $clave,
                'valor_anterior' => null,
                'valor_nuevo' => $valorNuevo,
                'accion' => 'creado',
            ];
        }
        
        $valorAnterior = $config->valor;
        $valorNuevoStr = $this->convertirAString($valorNuevo);
        
        // Si el valor no cambió, no hacer nada
        if ($valorAnterior === $valorNuevoStr) {
            return [
                'modificado' => false,
                'clave' => $clave,
                'valor_anterior' => $valorAnterior,
                'valor_nuevo' => $valorNuevo,
            ];
        }
        
        // CU-31 Paso 8: Persistir cambio
        $config->valor = $valorNuevoStr;
        $config->save();
        
        // Invalidar caché
        cache()->forget("config:{$clave}");
        
        // CU-31 Paso 9: Registrar en auditoría
        Auditoria::registrar(
            Auditoria::ACCION_MODIFICAR_PARAMETRO_GLOBAL,
            'configuracion',
            $config->configuracionID,
            ['valor' => $valorAnterior],
            ['valor' => $valorNuevoStr],
            $motivo,
            "Parámetro '{$clave}' modificado de '{$valorAnterior}' a '{$valorNuevoStr}'",
            $usuarioId
        );
        
        Log::info('Configuración modificada', [
            'clave' => $clave,
            'valor_anterior' => $valorAnterior,
            'valor_nuevo' => $valorNuevoStr,
            'usuario_id' => $usuarioId,
        ]);
        
        return [
            'modificado' => true,
            'clave' => $clave,
            'valor_anterior' => $valorAnterior,
            'valor_nuevo' => $valorNuevo,
            'accion' => 'modificado',
        ];
    }

    /**
     * Obtiene configuraciones agrupadas por categoría
     * CU-31 Paso 2: Presentar parámetros agrupados
     * 
     * @return array
     */
    public function obtenerConfiguracionesAgrupadas(): array
    {
        $todas = Configuracion::all();
        
        return [
            'Generales' => $todas->filter(fn($c) => in_array($c->clave, [
                'nombre_empresa', 
                'cuit_empresa', 
                'email_contacto', 
                'direccion_empresa',
                'telefono_empresa',
            ]))->values(),
            
            'Ventas y Stock' => $todas->filter(fn($c) => 
                str_starts_with($c->clave, 'dias_maximos') || 
                str_starts_with($c->clave, 'permitir_venta') ||
                str_starts_with($c->clave, 'stock_') ||
                str_starts_with($c->clave, 'alerta_stock')
            )->values(),

            'Cuentas Corrientes' => $todas->filter(fn($c) => 
                in_array($c->clave, [
                    'dias_gracia_global',
                    'limite_credito_global',
                    'politicaAutoBlock'
                ])
            )->values(),
            
            'Reparaciones' => $todas->filter(fn($c) => 
                str_starts_with($c->clave, 'sla_') ||
                str_starts_with($c->clave, 'estados_pausa')
            )->values(),
            
            'Bonificaciones' => $todas->filter(fn($c) => 
                str_starts_with($c->clave, 'bonificacion_')
            )->values(),
            
            'Compras y Cotizaciones' => $todas->filter(fn($c) => 
                str_starts_with($c->clave, 'solicitud_cotizacion_') ||
                $c->clave === 'compras_generacion_automatica'
            )->values(),
            
            'Comunicación (WhatsApp)' => $todas->filter(fn($c) => 
                str_starts_with($c->clave, 'whatsapp_') && 
                !str_contains($c->clave, 'template') // Excluir templates viejos
            )->values(),
        ];
    }

    /**
     * Obtiene una configuración específica con casting apropiado
     * 
     * @param string $clave
     * @param mixed $default
     * @return mixed
     */
    public function obtener(string $clave, mixed $default = null): mixed
    {
        $valor = Configuracion::get($clave, $default);
        
        // Casting inteligente
        if ($valor === 'true') {
            return true;
        } elseif ($valor === 'false') {
            return false;
        } elseif (is_numeric($valor) && !$this->esTextoNumerico($clave)) {
            return +$valor;
        }
        
        return $valor;
    }

    /**
     * Verifica si una clave es un campo de texto que contiene números
     * (ej: CUIT, teléfono) y no debe convertirse a número
     */
    private function esTextoNumerico(string $clave): bool
    {
        return in_array($clave, [
            'cuit_empresa',
            'telefono_empresa',
            'whatsapp_admin_notificaciones',
        ]);
    }

    /**
     * Convierte un valor a string para almacenamiento en BD
     */
    private function convertirAString(mixed $valor): string
    {
        if (is_bool($valor)) {
            return $valor ? 'true' : 'false';
        }
        
        return (string) $valor;
    }

    /**
     * Limpia toda la caché de configuraciones
     * Útil después de importaciones masivas
     */
    public function limpiarCache(): void
    {
        Configuracion::clearCache();
        
        Log::info('Caché de configuraciones limpiado');
    }

    /**
     * Obtiene el historial de cambios de una configuración específica
     * 
     * @param string $clave
     * @param int $limite Cantidad de registros a retornar
     * @return array
     */
    public function obtenerHistorial(string $clave, int $limite = 50): array
    {
        $config = Configuracion::where('clave', $clave)->first();
        
        if (!$config) {
            return [];
        }
        
        return Auditoria::where('tabla_afectada', 'configuracion')
            ->where('registro_id', $config->configuracionID)
            ->where('accion', Auditoria::ACCION_MODIFICAR_PARAMETRO_GLOBAL)
            ->orderBy('created_at', 'desc')
            ->limit($limite)
            ->with('usuario:id,name')
            ->get()
            ->map(fn($a) => [
                'fecha' => $a->created_at,
                'usuario' => $a->usuario?->name ?? 'Sistema',
                'valor_anterior' => $a->datos_anteriores['valor'] ?? null,
                'valor_nuevo' => $a->datos_nuevos['valor'] ?? null,
                'motivo' => $a->motivo,
                'detalles' => $a->detalles,
            ])
            ->toArray();
    }
}
