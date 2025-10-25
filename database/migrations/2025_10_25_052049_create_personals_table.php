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
        Schema::create('personal', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('ci', 20)->unique();
            $table->enum('rol', ['paramedico', 'conductor', 'medico', 'enfermero'])->default('paramedico');
            $table->string('especialidad', 100)->nullable();
            $table->integer('experiencia')->default(0)->comment('Años de experiencia');
            $table->enum('estado', ['disponible', 'en_servicio', 'descanso', 'vacaciones'])->default('disponible');
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('estado');
            $table->index('rol');
            $table->index(['estado', 'rol']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal');
    }
};
