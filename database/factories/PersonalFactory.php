<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Personal>
 */
class PersonalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'ci' => strtoupper($this->faker->regexify('[A-Z][0-9]{7}')),
            'rol' => $this->faker->randomElement(['paramedico', 'enfermero', 'conductor', 'coordinador']),
            'especialidad' => $this->faker->randomElement(['emt_basico', 'emt_intermedio', 'emt_paramedico', 'enfermeria', 'general']),
            'experiencia' => $this->faker->numberBetween(1, 30),
            'estado' => $this->faker->randomElement(['activo', 'inactivo', 'licencia', 'jubilado']),
            'telefono' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
