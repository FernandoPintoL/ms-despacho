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
        Schema::create('asignacion_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->onDelete('cascade');
            $table->foreignId('personal_id')->constrained('personal')->onDelete('no action');
            $table->enum('rol_asignado', ['paramedico', 'conductor', 'medico', 'enfermero']);
            $table->boolean('es_responsable')->default(false)->comment('Indica si es el responsable del despacho');
            $table->timestamps();
            
            // Índices
            $table->index('despacho_id');
            $table->index('personal_id');
            $table->unique(['despacho_id', 'personal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignacion_personal');
    }
};
