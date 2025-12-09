<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla de embudos
        Schema::create('embudos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255)->unique();
            $table->string('color', 7)->default('#6b7280'); // Color hexadecimal (#RRGGBB)
            $table->string('icono', 50)->nullable(); // Nombre del icono (ej: circle, check-circle)
            $table->integer('orden')->default(0); // Para ordenar los embudos
            $table->boolean('activo')->default(true); // Para activar/desactivar embudos
            $table->string('descripcion', 500)->nullable(); // Descripción opcional
            $table->timestamps();
        });

        // Agregar columna embudo_id a la tabla negocios
        Schema::table('negocios', function (Blueprint $table) {
            $table->foreignId('embudo_id')
                ->nullable()
                ->after('embudo')
                ->constrained('embudos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar la columna embudo_id de negocios
        Schema::table('negocios', function (Blueprint $table) {
            $table->dropForeign(['embudo_id']);
            $table->dropColumn('embudo_id');
        });

        // Eliminar tabla de embudos
        Schema::dropIfExists('embudos');
    }
};
