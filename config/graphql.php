<?php

declare(strict_types=1);

return [
    // GraphQL endpoint
    'route' => [
        'prefix' => 'graphql',
        'middleware' => ['api'],
    ],

    // Default schema
    'default_schema' => 'default',

    // Schemas
    'schemas' => [
        'default' => [
            'query' => [
                '_service' => \App\GraphQL\Queries\ServiceQuery::class,
                'ambulancia' => \App\GraphQL\Queries\AmbulanciaQuery::class,
                'ambulancias' => \App\GraphQL\Queries\AmbulanciasQuery::class,
                'despacho' => \App\GraphQL\Queries\DespachoQuery::class,
                'despachos' => \App\GraphQL\Queries\DespachosQuery::class,
                'despachosRecientes' => \App\GraphQL\Queries\DespachosRecientesQuery::class,
                'estadisticasDespachos' => \App\GraphQL\Queries\EstadisticasDespachosQuery::class,
                'personal' => \App\GraphQL\Queries\PersonalQuery::class,
                'personales' => \App\GraphQL\Queries\PersonalesQuery::class,
            ],
            'mutation' => [
                'crearDespacho' => \App\GraphQL\Mutations\CrearDespachoMutation::class,
                'actualizarEstadoDespacho' => \App\GraphQL\Mutations\ActualizarEstadoDespachoMutation::class,
                'actualizarUbicacionAmbulancia' => \App\GraphQL\Mutations\ActualizarUbicacionAmbulanciaMutation::class,
                'registrarUbicacionGPS' => \App\GraphQL\Mutations\RegistrarUbicacionGPSMutation::class,
                'agregarFeedbackDespacho' => \App\GraphQL\Mutations\AgregarFeedbackDespachoMutation::class,
                'optimizarDespacho' => \App\GraphQL\Mutations\OptimizarDespachoMutation::class,
                'crearPersonal' => \App\GraphQL\Mutations\CrearPersonalMutation::class,
                'actualizarPersonal' => \App\GraphQL\Mutations\ActualizarPersonalMutation::class,
                'cambiarEstadoPersonal' => \App\GraphQL\Mutations\CambiarEstadoPersonalMutation::class,
                'asignarPersonal' => \App\GraphQL\Mutations\AsignarPersonalMutation::class,
                'desasignarPersonal' => \App\GraphQL\Mutations\DesasignarPersonalMutation::class,
            ],
            'types' => [],
            'middleware' => [],
            'method' => ['get', 'post'],
        ],
    ],

    // Types
    'types' => [
        '_Service' => \App\GraphQL\Types\ServiceType::class,
        'Ambulancia' => \App\GraphQL\Types\AmbulanciaType::class,
        'Personal' => \App\GraphQL\Types\PersonalType::class,
        'Despacho' => \App\GraphQL\Types\DespachoType::class,
    ],

    // Error handling
    'error_formatter' => [Rebing\GraphQL\GraphQL::class, 'formatError'],

    // Pagination
    'pagination_type' => \Rebing\GraphQL\Support\PaginationType::class,

    // Security
    'security' => [
        'query_max_complexity' => null,
        'query_max_depth' => null,
        'disable_introspection' => false,
    ],

    // GraphiQL
    'graphiql' => [
        'prefix' => '/graphiql',
        'middleware' => [],
        'view' => 'graphql::graphiql',
        'display' => env('ENABLE_GRAPHIQL', true),
    ],
];
