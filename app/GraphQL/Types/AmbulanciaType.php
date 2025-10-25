<?php

namespace App\GraphQL\Types;

use App\Models\Ambulancia;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class AmbulanciaType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Ambulancia',
        'description' => 'Ambulancia del sistema',
        'model' => Ambulancia::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID de la ambulancia',
            ],
            'placa' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Placa de la ambulancia',
            ],
            'modelo' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Modelo de la ambulancia',
            ],
            'tipo_ambulancia' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Tipo: basica, intermedia, avanzada, uci',
            ],
            'estado' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Estado: disponible, en_servicio, mantenimiento, fuera_servicio',
            ],
            'caracteristicas' => [
                'type' => Type::string(),
                'description' => 'Características en formato JSON',
                'resolve' => function ($root) {
                    return $root->caracteristicas ? json_encode($root->caracteristicas) : null;
                },
            ],
            'ubicacion_actual_lat' => [
                'type' => Type::float(),
                'description' => 'Latitud actual',
            ],
            'ubicacion_actual_lng' => [
                'type' => Type::float(),
                'description' => 'Longitud actual',
            ],
            'ultima_actualizacion' => [
                'type' => Type::string(),
                'description' => 'Última actualización de ubicación',
                'resolve' => function ($root) {
                    return $root->ultima_actualizacion?->toIso8601String();
                },
            ],
            'created_at' => [
                'type' => Type::string(),
                'description' => 'Fecha de creación',
                'resolve' => function ($root) {
                    return $root->created_at->toIso8601String();
                },
            ],
            'updated_at' => [
                'type' => Type::string(),
                'description' => 'Fecha de actualización',
                'resolve' => function ($root) {
                    return $root->updated_at->toIso8601String();
                },
            ],
        ];
    }
}
