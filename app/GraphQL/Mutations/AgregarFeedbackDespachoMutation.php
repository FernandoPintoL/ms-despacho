<?php

namespace App\GraphQL\Mutations;

use App\Models\Despacho;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class AgregarFeedbackDespachoMutation extends Mutation
{
    protected $attributes = [
        'name' => 'agregarFeedbackDespacho',
        'description' => 'Agregar feedback/evaluación a un despacho completado',
    ];

    public function type(): Type
    {
        return new ObjectType([
            'name' => 'FeedbackResponse',
            'fields' => [
                'despacho_id' => [
                    'type' => Type::int(),
                ],
                'calificacion' => [
                    'type' => Type::int(),
                ],
                'comentario' => [
                    'type' => Type::string(),
                ],
                'resultado_paciente' => [
                    'type' => Type::string(),
                ],
                'registrado_at' => [
                    'type' => Type::string(),
                ],
            ],
        ]);
    }

    public function args(): array
    {
        return [
            'despacho_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID del despacho',
            ],
            'calificacion' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Calificación (1-5)',
            ],
            'comentario' => [
                'type' => Type::string(),
                'description' => 'Comentario/feedback',
            ],
            'resultado_paciente' => [
                'type' => Type::string(),
                'description' => 'Resultado del paciente (estable, critico, fallecido, etc)',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        $despacho = Despacho::findOrFail($args['despacho_id']);

        // Actualizar datos_adicionales con feedback
        $datos = $despacho->datos_adicionales ?? [];
        $datos['feedback'] = [
            'calificacion' => $args['calificacion'],
            'comentario' => $args['comentario'] ?? null,
            'resultado_paciente' => $args['resultado_paciente'] ?? null,
            'registrado_at' => now()->toIso8601String(),
        ];

        $despacho->update(['datos_adicionales' => $datos]);

        return [
            'despacho_id' => $despacho->id,
            'calificacion' => $args['calificacion'],
            'comentario' => $args['comentario'],
            'resultado_paciente' => $args['resultado_paciente'],
            'registrado_at' => now()->toIso8601String(),
        ];
    }
}
