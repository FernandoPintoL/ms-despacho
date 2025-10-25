<?php

namespace App\GraphQL\Mutations;

use App\Models\Despacho;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class ActualizarEstadoDespachoMutation extends Mutation
{
    protected $attributes = [
        'name' => 'actualizarEstadoDespacho',
        'description' => 'Actualizar el estado de un despacho',
    ];

    public function type(): Type
    {
        return GraphQL::type('Despacho');
    }

    public function args(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID del despacho',
            ],
            'estado' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Nuevo estado: pendiente, asignado, en_camino, en_sitio, trasladando, completado, cancelado',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $despacho = Despacho::findOrFail($args['id']);
        
        $despacho->cambiarEstado($args['estado']);

        // Actualizar fechas según el estado
        switch ($args['estado']) {
            case 'en_camino':
                if (!$despacho->fecha_asignacion) {
                    $despacho->fecha_asignacion = now();
                }
                break;
            case 'en_sitio':
                if (!$despacho->fecha_llegada) {
                    $despacho->fecha_llegada = now();
                }
                break;
            case 'completado':
            case 'cancelado':
                if (!$despacho->fecha_finalizacion) {
                    $despacho->fecha_finalizacion = now();
                }
                break;
        }

        $despacho->save();
        $despacho->load(['ambulancia', 'personalAsignado']);

        return $despacho;
    }
}
