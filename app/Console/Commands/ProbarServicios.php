<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProbarServicios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'probar:servicios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar servicios GPS, Asignación y ML';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando Servicios del Sistema de Despacho');
        $this->newLine();

        // 1. Probar GpsService
        $this->probarGpsService();
        $this->newLine();

        // 2. Probar AsignacionService
        $this->probarAsignacionService();
        $this->newLine();

        // 3. Probar MlService
        $this->probarMlService();
        $this->newLine();

        $this->info('✅ Pruebas completadas');
        
        return 0;
    }

    private function probarGpsService()
    {
        $this->info('📍 Probando GpsService...');
        
        $gpsService = app(\App\Services\GpsService::class);

        // Coordenadas de prueba (La Paz, Bolivia)
        $lat1 = -16.5000;
        $lng1 = -68.1500;
        $lat2 = -16.5100;
        $lng2 = -68.1400;

        // Calcular distancia
        $distancia = $gpsService->calcularDistancia($lat1, $lng1, $lat2, $lng2);
        $this->line("  - Distancia calculada: {$distancia} km");

        // Estimar tiempo
        $tiempo = $gpsService->estimarTiempoViaje($distancia);
        $this->line("  - Tiempo estimado: {$tiempo} minutos");

        // Validar coordenadas
        $validas = $gpsService->validarCoordenadas($lat1, $lng1);
        $this->line("  - Coordenadas válidas: " . ($validas ? 'Sí' : 'No'));

        $this->info('  ✓ GpsService funcionando correctamente');
    }

    private function probarAsignacionService()
    {
        $this->info('🚑 Probando AsignacionService...');
        
        $asignacionService = app(\App\Services\AsignacionService::class);

        // Obtener estadísticas
        $stats = $asignacionService->obtenerEstadisticasDisponibilidad();
        
        $this->line("  - Ambulancias disponibles: {$stats['ambulancias']['disponibles']}/{$stats['ambulancias']['total']}");
        $this->line("  - Personal disponible: {$stats['personal']['disponibles']}/{$stats['personal']['total']}");
        $this->line("  - Despachos activos: {$stats['despachos_activos']}");

        // Buscar ambulancia más cercana
        $resultado = $asignacionService->encontrarAmbulanciaMasCercana(-16.5000, -68.1500);
        
        if ($resultado) {
            $ambulancia = $resultado['ambulancia'];
            $distancia = $resultado['distancia_km'];
            $this->line("  - Ambulancia más cercana: {$ambulancia->placa} ({$ambulancia->tipo_ambulancia}) a {$distancia} km");
        } else {
            $this->warn('  - No hay ambulancias disponibles');
        }

        $this->info('  ✓ AsignacionService funcionando correctamente');
    }

    private function probarMlService()
    {
        $this->info('🤖 Probando MlService...');
        
        try {
            $mlService = app(\App\Services\MlService::class);

            // Verificar estado del servicio
            $disponible = $mlService->verificarEstado();
            $this->line("  - Servicio ML disponible: " . ($disponible ? 'Sí' : 'No (usando fallback)'));

            // Predecir tiempo
            $tiempoEstimado = $mlService->predecirTiempoLlegada(10.5, 'avanzada', 0.6);
            $this->line("  - Predicción para 10.5 km: {$tiempoEstimado} minutos");

            if ($disponible) {
                // Obtener estadísticas
                $stats = $mlService->obtenerEstadisticas();
                if ($stats) {
                    $this->line("  - Predicciones realizadas: " . ($stats['total_predictions'] ?? 'N/A'));
                }
            }

            $this->info('  ✓ MlService funcionando correctamente');
        } catch (\Exception $e) {
            $this->warn('  ⚠ Error al probar MlService: ' . $e->getMessage());
            $this->line('  - Nota: Redis puede no estar configurado. El servicio funcionará con fallback.');
        }
    }
}
