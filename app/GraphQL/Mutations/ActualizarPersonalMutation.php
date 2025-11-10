<?php

namespace App\GraphQL\Mutations;

use App\Models\Personal;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class ActualizarPersonalMutation extends Mutation
{
    protected $attributes = [
        'name' => 'actualizarPersonal',
        'description' => 'Actualizar datos de personal',
    ];

    public function type(): Type
    {
        return GraphQL::type('Personal');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID del personal',
            ],
            'nombre' => [
                'type' => Type::string(),
                'description' => 'Nombre del personal',
            ],
            'apellido' => [
                'type' => Type::string(),
                'description' => 'Apellido del personal',
            ],
            'especialidad' => [
                'type' => Type::string(),
                'description' => 'Especialidad médica',
            ],
            'experiencia' => [
                'type' => Type::int(),
                'description' => 'Años de experiencia',
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
        $personal = Personal::findOrFail($args['id']);

        // Actualizar solo los campos proporcionados
        $updateData = [];

        if (isset($args['nombre'])) {
            $updateData['nombre'] = $args['nombre'];
        }
        if (isset($args['apellido'])) {
            $updateData['apellido'] = $args['apellido'];
        }
        if (isset($args['especialidad'])) {
            $updateData['especialidad'] = $args['especialidad'];
        }
        if (isset($args['experiencia'])) {
            $updateData['experiencia'] = $args['experiencia'];
        }
        if (isset($args['telefono'])) {
            $updateData['telefono'] = $args['telefono'];
        }
        if (isset($args['email'])) {
            $updateData['email'] = $args['email'];
        }

        if (!empty($updateData)) {
            $personal->update($updateData);
        }

        return $personal;
    }
}
