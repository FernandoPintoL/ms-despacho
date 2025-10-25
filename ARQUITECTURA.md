# Arquitectura MS Despacho de Ambulancias

## 🎯 Responsabilidades del Microservicio

1. **Gestión de Ambulancias**: CRUD y estado de ambulancias
2. **Gestión de Personal**: Paramédicos, conductores, médicos
3. **Asignación Inteligente**: Algoritmo de selección de ambulancia óptima
4. **Cálculo GPS**: Distancias y rutas entre puntos
5. **Predicción ML**: Tiempo estimado de llegada
6. **Rastreo en Tiempo Real**: WebSocket para tracking GPS
7. **Comunicación Inter-MS**: GraphQL y REST con otros microservicios

## 📊 Modelo de Datos

### Tabla: ambulancias

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT PK | ID único |
| placa | VARCHAR(10) | Placa del vehículo |
| modelo | VARCHAR(50) | Modelo del vehículo |
| tipo_ambulancia | ENUM | básica, intermedia, avanzada, UCI |
| estado | ENUM | disponible, en_servicio, mantenimiento, fuera_servicio |
| caracteristicas | JSON | Equipamiento especial |
| ubicacion_actual_lat | DECIMAL(10,8) | Latitud actual |
| ubicacion_actual_lng | DECIMAL(11,8) | Longitud actual |
| ultima_actualizacion | TIMESTAMP | Última actualización GPS |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### Tabla: personal

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT PK | ID único |
| nombre | VARCHAR(100) | Nombre completo |
| apellido | VARCHAR(100) | Apellido |
| ci | VARCHAR(20) | Cédula de identidad |
| rol | ENUM | paramedico, conductor, medico, enfermero |
| especialidad | VARCHAR(100) | Especialidad médica |
| experiencia | INT | Años de experiencia |
| estado | ENUM | disponible, en_servicio, descanso, vacaciones |
| telefono | VARCHAR(20) | Teléfono de contacto |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### Tabla: despachos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT PK | ID único |
| solicitud_id | INT | ID de solicitud (MS Recepción) |
| ambulancia_id | INT FK | Ambulancia asignada |
| fecha | DATETIME | Fecha del despacho |
| ubicacion_origen_lat | DECIMAL(10,8) | Latitud origen |
| ubicacion_origen_lng | DECIMAL(11,8) | Longitud origen |
| ubicacion_destino_lat | DECIMAL(10,8) | Latitud destino (hospital) |
| ubicacion_destino_lng | DECIMAL(11,8) | Longitud destino |
| distancia_km | DECIMAL(6,2) | Distancia calculada |
| tiempo_estimado_min | INT | Tiempo estimado ML |
| tiempo_real_min | INT | Tiempo real de llegada |
| resultado_final | ENUM | completado, cancelado, redirigido |
| incidente | ENUM | accidente, emergencia_medica, traslado |
| decision | ENUM | ambulatoria, traslado |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### Tabla: asignacion_personal

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT PK | ID único |
| despacho_id | INT FK | Despacho asociado |
| personal_id | INT FK | Personal asignado |
| rol | ENUM | paramedico, conductor, medico_apoyo |
| fecha_inicio | DATETIME | Inicio de turno |
| fecha_fin | DATETIME | Fin de turno |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

### Tabla: historial_rastreo

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT PK | ID único |
| despacho_id | INT FK | Despacho asociado |
| latitud | DECIMAL(10,8) | Latitud |
| longitud | DECIMAL(11,8) | Longitud |
| velocidad | DECIMAL(5,2) | Velocidad en km/h |
| timestamp | TIMESTAMP | Momento del registro |
| created_at | TIMESTAMP | |

### Tabla: estado_despacho

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT PK | ID único |
| despacho_id | INT FK | Despacho asociado |
| estado_actual | ENUM | asignado, en_ruta, en_sitio, trasladando, completado |
| tiempo_ultima_actualizacion | DATETIME | Última actualización |
| distancia_restante | DECIMAL(6,2) | Distancia restante en km |
| tiempo_estimado_restante | INT | Minutos restantes |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

## 🔄 Flujo de Trabajo Detallado

### 1. Recepción de Solicitud

```
MS Recepción → GraphQL Mutation → MS Despacho
```

**Input:**
```graphql
mutation CrearDespacho {
  crearDespacho(input: {
    solicitudId: 123
    ubicacionOrigen: {
      lat: -33.4569
      lng: -70.6483
    }
    tipoEmergencia: "accidente"
    severidad: "alta"
    descripcion: "Accidente vehicular, 2 heridos"
  }) {
    id
    ambulanciaAsignada {
      id
      placa
      tipo
    }
    personalAsignado {
      id
      nombre
      rol
    }
    tiempoEstimado
  }
}
```

### 2. Proceso de Asignación (Backend)

```php
// DespachoService.php

public function asignarAmbulancia($solicitud)
{
    // 1. Obtener ambulancias disponibles
    $ambulancias = $this->obtenerAmbulanciasDisponibles($solicitud->tipo_emergencia);
    
    // 2. Calcular distancias
    $ambulanciasConDistancia = $ambulancias->map(function($ambulancia) use ($solicitud) {
        return [
            'ambulancia' => $ambulancia,
            'distancia' => $this->gpsService->calcularDistancia(
                $ambulancia->ubicacion_actual_lat,
                $ambulancia->ubicacion_actual_lng,
                $solicitud->ubicacion_lat,
                $solicitud->ubicacion_lng
            )
        ];
    });
    
    // 3. Ordenar por distancia
    $ambulanciasOrdenadas = $ambulanciasConDistancia->sortBy('distancia');
    
    // 4. Seleccionar la más cercana
    $seleccionada = $ambulanciasOrdenadas->first();
    
    // 5. Predecir tiempo de llegada con ML
    $tiempoEstimado = $this->mlService->predecirTiempoLlegada([
        'distancia' => $seleccionada['distancia'],
        'hora_dia' => now()->hour,
        'dia_semana' => now()->dayOfWeek,
        'tipo_ambulancia' => $seleccionada['ambulancia']->tipo,
        'trafico_estimado' => $this->obtenerTraficoEstimado()
    ]);
    
    // 6. Asignar personal disponible
    $personal = $this->asignarPersonal($seleccionada['ambulancia']);
    
    // 7. Crear despacho
    $despacho = Despacho::create([
        'solicitud_id' => $solicitud->id,
        'ambulancia_id' => $seleccionada['ambulancia']->id,
        'ubicacion_origen_lat' => $solicitud->ubicacion_lat,
        'ubicacion_origen_lng' => $solicitud->ubicacion_lng,
        'distancia_km' => $seleccionada['distancia'],
        'tiempo_estimado_min' => $tiempoEstimado,
        'incidente' => $solicitud->tipo_emergencia
    ]);
    
    // 8. Actualizar estado ambulancia
    $seleccionada['ambulancia']->update(['estado' => 'en_servicio']);
    
    // 9. Notificar via WebSocket
    broadcast(new AmbulanciaAsignada($despacho));
    
    // 10. Notificar a paramédico (Queue Job)
    NotificarParamedico::dispatch($despacho, $personal);
    
    return $despacho;
}
```

### 3. Cálculo de Distancia GPS

```php
// GpsService.php

public function calcularDistancia($lat1, $lng1, $lat2, $lng2)
{
    // Fórmula de Haversine
    $earthRadius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng/2) * sin($dLng/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    $distance = $earthRadius * $c;
    
    return round($distance, 2);
}
```

### 4. Machine Learning - Predicción de Tiempo

```php
// MLPredictionService.php

use Phpml\Regression\LeastSquares;
use Phpml\ModelManager;

public function predecirTiempoLlegada($features)
{
    // Cargar modelo entrenado
    $modelPath = storage_path('ml_models/tiempo_llegada.model');
    
    if (!file_exists($modelPath)) {
        return $this->estimacionBasica($features['distancia']);
    }
    
    $modelManager = new ModelManager();
    $model = $modelManager->restoreFromFile($modelPath);
    
    // Preparar features
    $input = [
        $features['distancia'],
        $features['hora_dia'],
        $features['dia_semana'],
        $this->encodeTipoAmbulancia($features['tipo_ambulancia']),
        $features['trafico_estimado']
    ];
    
    // Predecir
    $tiempoMinutos = $model->predict($input);
    
    return max(5, round($tiempoMinutos)); // Mínimo 5 minutos
}

public function entrenarModelo()
{
    // Obtener datos históricos
    $historicos = Despacho::whereNotNull('tiempo_real_min')
        ->with('ambulancia')
        ->get();
    
    $samples = [];
    $targets = [];
    
    foreach ($historicos as $despacho) {
        $samples[] = [
            $despacho->distancia_km,
            $despacho->created_at->hour,
            $despacho->created_at->dayOfWeek,
            $this->encodeTipoAmbulancia($despacho->ambulancia->tipo),
            $this->calcularTraficoHistorico($despacho->created_at)
        ];
        
        $targets[] = $despacho->tiempo_real_min;
    }
    
    // Entrenar modelo
    $model = new LeastSquares();
    $model->train($samples, $targets);
    
    // Guardar modelo
    $modelManager = new ModelManager();
    $modelManager->saveToFile($model, storage_path('ml_models/tiempo_llegada.model'));
    
    return true;
}
```

### 5. WebSocket - Rastreo en Tiempo Real

```php
// Event: UbicacionActualizada.php

class UbicacionActualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $despacho;
    public $ubicacion;
    
    public function __construct(Despacho $despacho, array $ubicacion)
    {
        $this->despacho = $despacho;
        $this->ubicacion = $ubicacion;
    }
    
    public function broadcastOn()
    {
        return new Channel('despacho.' . $this->despacho->id);
    }
    
    public function broadcastAs()
    {
        return 'ubicacion.actualizada';
    }
    
    public function broadcastWith()
    {
        return [
            'despacho_id' => $this->despacho->id,
            'ambulancia_id' => $this->despacho->ambulancia_id,
            'latitud' => $this->ubicacion['lat'],
            'longitud' => $this->ubicacion['lng'],
            'velocidad' => $this->ubicacion['velocidad'],
            'timestamp' => now()->toIso8601String()
        ];
    }
}

// Controller: RastreoController.php

public function actualizarUbicacion(Request $request, $despachoId)
{
    $despacho = Despacho::findOrFail($despachoId);
    
    // Guardar en historial
    HistorialRastreo::create([
        'despacho_id' => $despacho->id,
        'latitud' => $request->lat,
        'longitud' => $request->lng,
        'velocidad' => $request->velocidad,
        'timestamp' => now()
    ]);
    
    // Actualizar ubicación ambulancia
    $despacho->ambulancia->update([
        'ubicacion_actual_lat' => $request->lat,
        'ubicacion_actual_lng' => $request->lng,
        'ultima_actualizacion' => now()
    ]);
    
    // Calcular distancia restante
    $distanciaRestante = $this->gpsService->calcularDistancia(
        $request->lat,
        $request->lng,
        $despacho->ubicacion_origen_lat,
        $despacho->ubicacion_origen_lng
    );
    
    // Actualizar estado
    EstadoDespacho::updateOrCreate(
        ['despacho_id' => $despacho->id],
        [
            'distancia_restante' => $distanciaRestante,
            'tiempo_ultima_actualizacion' => now()
        ]
    );
    
    // Broadcast via WebSocket
    broadcast(new UbicacionActualizada($despacho, [
        'lat' => $request->lat,
        'lng' => $request->lng,
        'velocidad' => $request->velocidad
    ]));
    
    return response()->json(['success' => true]);
}
```

## 🔌 APIs Expuestas

### GraphQL Schema

```graphql
type Query {
  ambulancias(estado: EstadoAmbulancia): [Ambulancia!]!
  ambulancia(id: ID!): Ambulancia
  despacho(id: ID!): Despacho
  despachos(estado: EstadoDespacho, fecha: Date): [Despacho!]!
  personal(disponible: Boolean): [Personal!]!
}

type Mutation {
  crearDespacho(input: CrearDespachoInput!): Despacho!
  actualizarEstadoDespacho(id: ID!, estado: EstadoDespacho!): Despacho!
  finalizarDespacho(id: ID!, tiempoReal: Int!): Despacho!
  actualizarUbicacionAmbulancia(ambulanciaId: ID!, lat: Float!, lng: Float!): Ambulancia!
}

type Subscription {
  despachoActualizado(id: ID!): Despacho!
  ubicacionActualizada(despachoId: ID!): UbicacionRastreo!
}
```

### REST API

```
POST   /api/despachos                    # Crear despacho
GET    /api/despachos/{id}               # Obtener despacho
PUT    /api/despachos/{id}/estado        # Actualizar estado
POST   /api/despachos/{id}/rastreo       # Actualizar ubicación
GET    /api/ambulancias/disponibles      # Listar disponibles
POST   /api/ml/entrenar                  # Entrenar modelo ML
GET    /api/ml/prediccion                # Obtener predicción
```

## 🔐 Seguridad

1. **JWT**: Autenticación entre microservicios
2. **Rate Limiting**: Limitar requests por IP/token
3. **CORS**: Configurar origins permitidos
4. **Validación**: Validar todos los inputs
5. **Logs**: Registrar todas las operaciones críticas

## 📈 Métricas y Monitoreo

- Tiempo promedio de asignación
- Precisión de predicción ML
- Ambulancias disponibles en tiempo real
- Tasa de éxito de despachos
- Latencia de WebSocket

## 🧪 Testing

```bash
# Unit Tests
tests/Unit/Services/GpsServiceTest.php
tests/Unit/Services/MLPredictionServiceTest.php

# Feature Tests
tests/Feature/DespachoTest.php
tests/Feature/RastreoTest.php
tests/Feature/GraphQL/DespachoMutationTest.php
```
