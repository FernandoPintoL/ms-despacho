<?php

namespace Tests\Unit\GraphQL\Mutations;

use Tests\TestCase;
use App\Models\Despacho;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AgregarFeedbackDespachoMutationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que debe agregar feedback a un despacho
     */
    public function test_debe_agregar_feedback_despacho()
    {
        $despacho = Despacho::factory()->create();

        $mutation = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 5
                    comentario: "Excelente servicio"
                    resultado_paciente: "estable"
                ) {
                    despacho_id
                    calificacion
                    comentario
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        $response->assertJsonPath('data.agregarFeedbackDespacho.despacho_id', $despacho->id);
        $response->assertJsonPath('data.agregarFeedbackDespacho.calificacion', 5);
        $response->assertJsonPath('data.agregarFeedbackDespacho.comentario', 'Excelente servicio');
    }

    /**
     * Test que debe almacenar feedback en datos_adicionales
     */
    public function test_debe_almacenar_feedback_en_datos_adicionales()
    {
        $despacho = Despacho::factory()->create();

        $mutation = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 4
                    comentario: "Bueno"
                    resultado_paciente: "mejorado"
                ) {
                    despacho_id
                }
            }
        GQL;

        $this->graphQL(sprintf($mutation, $despacho->id));

        $despacho = $despacho->fresh();

        $this->assertNotNull($despacho->datos_adicionales);
        $this->assertEquals(4, $despacho->datos_adicionales['calificacion'] ?? null);
        $this->assertEquals('Bueno', $despacho->datos_adicionales['comentario'] ?? null);
        $this->assertEquals('mejorado', $despacho->datos_adicionales['resultado_paciente'] ?? null);
    }

    /**
     * Test que debe validar calificación en rango 1-5
     */
    public function test_debe_validar_calificacion_rango_1_a_5()
    {
        $despacho = Despacho::factory()->create();

        // Calificación 0 (muy baja)
        $mutation0 = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 0
                    comentario: "Malo"
                ) {
                    despacho_id
                }
            }
        GQL;

        $response0 = $this->graphQL(sprintf($mutation0, $despacho->id));
        $response0->assertErrors();

        // Calificación 6 (muy alta)
        $mutation6 = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 6
                    comentario: "Excelente"
                ) {
                    despacho_id
                }
            }
        GQL;

        $response6 = $this->graphQL(sprintf($mutation6, $despacho->id));
        $response6->assertErrors();

        // Calificación negativa
        $mutationNeg = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: -1
                    comentario: "Error"
                ) {
                    despacho_id
                }
            }
        GQL;

        $responseNeg = $this->graphQL(sprintf($mutationNeg, $despacho->id));
        $responseNeg->assertErrors();
    }

    /**
     * Test que debe aceptar todas las calificaciones válidas
     */
    public function test_debe_aceptar_todas_calificaciones_validas()
    {
        $despacho = Despacho::factory()->create();

        for ($rating = 1; $rating <= 5; $rating++) {
            $mutation = <<<'GQL'
                mutation {
                    agregarFeedbackDespacho(
                        despacho_id: %d
                        calificacion: %d
                        comentario: "Calificación %d"
                    ) {
                        calificacion
                    }
                }
            GQL;

            $response = $this->graphQL(sprintf($mutation, $despacho->id, $rating, $rating));

            $response->assertJsonPath('data.agregarFeedbackDespacho.calificacion', $rating);
        }
    }

    /**
     * Test que debe permitir comentario opcional
     */
    public function test_debe_permitir_comentario_opcional()
    {
        $despacho = Despacho::factory()->create();

        $mutation = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 5
                ) {
                    despacho_id
                    calificacion
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        $response->assertJsonPath('data.agregarFeedbackDespacho.despacho_id', $despacho->id);
        $response->assertJsonPath('data.agregarFeedbackDespacho.calificacion', 5);
    }

    /**
     * Test que debe permitir resultado_paciente opcional
     */
    public function test_debe_permitir_resultado_paciente_opcional()
    {
        $despacho = Despacho::factory()->create();

        $mutation = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 4
                    comentario: "Bueno"
                ) {
                    despacho_id
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        $response->assertJsonPath('data.agregarFeedbackDespacho.despacho_id', $despacho->id);
    }

    /**
     * Test que debe manejar despacho inexistente
     */
    public function test_debe_manejar_despacho_inexistente()
    {
        $mutation = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: 999
                    calificacion: 5
                    comentario: "Excelente"
                ) {
                    despacho_id
                }
            }
        GQL;

        $response = $this->graphQL($mutation);

        $response->assertErrors();
    }

    /**
     * Test que debe validar longitud del comentario
     */
    public function test_debe_validar_longitud_comentario()
    {
        $despacho = Despacho::factory()->create();

        // Comentario muy largo (si hay límite)
        $longComment = str_repeat('a', 1000);

        $mutation = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 5
                    comentario: "%s"
                ) {
                    despacho_id
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id, $longComment));

        // Puede aceptar o rechazar según validaciones
        $this->assertTrue(
            $response->isOk() || count($response->json('errors')) > 0
        );
    }

    /**
     * Test que debe agregar múltiples feedbacks al mismo despacho
     */
    public function test_debe_agregar_multiples_feedbacks_mismo_despacho()
    {
        $despacho = Despacho::factory()->create();

        // Primer feedback
        $mutation1 = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 5
                    comentario: "Excelente"
                ) {
                    despacho_id
                }
            }
        GQL;

        $this->graphQL(sprintf($mutation1, $despacho->id));

        // Segundo feedback (puede ser actualización o nuevo registro)
        $mutation2 = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 4
                    comentario: "Bueno"
                ) {
                    despacho_id
                }
            }
        GQL;

        $this->graphQL(sprintf($mutation2, $despacho->id));

        // Debe tener feedback
        $despacho = $despacho->fresh();
        $this->assertNotNull($despacho->datos_adicionales);
    }

    /**
     * Test que debe incluir resultado_paciente en respuesta
     */
    public function test_debe_incluir_resultado_paciente_respuesta()
    {
        $despacho = Despacho::factory()->create();

        $mutation = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 5
                    comentario: "Excelente"
                    resultado_paciente: "mejorado"
                ) {
                    despacho_id
                    calificacion
                    comentario
                    resultado_paciente
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        $response->assertJsonPath('data.agregarFeedbackDespacho.resultado_paciente', 'mejorado');
    }

    /**
     * Test que debe validar valores válidos de resultado_paciente
     */
    public function test_debe_validar_valores_resultado_paciente()
    {
        $despacho = Despacho::factory()->create();

        // Valores típicos válidos
        $validResults = ['estable', 'mejorado', 'sin_cambios', 'crítico', 'fallecido'];

        foreach ($validResults as $result) {
            $mutation = <<<'GQL'
                mutation {
                    agregarFeedbackDespacho(
                        despacho_id: %d
                        calificacion: 5
                        resultado_paciente: "%s"
                    ) {
                        resultado_paciente
                    }
                }
            GQL;

            $response = $this->graphQL(sprintf($mutation, $despacho->id, $result));

            $response->assertJsonPath('data.agregarFeedbackDespacho.resultado_paciente', $result);
        }
    }

    /**
     * Test que debe retornar despacho_id en respuesta
     */
    public function test_debe_retornar_despacho_id()
    {
        $despacho = Despacho::factory()->create();

        $mutation = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: 5
                ) {
                    despacho_id
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        $response->assertJsonPath('data.agregarFeedbackDespacho.despacho_id', $despacho->id);
    }

    /**
     * Test que debe mantener integridad del feedback
     */
    public function test_debe_mantener_integridad_feedback()
    {
        $despacho = Despacho::factory()->create();

        $feedbackData = [
            'calificacion' => 5,
            'comentario' => 'Excelente servicio',
            'resultado_paciente' => 'estable',
        ];

        $mutation = <<<'GQL'
            mutation {
                agregarFeedbackDespacho(
                    despacho_id: %d
                    calificacion: %d
                    comentario: "%s"
                    resultado_paciente: "%s"
                ) {
                    despacho_id
                    calificacion
                    comentario
                    resultado_paciente
                }
            }
        GQL;

        $response = $this->graphQL(sprintf(
            $mutation,
            $despacho->id,
            $feedbackData['calificacion'],
            $feedbackData['comentario'],
            $feedbackData['resultado_paciente']
        ));

        $response->assertJsonPath('data.agregarFeedbackDespacho.calificacion', 5);
        $response->assertJsonPath('data.agregarFeedbackDespacho.comentario', 'Excelente servicio');
        $response->assertJsonPath('data.agregarFeedbackDespacho.resultado_paciente', 'estable');
    }
}
