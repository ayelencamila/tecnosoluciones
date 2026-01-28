<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para tipos de comprobante
 * Tabla: tipos_comprobante
 */
class TipoComprobante extends Model
{
    protected $table = 'tipos_comprobante';
    protected $primaryKey = 'tipo_id';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'prefijo',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Obtener comprobantes de este tipo
     */
    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class, 'tipo_comprobante_id', 'tipo_id');
    }

    /**
     * Buscar por código
     */
    public static function porCodigo(string $codigo): ?self
    {
        return static::where('codigo', $codigo)->first();
    }
}
