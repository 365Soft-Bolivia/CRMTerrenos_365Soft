<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Roles;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Seed de roles básicos
        $this->call([
            RolesSeeder::class,
            EmbudoSeeder::class,
        ]);

        // Usuario de prueba con rol Administrador
        $adminRoleId = Roles::where('nombre', 'administrador')->value('id');

        
    }
}
