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
        'description' => 'Actualizar un personal',
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
            'rol' => [
                'type' => Type::string(),
                'description' => 'Rol: paramedico, conductor, medico, enfermero',
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
        
        // Registrar cambios
        $cambios = [];
        
        // Actualizar solo los campos proporcionados
        if (isset($args['nombre']) && $personal->nombre !== $args['nombre']) {
            $cambios['nombre'] = $args['nombre'];
        }
        if (isset($args['apellido']) && $personal->apellido !== $args['apellido']) {
            $cambios['apellido'] = $args['apellido'];
        }
        if (isset($args['rol']) && $personal->rol !== $args['rol']) {
            $cambios['rol'] = $args['rol'];
        }
        if (isset($args['especialidad']) && $personal->especialidad !== $args['especialidad']) {
            $cambios['especialidad'] = $args['especialidad'];
        }
        if (isset($args['experiencia']) && $personal->experiencia !== $args['experiencia']) {
            $cambios['experiencia'] = $args['experiencia'];
        }
        if (isset($args['telefono']) && $personal->telefono !== $args['telefono']) {
            $cambios['telefono'] = $args['telefono'];
        }
        if (isset($args['email']) && $personal->email !== $args['email']) {
            $cambios['email'] = $args['email'];
        }

        // Si hay cambios, actualizar y disparar evento
        if (!empty($cambios)) {
            $personal->update($cambios);
            $personal->dispatchUpdated($cambios);
        }

        return $personal;
    }
}
