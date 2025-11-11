<?php

namespace App\GraphQL\Mutations;

use App\Models\Despacho;
use App\Models\HistorialRastreo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class RegistrarUbicacionGPSMutation extends Mutation
{
    protected $attributes = [
        'name' => 'registrarUbicacionGPS',
        'description' => 'Registrar ubicación GPS para un despacho',
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
            'ubicacion_lat' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Latitud actual',
            ],
            'ubicacion_lng' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Longitud actual',
            ],
            'velocidad' => [
                'type' => Type::float(),
                'description' => 'Velocidad actual en km/h',
            ],
            'altitud' => [
                'type' => Type::float(),
                'description' => 'Altitud en metros',
            ],
            'precision' => [
                'type' => Type::float(),
                'description' => 'Precisión del GPS en metros',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $despacho = Despacho::findOrFail($args['despacho_id']);

        // Registrar en historial de rastreo
        HistorialRastreo::create([
            'despacho_id' => $despacho->id,
            'ambulancia_id' => $despacho->ambulancia_id,
            'ubicacion_lat' => $args['ubicacion_lat'],
            'ubicacion_lng' => $args['ubicacion_lng'],
            'velocidad' => $args['velocidad'] ?? null,
            'altitud' => $args['altitud'] ?? null,
            'precision' => $args['precision'] ?? null,
        ]);

        // Actualizar ubicación de la ambulancia
        if ($despacho->ambulancia) {
            $despacho->ambulancia->update([
                'ubicacion_lat' => $args['ubicacion_lat'],
                'ubicacion_lng' => $args['ubicacion_lng'],
                'ultima_actualizacion' => now(),
            ]);
        }

        return $despacho->load(['ambulancia', 'personalAsignado']);
    }
}
