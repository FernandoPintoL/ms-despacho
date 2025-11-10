<?php

namespace App\GraphQL\Mutations;

use App\Models\Personal;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class CrearPersonalMutation extends Mutation
{
    protected $attributes = [
        'name' => 'crearPersonal',
        'description' => 'Crear un nuevo personal',
    ];

    public function type(): Type
    {
        return GraphQL::type('Personal');
    }

    public function args(): array
    {
        return [
            'nombre' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Nombre del personal',
            ],
            'apellido' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Apellido del personal',
            ],
            'ci' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Cédula de identidad (única)',
            ],
            'rol' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Rol: paramedico, conductor, medico, enfermero',
            ],
            'especialidad' => [
                'type' => Type::string(),
                'description' => 'Especialidad médica (opcional)',
            ],
            'experiencia' => [
                'type' => Type::int(),
                'description' => 'Años de experiencia',
                'defaultValue' => 0,
            ],
            'telefono' => [
                'type' => Type::string(),
                'description' => 'Teléfono de contacto',
            ],
            'email' => [
                'type' => Type::string(),
                'description' => 'Correo electrónico',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        // Validar que CI sea único
        if (Personal::where('ci', $args['ci'])->exists()) {
            throw new \Exception('El CI ya existe en el sistema');
        }

        // Crear personal
        $personal = Personal::create([
            'nombre' => $args['nombre'],
            'apellido' => $args['apellido'],
            'ci' => $args['ci'],
            'rol' => $args['rol'],
            'especialidad' => $args['especialidad'] ?? null,
            'experiencia' => $args['experiencia'] ?? 0,
            'telefono' => $args['telefono'] ?? null,
            'email' => $args['email'] ?? null,
            'estado' => 'disponible',
        ]);

        // Disparar evento de creación
        $personal->dispatchCreated();

        return $personal;
    }
}
