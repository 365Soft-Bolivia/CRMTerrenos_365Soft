<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Negocio extends Model
{
    use HasFactory;

    protected $table = 'negocios';

    protected $fillable = [
        'lead_id',
        'terreno_id',
        'tipo_operacion',
        'embudo',
        'etapa',
        'fecha_inicio',
        'monto_estimado',
        'notas',
        'asesor_id',
        'convertido_cliente',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'monto_estimado' => 'decimal:2',
        'convertido_cliente' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constantes para etapas
    const ETAPA_INTERES = '🟡 Interés Generado';
    const ETAPA_CONTACTO = '🔵 Contacto Inicial';
    const ETAPA_VISITA = '🟢 Visita Programada';
    const ETAPA_PROPUESTA = '🟢 Propuesta / Oferta';
    const ETAPA_NEGOCIACION = '🟠 Negociación';
    const ETAPA_CIERRE = '🟢 Cierre / Venta Concretada';
    const ETAPA_PERDIDO = '🔴 Perdido / No Concretado';

    public static function etapas()
    {
        return [
            self::ETAPA_INTERES,
            self::ETAPA_CONTACTO,
            self::ETAPA_VISITA,
            self::ETAPA_PROPUESTA,
            self::ETAPA_NEGOCIACION,
            self::ETAPA_CIERRE,
            self::ETAPA_PERDIDO,
        ];
    }

    /**
     * Relación: Un negocio pertenece a un lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Relación: Un negocio pertenece a un terreno
     */
    public function terreno()
    {
        return $this->belongsTo(Terreno::class);
    }

    /**
     * Relación: Un negocio pertenece a un asesor
     */
    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Relación: Un negocio tiene muchos seguimientos
     */
    public function seguimientos()
    {
        return $this->hasMany(Seguimiento::class)->orderBy('fecha_seguimiento', 'desc');
    }

    /**
     * Scope: Filtrar por etapa
     */
    public function scopePorEtapa($query, $etapa)
    {
        return $query->where('etapa', $etapa);
    }

    /**
     * Scope: Filtrar por asesor
     */
    public function scopeDelAsesor($query, $asesorId)
    {
        return $query->where('asesor_id', $asesorId);
    }

    /**
     * Scope: Negocios ganados
     */
    public function scopeGanados($query)
    {
        return $query->where('etapa', self::ETAPA_CIERRE);
    }

    /**
     * Scope: Negocios perdidos
     */
    public function scopePerdidos($query)
    {
        return $query->where('etapa', self::ETAPA_PERDIDO);
    }

    /**
     * Scope: Negocios activos (ni ganados ni perdidos)
     */
    public function scopeActivos($query)
    {
        return $query->whereNotIn('etapa', [self::ETAPA_CIERRE, self::ETAPA_PERDIDO]);
    }
}