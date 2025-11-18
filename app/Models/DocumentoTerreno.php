<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoTerreno extends Model
{
    use HasFactory;

    protected $table = 'documentos_terreno';

    // Solo lectura - no permitir inserciones/actualizaciones desde el CRM
    protected $guarded = ['*'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Un documento pertenece a un terreno
     */
    public function terreno()
    {
        return $this->belongsTo(Terreno::class, 'idterreno');
    }

    /**
     * Scope: Documentos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado_ocr', 'pendiente');
    }

    /**
     * Scope: Documentos procesados
     */
    public function scopeProcesados($query)
    {
        return $query->where('estado_ocr', 'procesado');
    }

    /**
     * Scope: Documentos con error
     */
    public function scopeConError($query)
    {
        return $query->where('estado_ocr', 'error');
    }

    /**
     * Scope: Documentos sin datos
     */
    public function scopeSinDatos($query)
    {
        return $query->where('estado_ocr', 'sin_datos');
    }
}