<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Embudo extends Model
{
    use HasFactory;

    protected $table = 'embudos';

    protected $fillable = [
        'nombre',
        'color',
        'icono',
        'orden',
        'activo',
        'descripcion',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Relación con los negocios que pertenecen a este embudo/etapa
     */
    public function negocios(): HasMany
    {
        return $this->hasMany(Negocio::class, 'embudo_id');
    }

    /**
     * Scope para obtener solo embudos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para ordenar por el campo 'orden'
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden', 'asc');
    }

    /**
     * Obtener el color en formato CSS (ya viene en hexadecimal)
     */
    public function getColorCssAttribute(): string
    {
        return $this->color ?? '#6b7280';
    }
}
