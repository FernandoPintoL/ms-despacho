<?php

namespace App\GraphQL\Queries;

use App\Models\Despacho;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class DespachoQuery extends Query
{
    protected $attributes = [
        'name' => 'despacho',
        'description' => 'Obtener un despacho por ID',
    ];

    public function type(): Type
    {
        return GraphQL::type('Despacho');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),  // Changed from Type::int() for Federation compatibility
                'description' => 'ID del despacho',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        return Despacho::with(['ambulancia', 'personalAsignado'])
            ->findOrFail($args['id']);
    }
}
