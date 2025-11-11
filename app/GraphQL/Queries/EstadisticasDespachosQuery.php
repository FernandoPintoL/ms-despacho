<?php

namespace App\GraphQL\Queries;

use App\Models\Despacho;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class EstadisticasDespachosQuery extends Query
{
    protected $attributes = [
        'name' => 'estadisticasDespachos',
        'description' => 'Obtener estadísticas de despachos',
    ];

    public function type(): Type
    {
        return new ObjectType([
            'name' => 'EstadisticasDespachos',
            'fields' => [
                'total' => [
                    'type' => Type::int(),
                    'description' => 'Total de despachos',
                ],
                'completados' => [
                    'type' => Type::int(),
                    'description' => 'Despachos completados',
                ],
                'pendientes' => [
                    'type' => Type::int(),
                    'description' => 'Despachos pendientes',
                ],
                'en_camino' => [
                    'type' => Type::int(),
                    'description' => 'Despachos en camino',
                ],
                'en_sitio' => [
                    'type' => Type::int(),
                    'description' => 'Despachos en sitio',
                ],
                'cancelados' => [
                    'type' => Type::int(),
                    'description' => 'Despachos cancelados',
                ],
                'critica' => [
                    'type' => Type::int(),
                    'description' => 'Despachos críticos',
                ],
                'alta' => [
                    'type' => Type::int(),
                    'description' => 'Despachos alta prioridad',
                ],
                'media' => [
                    'type' => Type::int(),
                    'description' => 'Despachos media prioridad',
                ],
                'baja' => [
                    'type' => Type::int(),
                    'description' => 'Despachos baja prioridad',
                ],
                'tasa_completcion' => [
                    'type' => Type::float(),
                    'description' => 'Tasa de completación (%)',
                ],
            ],
        ]);
    }

    public function args(): array
    {
        return [
            'horas' => [
                'type' => Type::int(),
                'description' => 'Filtrar despachos de las últimas N horas',
                'defaultValue' => 24,
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $horas = $args['horas'] ?? 24;
        $fechaDesde = now()->subHours($horas);

        $query = Despacho::where('created_at', '>=', $fechaDesde);

        $total = $query->count();
        $completados = (clone $query)->where('estado', 'completado')->count();
        $pendientes = (clone $query)->where('estado', 'pendiente')->count();
        $en_camino = (clone $query)->where('estado', 'en_camino')->count();
        $en_sitio = (clone $query)->where('estado', 'en_sitio')->count();
        $cancelados = (clone $query)->where('estado', 'cancelado')->count();

        // Por prioridad
        $critica = (clone $query)->where('prioridad', 'critica')->count();
        $alta = (clone $query)->where('prioridad', 'alta')->count();
        $media = (clone $query)->where('prioridad', 'media')->count();
        $baja = (clone $query)->where('prioridad', 'baja')->count();

        $tasa_completcion = $total > 0 ? round(($completados / $total) * 100, 2) : 0;

        return [
            'total' => $total,
            'completados' => $completados,
            'pendientes' => $pendientes,
            'en_camino' => $en_camino,
            'en_sitio' => $en_sitio,
            'cancelados' => $cancelados,
            'critica' => $critica,
            'alta' => $alta,
            'media' => $media,
            'baja' => $baja,
            'tasa_completcion' => $tasa_completcion,
        ];
    }
}
