<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ambulancia>
 */
class AmbulanciaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placa' => strtoupper($this->faker->regexify('[A-Z]{3}[0-9]{3}')),
            'modelo' => $this->faker->randomElement(['Ford Transit', 'Mercedes Sprinter', 'Iveco Daily', 'Renault Master']),
            'tipo_ambulancia' => $this->faker->randomElement(['basica', 'avanzada', 'critica']),
            'estado' => $this->faker->randomElement(['disponible', 'en_servicio', 'mantenimiento', 'fuera_servicio']),
            'caracteristicas' => [
                'capacidad' => $this->faker->numberBetween(2, 6),
                'equipo' => ['desfibrilador', 'monitor', 'oxigeno'],
                'anio' => $this->faker->numberBetween(2015, 2023),
            ],
            'ubicacion_actual_lat' => $this->faker->latitude(),
            'ubicacion_actual_lng' => $this->faker->longitude(),
            'ultima_actualizacion' => now(),
        ];
    }
}
