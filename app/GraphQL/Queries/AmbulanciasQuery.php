<?php

namespace App\GraphQL\Queries;

use App\Models\Ambulancia;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class AmbulanciasQuery extends Query
{
    protected $attributes = [
        'name' => 'ambulancias',
        'description' => 'Listar ambulancias con filtros opcionales',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Ambulancia'));
    }

    public function args(): array
    {
        return [
            'estado' => [
                'type' => Type::string(),
                'description' => 'Filtrar por estado',
            ],
            'tipo_ambulancia' => [
                'type' => Type::string(),
                'description' => 'Filtrar por tipo',
            ],
            'disponibles' => [
                'type' => Type::boolean(),
                'description' => 'Solo ambulancias disponibles',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $query = Ambulancia::query();

        if (isset($args['estado'])) {
            $query->where('estado', $args['estado']);
        }

        if (isset($args['tipo_ambulancia'])) {
            $query->where('tipo_ambulancia', $args['tipo_ambulancia']);
        }

        if (isset($args['disponibles']) && $args['disponibles']) {
            $query->disponibles();
        }

        return $query->get();
    }
}
