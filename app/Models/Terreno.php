<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Terreno extends Model
{
    use HasFactory;

    protected $table = 'terrenos';

    // Solo lectura - no permitir inserciones/actualizaciones desde el CRM
    protected $guarded = ['*'];
    protected $hidden = ['poligono'];

    protected $casts = [
        'cuota_inicial' => 'decimal:2',
        'cuota_mensual' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'estado' => 'integer',
        'condicion' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Un terreno pertenece a un proyecto
     */
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'idproyecto');
    }

    /**
     * Relación: Un terreno pertenece a una cuadra
     */
    public function cuadra()
    {
        return $this->belongsTo(Cuadra::class, 'idcuadra');
    }

    /**
     * Relación: Un terreno pertenece a una categoría
     */
    public function categoria()
    {
        return $this->belongsTo(CategoriaTerreno::class, 'idcategoria');
    }

    /**
     * Relación: Un terreno tiene muchos negocios (desde el CRM)
     */
    public function negocios()
    {
        return $this->hasMany(Negocio::class);
    }

    /**
     * Relación: Un terreno tiene muchos documentos
     */
    public function documentos()
    {
        return $this->hasMany(DocumentoTerreno::class, 'idterreno');
    }

    /**
     * Scope: Terrenos disponibles (estado = 0 significa disponible según tu estructura)
     */
    public function scopeDisponibles($query)
    {
        return $query->where('estado', 0);
    }

    /**
     * Scope: Filtrar por proyecto
     */
    public function scopeDelProyecto($query, $proyectoId)
    {
        return $query->where('idproyecto', $proyectoId);
    }

    /**
     * Scope: Filtrar por categoría
     */
    public function scopeDeCategoria($query, $categoriaId)
    {
        return $query->where('idcategoria', $categoriaId);
    }

    /**
     * Scope: Filtrar por cuadra
     */
    public function scopeDeCuadra($query, $cuadraId)
    {
        return $query->where('idcuadra', $cuadraId);
    }
}