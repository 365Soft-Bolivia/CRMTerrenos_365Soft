<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $table = 'leads';

    protected $fillable = [
        'nombre',
        'carnet',
        'numero_1',
        'numero_2',
        'direccion',
        'asesor_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Un lead tiene un negocio
     */
    public function negocio()
    {
        return $this->hasOne(Negocio::class);
    }

    /**
     * Relación: Un lead pertenece a un asesor (usuario)
     */
    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    /**
     * Scope: Filtrar por asesor
     */
    public function scopeDelAsesor($query, $asesorId)
    {
        return $query->where('asesor_id', $asesorId);
    }

    /**
     * Scope: Buscar por nombre o carnet
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where('nombre', 'like', "%{$termino}%")
                     ->orWhere('carnet', 'like', "%{$termino}%")
                     ->orWhere('numero_1', 'like', "%{$termino}%");
    }
}