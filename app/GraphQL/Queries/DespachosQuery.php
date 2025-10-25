<?php

namespace App\GraphQL\Queries;

use App\Models\Despacho;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class DespachosQuery extends Query
{
    protected $attributes = [
        'name' => 'despachos',
        'description' => 'Listar despachos con filtros opcionales',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Despacho'));
    }

    public function args(): array
    {
        return [
            'estado' => [
                'type' => Type::string(),
                'description' => 'Filtrar por estado',
            ],
            'prioridad' => [
                'type' => Type::string(),
                'description' => 'Filtrar por prioridad',
            ],
            'activos' => [
                'type' => Type::boolean(),
                'description' => 'Solo despachos activos',
            ],
            'limit' => [
                'type' => Type::int(),
                'description' => 'Límite de resultados',
                'defaultValue' => 50,
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $query = Despacho::with(['ambulancia', 'personalAsignado']);

        if (isset($args['estado'])) {
            $query->where('estado', $args['estado']);
        }

        if (isset($args['prioridad'])) {
            $query->where('prioridad', $args['prioridad']);
        }

        if (isset($args['activos']) && $args['activos']) {
            $query->activos();
        }

        $query->orderBy('created_at', 'desc');

        if (isset($args['limit'])) {
            $query->limit($args['limit']);
        }

        return $query->get();
    }
}
