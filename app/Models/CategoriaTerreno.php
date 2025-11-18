<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaTerreno extends Model
{
    use HasFactory;

    protected $table = 'categorias_terrenos';

    // Solo lectura - no permitir inserciones/actualizaciones desde el CRM
    protected $guarded = ['*'];

    protected $casts = [
        'estado' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Una categoría pertenece a un proyecto
     */
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idproyecto');
    }

    /**
     * Relación: Una categoría tiene muchos terrenos
     */
    public function terrenos()
    {
        return $this->hasMany(Terreno::class, 'idcategoria');
    }

    /**
     * Scope: Categorías activas
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', 1);
    }

    /**
     * Scope: Filtrar por proyecto
     */
    public function scopeDelProyecto($query, $proyectoId)
    {
        return $query->where('idproyecto', $proyectoId);
    }
}