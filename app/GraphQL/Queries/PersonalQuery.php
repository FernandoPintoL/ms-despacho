<?php

namespace App\GraphQL\Queries;

use App\Models\Personal;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class PersonalQuery extends Query
{
    protected $attributes = [
        'name' => 'personal',
        'description' => 'Obtener un personal por ID',
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
        ];
    }

    public function resolve($root, array $args)
    {
        return Personal::findOrFail($args['id']);
    }
}
