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
        'embudo_id',
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

    // Constantes para etapas (sin emojis - ahora se obtienen de la BD)
    const ETAPA_INTERES = 'Interés Generado';
    const ETAPA_CONTACTO = 'Contacto Inicial';
    const ETAPA_VISITA = 'Visita Programada';
    const ETAPA_PROPUESTA = 'Propuesta / Oferta';
    const ETAPA_NEGOCIACION = 'Negociación';
    const ETAPA_CIERRE = 'Cierre / Venta Concretada';
    const ETAPA_PERDIDO = 'Perdido / No Concretado';

    /**
     * Obtener todas las etapas disponibles desde la tabla embudos
     */
    public static function etapas()
    {
        return Embudo::activos()->ordenado()->pluck('nombre')->toArray();
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
     * Relación: Un negocio pertenece a un embudo/etapa
     */
    public function embudoRelacion()
    {
        return $this->belongsTo(Embudo::class, 'embudo_id');
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