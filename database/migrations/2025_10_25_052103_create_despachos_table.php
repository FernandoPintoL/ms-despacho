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
        Schema::create('despachos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitud_id')->nullable()->comment('ID de MS Recepción');
            $table->foreignId('ambulancia_id')->constrained('ambulancias')->onDelete('no action');
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_asignacion')->nullable();
            $table->dateTime('fecha_llegada')->nullable();
            $table->dateTime('fecha_finalizacion')->nullable();
            
            // Ubicación origen (donde ocurrió el incidente)
            $table->decimal('ubicacion_origen_lat', 10, 8);
            $table->decimal('ubicacion_origen_lng', 11, 8);
            $table->string('direccion_origen', 255)->nullable();
            
            // Ubicación destino (hospital si aplica)
            $table->decimal('ubicacion_destino_lat', 10, 8)->nullable();
            $table->decimal('ubicacion_destino_lng', 11, 8)->nullable();
            $table->string('direccion_destino', 255)->nullable();
            
            // Métricas
            $table->decimal('distancia_km', 6, 2)->nullable();
            $table->integer('tiempo_estimado_min')->nullable();
            $table->integer('tiempo_real_min')->nullable();
            
            // Estado y clasificación
            $table->enum('estado', ['pendiente', 'asignado', 'en_camino', 'en_sitio', 'trasladando', 'completado', 'cancelado'])->default('pendiente');
            $table->enum('incidente', ['accidente', 'emergencia_medica', 'traslado', 'otro'])->default('emergencia_medica');
            $table->enum('decision', ['ambulatoria', 'traslado', 'pendiente'])->default('pendiente');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            
            // Observaciones
            $table->text('observaciones')->nullable();
            $table->json('datos_adicionales')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('estado');
            $table->index('fecha_solicitud');
            $table->index('ambulancia_id');
            $table->index(['estado', 'prioridad']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('despachos');
    }
};
