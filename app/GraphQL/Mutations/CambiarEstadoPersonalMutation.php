<?php

namespace App\GraphQL\Mutations;

use App\Models\Personal;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class CambiarEstadoPersonalMutation extends Mutation
{
    protected $attributes = [
        'name' => 'cambiarEstadoPersonal',
        'description' => 'Cambiar el estado de un personal',
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
            'estado' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Nuevo estado: disponible, en_servicio, descanso, vacaciones',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $personal = Personal::findOrFail($args['id']);

        $estadosValidos = ['disponible', 'en_servicio', 'descanso', 'vacaciones'];
        if (!in_array($args['estado'], $estadosValidos)) {
            throw new \Exception("Estado inválido. Estados válidos: " . implode(', ', $estadosValidos));
        }

        $personal->update(['estado' => $args['estado']]);

        return $personal;
    }
}
