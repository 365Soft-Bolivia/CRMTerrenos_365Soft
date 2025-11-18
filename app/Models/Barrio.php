<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barrio extends Model
{
    use HasFactory;

    protected $table = 'barrios';

    // Solo lectura - no permitir inserciones/actualizaciones desde el CRM
    protected $guarded = ['*'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Un barrio pertenece a un proyecto
     */
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idproyecto');
    }

    /**
     * Relación: Un barrio tiene muchas cuadras
     */
    public function cuadras()
    {
        return $this->hasMany(Cuadra::class, 'idbarrio');
    }

    /**
     * Scope: Filtrar por proyecto
     */
    public function scopeDelProyecto($query, $proyectoId)
    {
        return $query->where('idproyecto', $proyectoId);
    }
}