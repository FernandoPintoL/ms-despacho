<?php

namespace App\GraphQL\Types;

use App\Models\Ambulancia;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class AmbulanciaType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Ambulancia',
        'description' => 'Ambulancia del sistema - Apollo Federation Subgraph Entity',
        'model' => Ambulancia::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::id()),  // Changed from Type::int() for Federation
                'description' => 'ID de la ambulancia (Federation Key)',
            ],
            'placa' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Placa de la ambulancia',
            ],
            'modelo' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Modelo de la ambulancia',
            ],
            'tipoAmbulancia' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Tipo: basica, intermedia, avanzada, uci',
                'resolve' => function ($root) {
                    return $root->tipo_ambulancia;
                },
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
            'ubicacionActualLat' => [
                'type' => Type::float(),
                'description' => 'Latitud actual',
                'resolve' => function ($root) {
                    return $root->ubicacion_actual_lat;
                },
            ],
            'ubicacionActualLng' => [
                'type' => Type::float(),
                'description' => 'Longitud actual',
                'resolve' => function ($root) {
                    return $root->ubicacion_actual_lng;
                },
            ],
            'ultimaActualizacion' => [
                'type' => Type::string(),
                'description' => 'Última actualización de ubicación',
                'resolve' => function ($root) {
                    return $root->ultima_actualizacion ? $root->ultima_actualizacion->format('Y-m-d H:i:s') : null;
                },
            ],
            'createdAt' => [
                'type' => Type::string(),
                'description' => 'Fecha de creación',
                'resolve' => function ($root) {
                    return $root->created_at->format('Y-m-d H:i:s');
                },
            ],
            'updatedAt' => [
                'type' => Type::string(),
                'description' => 'Fecha de actualización',
                'resolve' => function ($root) {
                    return $root->updated_at->format('Y-m-d H:i:s');
                },
            ],
        ];
    }
}
