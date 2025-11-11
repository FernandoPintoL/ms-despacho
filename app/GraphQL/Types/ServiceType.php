<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ServiceType extends GraphQLType
{
    protected $attributes = [
        'name' => '_Service',
        'description' => 'Apollo Federation Service type - for schema composition',
    ];

    public function fields(): array
    {
        return [
            'sdl' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Schema Definition Language (SDL) for this subgraph',
            ],
        ];
    }
}
