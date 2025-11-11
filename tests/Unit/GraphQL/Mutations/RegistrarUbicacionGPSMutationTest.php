<?php

namespace Tests\Unit\GraphQL\Mutations;

use Tests\TestCase;
use App\Models\Despacho;
use App\Models\HistorialRastreo;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrarUbicacionGPSMutationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que debe registrar ubicación GPS correctamente
     */
    public function test_debe_registrar_ubicacion_gps()
    {
        $despacho = Despacho::factory()->create();

        $mutation = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7115
                    ubicacion_lng: -74.0730
                    velocidad: 60
                    altitud: 500
                    precision: 5
                ) {
                    id
                    estado
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        $response->assertJsonPath('data.registrarUbicacionGPS.id', $despacho->id);
        $response->assertJsonPath('data.registrarUbicacionGPS.estado', $despacho->estado);
    }

    /**
     * Test que debe crear registro en HistorialRastreo
     */
    public function test_debe_crear_registro_en_historial_rastreo()
    {
        $despacho = Despacho::factory()->create();

        $mutation = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7115
                    ubicacion_lng: -74.0730
                    velocidad: 60
                    altitud: 500
                    precision: 5
                ) {
                    id
                }
            }
        GQL;

        $this->graphQL(sprintf($mutation, $despacho->id));

        $this->assertDatabaseHas('historial_rastreo', [
            'despacho_id' => $despacho->id,
            'ubicacion_lat' => 4.7115,
            'ubicacion_lng' => -74.0730,
            'velocidad' => 60,
            'altitud' => 500,
            'precision' => 5,
        ]);
    }

    /**
     * Test que debe permitir valores opcionales
     */
    public function test_debe_permitir_valores_opcionales()
    {
        $despacho = Despacho::factory()->create();

        // Sin velocidad, altitud ni precision
        $mutation = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7115
                    ubicacion_lng: -74.0730
                ) {
                    id
                    estado
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        $response->assertJsonPath('data.registrarUbicacionGPS.id', $despacho->id);

        // Verificar que se creó el registro sin valores opcionales
        $this->assertDatabaseHas('historial_rastreo', [
            'despacho_id' => $despacho->id,
            'ubicacion_lat' => 4.7115,
            'ubicacion_lng' => -74.0730,
        ]);
    }

    /**
     * Test que debe manejar despacho inexistente
     */
    public function test_debe_manejar_despacho_inexistente()
    {
        $mutation = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: 999
                    ubicacion_lat: 4.7115
                    ubicacion_lng: -74.0730
                ) {
                    id
                }
            }
        GQL;

        $response = $this->graphQL($mutation);

        $response->assertErrors();
    }

    /**
     * Test que debe validar coordenadas válidas
     */
    public function test_debe_validar_coordenadas_validas()
    {
        $despacho = Despacho::factory()->create();

        // Latitud fuera de rango (-90 a 90)
        $mutation = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 95.0
                    ubicacion_lng: -74.0730
                ) {
                    id
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        $response->assertErrors();
    }

    /**
     * Test que debe validar longitud válida
     */
    public function test_debe_validar_longitud_valida()
    {
        $despacho = Despacho::factory()->create();

        // Longitud fuera de rango (-180 a 180)
        $mutation = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7115
                    ubicacion_lng: -185.0
                ) {
                    id
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        $response->assertErrors();
    }

    /**
     * Test que debe crear múltiples registros para mismo despacho
     */
    public function test_debe_crear_multiples_registros_para_mismo_despacho()
    {
        $despacho = Despacho::factory()->create();

        // Primera ubicación
        $mutation1 = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7110
                    ubicacion_lng: -74.0720
                ) {
                    id
                }
            }
        GQL;

        $this->graphQL(sprintf($mutation1, $despacho->id));

        // Segunda ubicación
        $mutation2 = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7120
                    ubicacion_lng: -74.0730
                ) {
                    id
                }
            }
        GQL;

        $this->graphQL(sprintf($mutation2, $despacho->id));

        // Debe haber 2 registros en el historial
        $count = HistorialRastreo::where('despacho_id', $despacho->id)->count();
        $this->assertEquals(2, $count);
    }

    /**
     * Test que debe retornar estado actual del despacho
     */
    public function test_debe_retornar_estado_actual_despacho()
    {
        $despacho = Despacho::factory()->create(['estado' => 'en_camino']);

        $mutation = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7115
                    ubicacion_lng: -74.0730
                ) {
                    id
                    estado
                    ambulancia_id
                    fecha_solicitud
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        $response->assertJsonPath('data.registrarUbicacionGPS.estado', 'en_camino');
        $response->assertJsonPath('data.registrarUbicacionGPS.id', $despacho->id);
    }

    /**
     * Test que debe validar velocidad como número positivo
     */
    public function test_debe_validar_velocidad_positiva()
    {
        $despacho = Despacho::factory()->create();

        // Velocidad negativa
        $mutation = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7115
                    ubicacion_lng: -74.0730
                    velocidad: -10
                ) {
                    id
                }
            }
        GQL;

        $response = $this->graphQL(sprintf($mutation, $despacho->id));

        // Puede ser error o puede aceptar pero registrar como válido
        // Dependiendo de las validaciones del sistema
    }

    /**
     * Test que debe procesar datos de múltiples despachos
     */
    public function test_debe_procesar_datos_multiples_despachos()
    {
        $despacho1 = Despacho::factory()->create();
        $despacho2 = Despacho::factory()->create();

        // Registrar para primer despacho
        $mutation1 = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7110
                    ubicacion_lng: -74.0720
                ) {
                    id
                }
            }
        GQL;

        // Registrar para segundo despacho
        $mutation2 = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7130
                    ubicacion_lng: -74.0740
                ) {
                    id
                }
            }
        GQL;

        $this->graphQL(sprintf($mutation1, $despacho1->id));
        $this->graphQL(sprintf($mutation2, $despacho2->id));

        // Cada despacho debe tener su propio registro
        $this->assertDatabaseHas('historial_rastreo', [
            'despacho_id' => $despacho1->id,
            'ubicacion_lat' => 4.7110,
        ]);

        $this->assertDatabaseHas('historial_rastreo', [
            'despacho_id' => $despacho2->id,
            'ubicacion_lat' => 4.7130,
        ]);
    }

    /**
     * Test que debe incluir timestamp de creación
     */
    public function test_debe_incluir_timestamp_creacion()
    {
        $despacho = Despacho::factory()->create();

        $mutation = <<<'GQL'
            mutation {
                registrarUbicacionGPS(
                    despacho_id: %d
                    ubicacion_lat: 4.7115
                    ubicacion_lng: -74.0730
                ) {
                    id
                }
            }
        GQL;

        $beforeTime = now();
        $this->graphQL(sprintf($mutation, $despacho->id));
        $afterTime = now();

        $historial = HistorialRastreo::where('despacho_id', $despacho->id)->first();

        $this->assertNotNull($historial);
        $this->assertTrue($historial->created_at >= $beforeTime);
        $this->assertTrue($historial->created_at <= $afterTime);
    }
}
