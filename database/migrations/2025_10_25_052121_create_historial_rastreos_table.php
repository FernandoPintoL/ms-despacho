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
        Schema::create('historial_rastreo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->onDelete('cascade');
            $table->decimal('latitud', 10, 8);
            $table->decimal('longitud', 11, 8);
            $table->decimal('velocidad', 5, 2)->nullable()->comment('km/h');
            $table->decimal('altitud', 8, 2)->nullable()->comment('metros');
            $table->decimal('precision', 6, 2)->nullable()->comment('metros');
            $table->timestamp('timestamp_gps');
            $table->timestamps();
            
            // Índices
            $table->index('despacho_id');
            $table->index('timestamp_gps');
            $table->index(['despacho_id', 'timestamp_gps']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_rastreo');
    }
};
