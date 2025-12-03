<?php

namespace Database\Seeders;

use App\Models\Roles;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Roles::firstOrCreate(
            ['nombre' => 'asesor'],
            [
                'descripcion' => 'Asesor',
                'activo' => true,
            ]
        );
    }
}
