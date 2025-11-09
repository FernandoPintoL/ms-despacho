<?php

namespace Tests\Feature;

use App\Models\Ambulancia;
use App\Models\Despacho;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DespachoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearDatosTest();
    }

    private function crearDatosTest(): void
    {
        // Create test user
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create ambulances
        Ambulancia::factory()->count(3)->create([
            'estado' => 'disponible',
        ]);

        // Create despachos
        Despacho::factory()->count(5)->create([
            'ambulancia_id' => Ambulancia::first()->id,
            'estado' => 'completado',
        ]);
    }

    /**
     * Test GET /api/v1/despachos - list all despachos
     */
    public function test_get_despachos_list(): void
    {
        $response = $this->getJson('/api/v1/despachos');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'solicitud_id',
                        'ambulancia_id',
                        'estado',
                        'prioridad',
                        'distancia_km',
                        'tiempo_estimado_min',
                        'fecha_solicitud',
                    ]
                ]
            ]);

        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    /**
     * Test GET /api/v1/despachos with estado filter
     */
    public function test_get_despachos_con_filtro_estado(): void
    {
        Despacho::where('estado', 'completado')
            ->first()
            ->update(['estado' => 'en_camino']);

        $response = $this->getJson('/api/v1/despachos?estado=en_camino');

        $response->assertStatus(200);

        $despachos = $response->json('data');
        $this->assertTrue(
            collect($despachos)->every(fn($d) => $d['estado'] === 'en_camino')
        );
    }

    /**
     * Test GET /api/v1/despachos with prioridad filter
     */
    public function test_get_despachos_con_filtro_prioridad(): void
    {
        Despacho::first()->update(['prioridad' => 'critica']);

        $response = $this->getJson('/api/v1/despachos?prioridad=critica');

        $response->assertStatus(200);
        $despachos = $response->json('data');
        $this->assertTrue(
            collect($despachos)->every(fn($d) => $d['prioridad'] === 'critica')
        );
    }

    /**
     * Test GET /api/v1/despachos/:id - get single despacho
     */
    public function test_get_despacho_by_id(): void
    {
        $despacho = Despacho::first();

        $response = $this->getJson("/api/v1/despachos/{$despacho->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $despacho->id,
                    'solicitud_id' => $despacho->solicitud_id,
                    'ambulancia_id' => $despacho->ambulancia_id,
                    'estado' => $despacho->estado,
                ]
            ]);
    }

    /**
     * Test GET /api/v1/despachos/:id with invalid ID returns 404
     */
    public function test_get_despacho_invalid_id_returns_404(): void
    {
        $response = $this->getJson('/api/v1/despachos/99999');

        $response->assertStatus(404);
    }

    /**
     * Test POST /api/v1/despachos - create new despacho
     */
    public function test_create_despacho(): void
    {
        $datos = [
            'ubicacion_origen_lat' => -16.5,
            'ubicacion_origen_lng' => -68.15,
            'ubicacion_destino_lat' => -16.8854,
            'ubicacion_destino_lng' => -68.1131,
            'incidente' => 'accidente',
            'prioridad' => 'alta',
            'observaciones' => 'Accidente vehicular',
        ];

        $response = $this->postJson('/api/v1/despachos', $datos);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'ambulancia_id',
                    'estado',
                    'distancia_km',
                    'tiempo_estimado_min',
                ]
            ]);

        $despacho = Despacho::find($response->json('data.id'));
        $this->assertNotNull($despacho);
        $this->assertEquals('asignado', $despacho->estado);
        $this->assertEquals('alta', $despacho->prioridad);
    }

    /**
     * Test POST /api/v1/despachos - validation fails with invalid data
     */
    public function test_create_despacho_validation_fails(): void
    {
        $datos = [
            'ubicacion_origen_lat' => 91, // Invalid
            'ubicacion_origen_lng' => -68.15,
        ];

        $response = $this->postJson('/api/v1/despachos', $datos);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ubicacion_origen_lat']);
    }

    /**
     * Test POST /api/v1/despachos - missing required fields
     */
    public function test_create_despacho_missing_required_fields(): void
    {
        $datos = [
            'incidente' => 'accidente',
        ];

        $response = $this->postJson('/api/v1/despachos', $datos);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'ubicacion_origen_lat',
                'ubicacion_origen_lng',
            ]);
    }

    /**
     * Test PATCH /api/v1/despachos/:id - update dispatch status
     */
    public function test_update_despacho_estado(): void
    {
        $despacho = Despacho::first();

        $response = $this->patchJson(
            "/api/v1/despachos/{$despacho->id}",
            ['estado' => 'en_camino']
        );

        $response->assertStatus(200);

        $despacho->refresh();
        $this->assertEquals('en_camino', $despacho->estado);
    }

    /**
     * Test PATCH /api/v1/despachos/:id - invalid state rejected
     */
    public function test_update_despacho_invalid_estado(): void
    {
        $despacho = Despacho::first();

        $response = $this->patchJson(
            "/api/v1/despachos/{$despacho->id}",
            ['estado' => 'estado_invalido']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);
    }

    /**
     * Test POST /api/v1/despachos/:id/rastreo - add tracking
     */
    public function test_add_rastreo(): void
    {
        $despacho = Despacho::where('estado', '!=', 'completado')->first();
        if (!$despacho) {
            $despacho = Despacho::factory()->create([
                'ambulancia_id' => Ambulancia::first()->id,
                'estado' => 'en_camino',
            ]);
        }

        $datos = [
            'latitud' => -16.6,
            'longitud' => -68.14,
            'velocidad' => 45,
            'altitud' => 3450,
            'precision' => 10,
        ];

        $response = $this->postJson(
            "/api/v1/despachos/{$despacho->id}/rastreo",
            $datos
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'despacho_id',
                    'latitud',
                    'longitud',
                    'velocidad',
                    'timestamp_gps',
                ]
            ]);

        // Verify ambulancia location updated
        $despacho->ambulancia->refresh();
        $this->assertEquals(-16.6, $despacho->ambulancia->ubicacion_actual_lat);
        $this->assertEquals(-68.14, $despacho->ambulancia->ubicacion_actual_lng);
    }

    /**
     * Test POST /api/v1/despachos/:id/rastreo - invalid coordinates rejected
     */
    public function test_add_rastreo_invalid_coordinates(): void
    {
        $despacho = Despacho::first();

        $datos = [
            'latitud' => 91, // Invalid
            'longitud' => -68.14,
        ];

        $response = $this->postJson(
            "/api/v1/despachos/{$despacho->id}/rastreo",
            $datos
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitud']);
    }

    /**
     * Test GET /api/v1/despachos with pagination
     */
    public function test_despacho_list_pagination(): void
    {
        $response = $this->getJson('/api/v1/despachos?per_page=2&page=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertLessThanOrEqual(2, count($data));
    }

    /**
     * Test despacho with ambulancia relation
     */
    public function test_despacho_includes_ambulancia(): void
    {
        $despacho = Despacho::with('ambulancia')->first();

        $response = $this->getJson("/api/v1/despachos/{$despacho->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.ambulancia.id', $despacho->ambulancia->id)
            ->assertJsonPath('data.ambulancia.placa', $despacho->ambulancia->placa);
    }

    /**
     * Test despacho estado transitions
     */
    public function test_despacho_estado_transitions(): void
    {
        $despacho = Despacho::first();

        // pendiente -> asignado
        $this->patchJson("/api/v1/despachos/{$despacho->id}", ['estado' => 'asignado']);
        $despacho->refresh();
        $this->assertEquals('asignado', $despacho->estado);

        // asignado -> en_camino
        $this->patchJson("/api/v1/despachos/{$despacho->id}", ['estado' => 'en_camino']);
        $despacho->refresh();
        $this->assertEquals('en_camino', $despacho->estado);

        // en_camino -> en_sitio
        $this->patchJson("/api/v1/despachos/{$despacho->id}", ['estado' => 'en_sitio']);
        $despacho->refresh();
        $this->assertEquals('en_sitio', $despacho->estado);

        // en_sitio -> trasladando
        $this->patchJson("/api/v1/despachos/{$despacho->id}", ['estado' => 'trasladando']);
        $despacho->refresh();
        $this->assertEquals('trasladando', $despacho->estado);

        // trasladando -> completado
        $this->patchJson("/api/v1/despachos/{$despacho->id}", ['estado' => 'completado']);
        $despacho->refresh();
        $this->assertEquals('completado', $despacho->estado);
    }

    /**
     * Test despacho cancelation
     */
    public function test_cancelar_despacho(): void
    {
        $despacho = Despacho::where('estado', 'asignado')->first();
        if (!$despacho) {
            $despacho = Despacho::factory()->create([
                'ambulancia_id' => Ambulancia::first()->id,
                'estado' => 'asignado',
            ]);
        }

        $response = $this->patchJson(
            "/api/v1/despachos/{$despacho->id}",
            ['estado' => 'cancelado']
        );

        $response->assertStatus(200);
        $despacho->refresh();
        $this->assertEquals('cancelado', $despacho->estado);
    }
}
