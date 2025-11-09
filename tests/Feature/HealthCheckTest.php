<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test GET /api/health endpoint
     */
    public function test_health_endpoint(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'service',
                'version',
                'timestamp',
            ]);

        $this->assertEquals('healthy', $response->json('status'));
        $this->assertEquals('ms-despacho', $response->json('service'));
    }

    /**
     * Test GET /api/health/microservices endpoint
     */
    public function test_microservices_health_endpoint(): void
    {
        $response = $this->getJson('/api/health/microservices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'services' => [
                    '*' => [
                        'name',
                        'healthy',
                        'url',
                        'latency_ms',
                    ]
                ],
                'timestamp',
            ]);
    }

    /**
     * Test health endpoint shows database status
     */
    public function test_health_includes_database_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('checks', $data);
        $this->assertArrayHasKey('database', $data['checks']);
        $this->assertTrue($data['checks']['database']['healthy']);
    }

    /**
     * Test health endpoint shows cache status
     */
    public function test_health_includes_cache_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('cache', $data['checks']);
        $this->assertTrue($data['checks']['cache']['healthy']);
    }

    /**
     * Test health endpoint shows queue status
     */
    public function test_health_includes_queue_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('queue', $data['checks']);
        // Queue is optional, just check it exists
        $this->assertIsArray($data['checks']);
    }

    /**
     * Test microservices health checks all services
     */
    public function test_microservices_health_checks_all_services(): void
    {
        $response = $this->getJson('/api/health/microservices');

        $response->assertStatus(200);

        $services = $response->json('services');
        $this->assertNotEmpty($services);

        // Check structure of each service
        foreach ($services as $service) {
            $this->assertArrayHasKey('name', $service);
            $this->assertArrayHasKey('healthy', $service);
            $this->assertArrayHasKey('url', $service);
            $this->assertArrayHasKey('latency_ms', $service);
        }
    }

    /**
     * Test health endpoint response time
     */
    public function test_health_endpoint_fast_response(): void
    {
        $start = microtime(true);
        $this->getJson('/api/health');
        $duration = (microtime(true) - $start) * 1000;

        // Health check should respond in less than 1 second (1000ms)
        $this->assertLessThan(1000, $duration);
    }
}
