<?php

namespace Tests\Unit\Services;

use App\Models\Ambulancia;
use App\Models\Despacho;
use App\Models\Personal;
use App\Services\AsignacionService;
use App\Services\GpsService;
use App\Services\MlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsignacionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AsignacionService $asignacionService;
    private GpsService $gpsService;
    private MlService $mlService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gpsService = new GpsService();
        $this->mlService = new MlService();
        $this->asignacionService = new AsignacionService($this->gpsService, $this->mlService);

        // Create test data
        $this->crearDatosTest();
    }

    private function crearDatosTest(): void
    {
        // Create ambulances
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
            'estado' => 'disponible',
            'ubicacion_actual_lat' => -16.8854,
            'ubicacion_actual_lng' => -68.1131,
        ]);

        Ambulancia::factory()->create([
            'placa' => 'AMB-003',
            'modelo' => 'Toyota Hiace',
            'tipo_ambulancia' => 'basica',
            'estado' => 'en_servicio',
            'ubicacion_actual_lat' => -17.3895,
            'ubicacion_actual_lng' => -66.1568,
        ]);

        // Create personal
        Personal::factory()->create([
            'nombre' => 'Carlos',
            'apellido' => 'García',
            'ci' => '1234567',
            'rol' => 'conductor',
            'estado' => 'disponible',
        ]);

        Personal::factory()->create([
            'nombre' => 'Juan',
            'apellido' => 'Rodríguez',
            'ci' => '7654321',
            'rol' => 'paramedico',
            'estado' => 'disponible',
        ]);

        Personal::factory()->create([
            'nombre' => 'María',
            'apellido' => 'López',
            'ci' => '5555555',
            'rol' => 'paramedico',
            'estado' => 'en_servicio',
        ]);
    }

    /**
     * Test finding closest ambulance
     */
    public function test_encontrar_ambulancia_mas_cercana(): void
    {
        // Request from somewhere between AMB-001 and AMB-002
        $latOrigen = -16.7;
        $lngOrigen = -68.13;

        $ambulancia = $this->asignacionService->encontrarAmbulanciaMasCercana(
            $latOrigen,
            $lngOrigen,
            'disponible',
            'intermedia'
        );

        // Should find AMB-002 (intermedia type available)
        $this->assertNotNull($ambulancia);
        $this->assertEquals('AMB-002', $ambulancia->placa);
    }

    /**
     * Test finding closest ambulance with no specific type
     */
    public function test_encontrar_ambulancia_mas_cercana_sin_tipo(): void
    {
        $latOrigen = -16.7;
        $lngOrigen = -68.13;

        $ambulancia = $this->asignacionService->encontrarAmbulanciaMasCercana(
            $latOrigen,
            $lngOrigen,
            'disponible'
        );

        // Should find AMB-001 (closest available, regardless of type)
        $this->assertNotNull($ambulancia);
        $this->assertTrue(in_array($ambulancia->placa, ['AMB-001', 'AMB-002']));
    }

    /**
     * Test returns null when no ambulances available
     */
    public function test_no_ambulancia_disponible(): void
    {
        // Mark all ambulances as unavailable
        Ambulancia::query()->update(['estado' => 'en_servicio']);

        $latOrigen = -16.5;
        $lngOrigen = -68.15;

        $ambulancia = $this->asignacionService->encontrarAmbulanciaMasCercana(
            $latOrigen,
            $lngOrigen,
            'disponible'
        );

        $this->assertNull($ambulancia);
    }

    /**
     * Test listing ambulances by distance
     */
    public function test_listar_ambulancias_por_distancia(): void
    {
        $latOrigen = -16.5;
        $lngOrigen = -68.15;

        $ambulancias = $this->asignacionService->listarAmbulanciasPorDistancia(
            $latOrigen,
            $lngOrigen,
            5
        );

        // Should return ambulances sorted by distance
        $this->assertCount(3, $ambulancias);

        // First should be AMB-001 (same location)
        $this->assertEquals('AMB-001', $ambulancias[0]['ambulancia']->placa);

        // Distances should be in ascending order
        $this->assertLessThan(
            $ambulancias[1]['distancia'],
            $ambulancias[2]['distancia']
        );
    }

    /**
     * Test creating a dispatch
     */
    public function test_crear_despacho(): void
    {
        $datos = [
            'ubicacion_origen_lat' => -16.5,
            'ubicacion_origen_lng' => -68.15,
            'ubicacion_destino_lat' => -16.8854,
            'ubicacion_destino_lng' => -68.1131,
            'incidente' => 'accidente',
            'prioridad' => 'alta',
            'tipo_ambulancia' => 'avanzada',
            'observaciones' => 'Accidente vehicular con múltiples heridos',
        ];

        $despacho = $this->asignacionService->crearDespacho($datos);

        $this->assertNotNull($despacho->id);
        $this->assertEquals('asignado', $despacho->estado);
        $this->assertEquals('avanzada', $despacho->ambulancia->tipo_ambulancia);
        $this->assertEquals('alta', $despacho->prioridad);
        $this->assertGreaterThan(0, $despacho->distancia_km);
        $this->assertGreaterThan(0, $despacho->tiempo_estimado_min);
    }

    /**
     * Test creating dispatch with invalid coordinates
     */
    public function test_crear_despacho_coordenadas_invalidas(): void
    {
        $datos = [
            'ubicacion_origen_lat' => 91, // Invalid
            'ubicacion_origen_lng' => -68.15,
            'incidente' => 'accidente',
            'prioridad' => 'alta',
        ];

        $this->expectException(\Exception::class);
        $this->asignacionService->crearDespacho($datos);
    }

    /**
     * Test assigning personal to dispatch
     */
    public function test_asignar_personal(): void
    {
        // Create dispatch first
        $despacho = Despacho::factory()->create([
            'ambulancia_id' => Ambulancia::first()->id,
            'estado' => 'asignado',
        ]);

        $rolesRequeridos = ['conductor', 'paramedico'];

        $result = $this->asignacionService->asignarPersonal(
            $despacho,
            $rolesRequeridos
        );

        // Should have assigned staff
        $this->assertTrue($result);
        $this->assertEquals(2, $despacho->personalAsignado()->count());

        // Check roles are correct
        $rolesAsignados = $despacho->personalAsignado()
            ->pluck('rol_asignado')
            ->toArray();

        $this->assertContains('conductor', $rolesAsignados);
        $this->assertContains('paramedico', $rolesAsignados);
    }

    /**
     * Test releasing personal
     */
    public function test_liberar_personal(): void
    {
        $despacho = Despacho::factory()->create([
            'ambulancia_id' => Ambulancia::first()->id,
        ]);

        // Assign first
        $this->asignacionService->asignarPersonal(
            $despacho,
            ['conductor', 'paramedico']
        );

        $this->assertEquals(2, $despacho->personalAsignado()->count());

        // Now release
        $this->asignacionService->liberarPersonal($despacho);

        $this->assertEquals(0, $despacho->personalAsignado()->count());
    }

    /**
     * Test finalizing dispatch
     */
    public function test_finalizar_despacho(): void
    {
        $despacho = Despacho::factory()->create([
            'ambulancia_id' => Ambulancia::first()->id,
            'estado' => 'trasladando',
            'fecha_asignacion' => now()->subMinutes(45),
            'tiempo_estimado_min' => 40,
        ]);

        $this->asignacionService->finalizarDespacho($despacho, 'completado');

        $despacho->refresh();

        $this->assertEquals('completado', $despacho->estado);
        $this->assertNotNull($despacho->fecha_finalizacion);
        $this->assertNotNull($despacho->tiempo_real_min);
        $this->assertEquals('disponible', $despacho->ambulancia->refresh()->estado);
    }

    /**
     * Test getting availability statistics
     */
    public function test_obtener_estadisticas_disponibilidad(): void
    {
        $stats = $this->asignacionService->obtenerEstadisticasDisponibilidad();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_ambulancias', $stats);
        $this->assertArrayHasKey('disponibles', $stats);
        $this->assertArrayHasKey('en_servicio', $stats);
        $this->assertArrayHasKey('mantenimiento', $stats);
        $this->assertArrayHasKey('por_tipo', $stats);

        $this->assertEquals(3, $stats['total_ambulancias']);
        $this->assertEquals(2, $stats['disponibles']);
    }

    /**
     * Test ambulancia becomes unavailable after dispatch
     */
    public function test_ambulancia_no_disponible_despues_despacho(): void
    {
        $ambulancia = Ambulancia::where('placa', 'AMB-001')->first();
        $this->assertEquals('disponible', $ambulancia->estado);

        $datos = [
            'ubicacion_origen_lat' => -16.5,
            'ubicacion_origen_lng' => -68.15,
            'incidente' => 'accidente',
            'prioridad' => 'alta',
        ];

        $despacho = $this->asignacionService->crearDespacho($datos);

        $ambulancia->refresh();
        $this->assertEquals('en_servicio', $ambulancia->estado);
    }
}
