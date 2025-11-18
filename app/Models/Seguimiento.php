<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seguimiento extends Model
{
    use HasFactory;

    protected $table = 'seguimientos';

    protected $fillable = [
        'negocio_id',
        'tipo',
        'descripcion',
        'fecha_seguimiento',
        'proximo_seguimiento',
        'recordatorio_enviado',
        'asesor_id',
    ];

    protected $casts = [
        'fecha_seguimiento' => 'datetime',
        'proximo_seguimiento' => 'date',
        'recordatorio_enviado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Constantes para tipos de seguimiento
    const TIPO_LLAMADA = '📞 Llamada';
    const TIPO_EMAIL = '📧 Email';
    const TIPO_VISITA = '🚗 Visita';
    const TIPO_REUNION = '💬 Reunión';
    const TIPO_WHATSAPP = '💬 WhatsApp';

    public static function tipos()
    {
        return [
            self::TIPO_LLAMADA,
            self::TIPO_EMAIL,
            self::TIPO_VISITA,
            self::TIPO_REUNION,
            self::TIPO_WHATSAPP,
        ];
    }

    /**
     * Relación: Un seguimiento pertenece a un negocio
     */
    public function negocio()
    {
        return $this->belongsTo(Negocio::class);
    }

    /**
     * Relación: Un seguimiento pertenece a un asesor
     */
    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Scope: Seguimientos pendientes (con próximo seguimiento futuro)
     */
    public function scopePendientes($query)
    {
        return $query->whereNotNull('proximo_seguimiento')
                     ->where('proximo_seguimiento', '>=', now()->toDateString())
                     ->where('recordatorio_enviado', false);
    }

    /**
     * Scope: Filtrar por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}