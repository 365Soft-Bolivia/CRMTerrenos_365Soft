<?php

namespace Database\Seeders;

use App\Models\Embudo;
use Illuminate\Database\Seeder;

class EmbudoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $embudos = [
            [
                'nombre' => 'Interés Generado',
                'color' => '#FBBF24', // amber-400
                'icono' => 'circle',
                'orden' => 1,
                'activo' => true,
                'descripcion' => 'Lead que ha mostrado interés inicial en un terreno',
            ],
            [
                'nombre' => 'Contacto Inicial',
                'color' => '#3B82F6', // blue-500
                'icono' => 'circle',
                'orden' => 2,
                'activo' => true,
                'descripcion' => 'Se ha realizado el primer contacto con el lead',
            ],
            [
                'nombre' => 'Visita Programada',
                'color' => '#8B5CF6', // violet-500
                'icono' => 'circle',
                'orden' => 3,
                'activo' => true,
                'descripcion' => 'Se ha programado una visita al terreno',
            ],
            [
                'nombre' => 'Propuesta / Oferta',
                'color' => '#06B6D4', // cyan-500
                'icono' => 'circle',
                'orden' => 4,
                'activo' => true,
                'descripcion' => 'Se ha enviado una propuesta formal al cliente',
            ],
            [
                'nombre' => 'Negociación',
                'color' => '#F97316', // orange-500
                'icono' => 'circle',
                'orden' => 5,
                'activo' => true,
                'descripcion' => 'En proceso de negociación de precio y condiciones',
            ],
            [
                'nombre' => 'Cierre / Venta Concretada',
                'color' => '#10B981', // emerald-500
                'icono' => 'check-circle',
                'orden' => 6,
                'activo' => true,
                'descripcion' => 'La venta se ha concretado exitosamente',
            ],
            [
                'nombre' => 'Perdido / No Concretado',
                'color' => '#EF4444', // red-500
                'icono' => 'x-circle',
                'orden' => 7,
                'activo' => true,
                'descripcion' => 'El negocio no se concretó',
            ],
        ];

        foreach ($embudos as $embudo) {
            Embudo::updateOrCreate(
                ['nombre' => $embudo['nombre']],
                $embudo
            );
        }
    }
}
