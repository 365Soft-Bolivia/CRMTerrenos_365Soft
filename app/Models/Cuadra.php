<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuadra extends Model
{
    use HasFactory;

    protected $table = 'cuadras';

    // Solo lectura - no permitir inserciones/actualizaciones desde el CRM
    protected $guarded = ['*'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Una cuadra pertenece a un barrio
     */
    public function barrio()
    {
        return $this->belongsTo(Barrio::class, 'idbarrio');
    }

    /**
     * Relación: Una cuadra tiene muchos terrenos
     */
    public function terrenos()
    {
        return $this->hasMany(Terreno::class, 'idcuadra');
    }

    /**
     * Scope: Filtrar por barrio
     */
    public function scopeDelBarrio($query, $barrioId)
    {
        return $query->where('idbarrio', $barrioId);
    }
}