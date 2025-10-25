<?php

namespace App\GraphQL\Mutations;

use App\Models\Ambulancia;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class ActualizarUbicacionAmbulanciaMutation extends Mutation
{
    protected $attributes = [
        'name' => 'actualizarUbicacionAmbulancia',
        'description' => 'Actualizar la ubicación GPS de una ambulancia',
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
            'latitud' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Nueva latitud',
            ],
            'longitud' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Nueva longitud',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $ambulancia = Ambulancia::findOrFail($args['id']);
        
        $ambulancia->actualizarUbicacion($args['latitud'], $args['longitud']);

        return $ambulancia;
    }
}
