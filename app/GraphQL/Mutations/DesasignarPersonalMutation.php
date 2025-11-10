<?php

namespace App\GraphQL\Mutations;

use App\Models\AsignacionPersonal;
use App\Models\Despacho;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class DesasignarPersonalMutation extends Mutation
{
    protected $attributes = [
        'name' => 'desasignarPersonal',
        'description' => 'Remover personal de un despacho',
    ];

    public function type(): Type
    {
        return GraphQL::type('Despacho');
    }

    public function args(): array
    {
        return [
            'despacho_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID del despacho',
            ],
            'personal_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID del personal',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $despacho = Despacho::findOrFail($args['despacho_id']);

        // Buscar y eliminar la asignación
        $asignacion = AsignacionPersonal::where('despacho_id', $args['despacho_id'])
            ->where('personal_id', $args['personal_id'])
            ->firstOrFail();

        $asignacion->delete();

        // Cargar relaciones y retornar despacho actualizado
        $despacho->load(['ambulancia', 'personalAsignado']);

        return $despacho;
    }
}
