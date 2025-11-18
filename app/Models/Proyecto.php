<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    use HasFactory;

    protected $table = 'proyectos';

    // Solo lectura - no permitir inserciones/actualizaciones desde el CRM
    protected $guarded = ['*'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Un proyecto tiene muchos terrenos
     */
    public function terrenos()
    {
        return $this->hasMany(Terreno::class, 'idproyecto');
    }

    /**
     * Relación: Un proyecto tiene muchos barrios
     */
    public function barrios()
    {
        return $this->hasMany(Barrio::class, 'idproyecto');
    }

    /**
     * Relación: Un proyecto tiene muchas categorías de terrenos
     */
    public function categorias()
    {
        return $this->hasMany(CategoriaTerreno::class, 'idproyecto');
    }
}