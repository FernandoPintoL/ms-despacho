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
        Schema::create('ambulancias', function (Blueprint $table) {
            $table->id();
            $table->string('placa', 10)->unique();
            $table->string('modelo', 50);
            $table->enum('tipo_ambulancia', ['basica', 'intermedia', 'avanzada', 'uci'])->default('basica');
            $table->enum('estado', ['disponible', 'en_servicio', 'mantenimiento', 'fuera_servicio'])->default('disponible');
            $table->json('caracteristicas')->nullable();
            $table->decimal('ubicacion_actual_lat', 10, 8)->nullable();
            $table->decimal('ubicacion_actual_lng', 11, 8)->nullable();
            $table->timestamp('ultima_actualizacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('estado');
            $table->index('tipo_ambulancia');
            $table->index(['ubicacion_actual_lat', 'ubicacion_actual_lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambulancias');
    }
};
