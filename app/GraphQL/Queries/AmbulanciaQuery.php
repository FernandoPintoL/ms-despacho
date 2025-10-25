<?php

namespace App\GraphQL\Queries;

use App\Models\Ambulancia;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class AmbulanciaQuery extends Query
{
    protected $attributes = [
        'name' => 'ambulancia',
        'description' => 'Obtener una ambulancia por ID',
    ];

    public function type(): Type
    {
        return GraphQL::type('Ambulancia');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID de la ambulancia',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        return Ambulancia::findOrFail($args['id']);
    }
}
