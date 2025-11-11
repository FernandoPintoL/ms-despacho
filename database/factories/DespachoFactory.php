<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Despacho>
 */
class DespachoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $now = now();
        return [
            'solicitud_id' => $this->faker->numberBetween(1, 100),
            'ambulancia_id' => $this->faker->numberBetween(1, 20),
            'fecha_solicitud' => $now->subHours($this->faker->numberBetween(1, 168)),
            'fecha_asignacion' => $now->subHours($this->faker->numberBetween(0, 12)),
            'fecha_llegada' => null,
            'fecha_finalizacion' => null,
            'ubicacion_origen_lat' => $this->faker->latitude(),
            'ubicacion_origen_lng' => $this->faker->longitude(),
            'direccion_origen' => $this->faker->address(),
            'ubicacion_destino_lat' => $this->faker->latitude(),
            'ubicacion_destino_lng' => $this->faker->longitude(),
            'direccion_destino' => $this->faker->address(),
            'distancia_km' => $this->faker->randomFloat(2, 0.5, 50),
            'tiempo_estimado_min' => $this->faker->numberBetween(5, 120),
            'tiempo_real_min' => null,
            'estado' => $this->faker->randomElement(['pendiente', 'asignado', 'en_camino', 'en_sitio', 'completado', 'cancelado']),
            'incidente' => $this->faker->randomElement(['emergencia_medica', 'accidente', 'traslado', 'otro']),
            'decision' => $this->faker->randomElement(['aceptado', 'rechazado', 'derivado']),
            'prioridad' => $this->faker->randomElement(['baja', 'media', 'alta', 'critica']),
            'observaciones' => $this->faker->optional()->sentence(),
            'datos_adicionales' => json_encode([]),
        ];
    }
}
