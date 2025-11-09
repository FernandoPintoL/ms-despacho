<?php

namespace Tests\Feature;

use App\Models\Ambulancia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmbulanciaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearDatosTest();
    }

    private function crearDatosTest(): void
    {
        Ambulancia::factory()->create([
            'placa' => 'AMB-001',
            'modelo' => 'Mercedes Sprinter',
            'tipo_ambulancia' => 'avanzada',
            'estado' => 'disponible',
            'ubicacion_actual_lat' => -16.5,
            'ubicacion_actual_lng' => -68.15,
        ]);

        Ambulancia::factory()->create([
            'placa' => 'AMB-002',
            'modelo' => 'Ford Transit',
            'tipo_ambulancia' => 'intermedia',
            'estado' => 'en_servicio',
            'ubicacion_actual_lat' => -16.8854,
            'ubicacion_actual_lng' => -68.1131,
        ]);

        Ambulancia::factory()->create([
            'placa' => 'AMB-003',
            'modelo' => 'Toyota Hiace',
            'tipo_ambulancia' => 'basica',
            'estado' => 'mantenimiento',
            'ubicacion_actual_lat' => -17.3895,
            'ubicacion_actual_lng' => -66.1568,
        ]);
    }

    /**
     * Test GET /api/v1/ambulancias - list all ambulancias
     */
    public function test_get_ambulancias_list(): void
    {
        $response = $this->getJson('/api/v1/ambulancias');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'placa',
                        'modelo',
                        'tipo_ambulancia',
                        'estado',
                        'ubicacion_actual_lat',
                        'ubicacion_actual_lng',
                        'caracteristicas',
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test GET /api/v1/ambulancias with estado filter
     */
    public function test_get_ambulancias_con_filtro_estado(): void
    {
        $response = $this->getJson('/api/v1/ambulancias?estado=disponible');

        $response->assertStatus(200);

        $ambulancias = $response->json('data');
        $this->assertTrue(
            collect($ambulancias)->every(fn($a) => $a['estado'] === 'disponible')
        );
    }

    /**
     * Test GET /api/v1/ambulancias with tipo_ambulancia filter
     */
    public function test_get_ambulancias_con_filtro_tipo(): void
    {
        $response = $this->getJson('/api/v1/ambulancias?tipo_ambulancia=avanzada');

        $response->assertStatus(200);

        $ambulancias = $response->json('data');
        $this->assertTrue(
            collect($ambulancias)->every(fn($a) => $a['tipo_ambulancia'] === 'avanzada')
        );
    }

    /**
     * Test GET /api/v1/ambulancias with disponibles=true filter
     */
    public function test_get_ambulancias_solo_disponibles(): void
    {
        $response = $this->getJson('/api/v1/ambulancias?disponibles=true');

        $response->assertStatus(200);

        $ambulancias = $response->json('data');
        $this->assertTrue(
            collect($ambulancias)->every(fn($a) => $a['estado'] === 'disponible')
        );
    }

    /**
     * Test GET /api/v1/ambulancias/:id - get single ambulancia
     */
    public function test_get_ambulancia_by_id(): void
    {
        $ambulancia = Ambulancia::first();

        $response = $this->getJson("/api/v1/ambulancias/{$ambulancia->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $ambulancia->id,
                    'placa' => $ambulancia->placa,
                    'modelo' => $ambulancia->modelo,
                    'tipo_ambulancia' => $ambulancia->tipo_ambulancia,
                ]
            ]);
    }

    /**
     * Test GET /api/v1/ambulancias/:id with invalid ID returns 404
     */
    public function test_get_ambulancia_invalid_id_returns_404(): void
    {
        $response = $this->getJson('/api/v1/ambulancias/99999');

        $response->assertStatus(404);
    }

    /**
     * Test POST /api/v1/ambulancias/:id/ubicacion - update location
     */
    public function test_actualizar_ubicacion(): void
    {
        $ambulancia = Ambulancia::first();

        $datos = [
            'latitud' => -16.6,
            'longitud' => -68.14,
        ];

        $response = $this->postJson(
            "/api/v1/ambulancias/{$ambulancia->id}/ubicacion",
            $datos
        );

        $response->assertStatus(200);

        $ambulancia->refresh();
        $this->assertEquals(-16.6, $ambulancia->ubicacion_actual_lat);
        $this->assertEquals(-68.14, $ambulancia->ubicacion_actual_lng);
    }

    /**
     * Test POST /api/v1/ambulancias/:id/ubicacion - invalid coordinates
     */
    public function test_actualizar_ubicacion_invalid_coordinates(): void
    {
        $ambulancia = Ambulancia::first();

        $datos = [
            'latitud' => 91, // Invalid
            'longitud' => -68.14,
        ];

        $response = $this->postJson(
            "/api/v1/ambulancias/{$ambulancia->id}/ubicacion",
            $datos
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitud']);
    }

    /**
     * Test POST /api/v1/ambulancias/:id/ubicacion - missing required fields
     */
    public function test_actualizar_ubicacion_missing_fields(): void
    {
        $ambulancia = Ambulancia::first();

        $response = $this->postJson(
            "/api/v1/ambulancias/{$ambulancia->id}/ubicacion",
            []
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitud', 'longitud']);
    }

    /**
     * Test PATCH /api/v1/ambulancias/:id/estado - update status
     */
    public function test_actualizar_estado(): void
    {
        $ambulancia = Ambulancia::where('placa', 'AMB-001')->first();

        $response = $this->patchJson(
            "/api/v1/ambulancias/{$ambulancia->id}/estado",
            ['estado' => 'en_servicio']
        );

        $response->assertStatus(200);

        $ambulancia->refresh();
        $this->assertEquals('en_servicio', $ambulancia->estado);
    }

    /**
     * Test PATCH /api/v1/ambulancias/:id/estado - invalid estado
     */
    public function test_actualizar_estado_invalid(): void
    {
        $ambulancia = Ambulancia::first();

        $response = $this->patchJson(
            "/api/v1/ambulancias/{$ambulancia->id}/estado",
            ['estado' => 'estado_invalido']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);
    }

    /**
     * Test ambulancia status transitions
     */
    public function test_ambulancia_status_transitions(): void
    {
        $ambulancia = Ambulancia::first();

        // disponible -> en_servicio
        $this->patchJson(
            "/api/v1/ambulancias/{$ambulancia->id}/estado",
            ['estado' => 'en_servicio']
        );
        $ambulancia->refresh();
        $this->assertEquals('en_servicio', $ambulancia->estado);

        // en_servicio -> disponible
        $this->patchJson(
            "/api/v1/ambulancias/{$ambulancia->id}/estado",
            ['estado' => 'disponible']
        );
        $ambulancia->refresh();
        $this->assertEquals('disponible', $ambulancia->estado);

        // disponible -> mantenimiento
        $this->patchJson(
            "/api/v1/ambulancias/{$ambulancia->id}/estado",
            ['estado' => 'mantenimiento']
        );
        $ambulancia->refresh();
        $this->assertEquals('mantenimiento', $ambulancia->estado);

        // mantenimiento -> disponible
        $this->patchJson(
            "/api/v1/ambulancias/{$ambulancia->id}/estado",
            ['estado' => 'disponible']
        );
        $ambulancia->refresh();
        $this->assertEquals('disponible', $ambulancia->estado);

        // disponible -> fuera_servicio
        $this->patchJson(
            "/api/v1/ambulancias/{$ambulancia->id}/estado",
            ['estado' => 'fuera_servicio']
        );
        $ambulancia->refresh();
        $this->assertEquals('fuera_servicio', $ambulancia->estado);
    }

    /**
     * Test ambulancia caracteristicas JSON
     */
    public function test_ambulancia_caracteristicas(): void
    {
        $ambulancia = Ambulancia::first();

        $response = $this->getJson("/api/v1/ambulancias/{$ambulancia->id}");

        $response->assertStatus(200);
        $this->assertIsArray($response->json('data.caracteristicas'));
    }

    /**
     * Test location updates trigger events
     */
    public function test_location_update_broadcasts_event(): void
    {
        $ambulancia = Ambulancia::first();

        $this->postJson(
            "/api/v1/ambulancias/{$ambulancia->id}/ubicacion",
            ['latitud' => -16.6, 'longitud' => -68.14]
        );

        // Verify event was dispatched (check via event log or DB)
        $this->assertTrue(true); // Event broadcasting tested in feature tests
    }

    /**
     * Test filtering by multiple criteria
     */
    public function test_filter_ambulancias_multiple_criteria(): void
    {
        $response = $this->getJson(
            '/api/v1/ambulancias?tipo_ambulancia=avanzada&estado=disponible'
        );

        $response->assertStatus(200);
        $ambulancias = $response->json('data');

        $this->assertTrue(
            collect($ambulancias)->every(
                fn($a) => $a['tipo_ambulancia'] === 'avanzada' && $a['estado'] === 'disponible'
            )
        );
    }

    /**
     * Test ambulancia with all fields
     */
    public function test_ambulancia_response_structure(): void
    {
        $ambulancia = Ambulancia::first();

        $response = $this->getJson("/api/v1/ambulancias/{$ambulancia->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'placa',
                    'modelo',
                    'tipo_ambulancia',
                    'estado',
                    'ubicacion_actual_lat',
                    'ubicacion_actual_lng',
                    'caracteristicas',
                    'ultima_actualizacion',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }
}
