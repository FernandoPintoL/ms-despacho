# 🏗️ Arquitectura Actualizada - Sistema de Despacho de Ambulancias

## 📊 Diagrama de Arquitectura

```
┌─────────────────────────────────────────────────────────────────────┐
│                    SISTEMA DE DESPACHO DE AMBULANCIAS                │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│ MS Recepción │────▶│ MS Despacho  │────▶│ MS Decisión  │
│  (Laravel)   │     │  (Laravel)   │     │  (Laravel)   │
│  Puerto 8000 │     │  Puerto 8001 │     │  Puerto 8002 │
└──────────────┘     └──────┬───────┘     └──────────────┘
                            │
                    ┌───────┴───────┐
                    │               │
            ┌───────▼──────┐  ┌─────▼────────┐
            │  ML Service  │  │ MS WebSocket │
            │   (Python)   │  │   (Node.js)  │
            │  Puerto 5000 │  │  Puerto 3000 │
            └──────────────┘  └──────┬───────┘
                                     │
                              ┌──────▼──────┐
                              │ App Flutter │
                              │  (Clientes) │
                              └─────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      MS Autenticación (Sanctum)                      │
│                    Puerto 8003 - Valida todos los MS                 │
└─────────────────────────────────────────────────────────────────────┘

                    ┌──────────────┐
                    │    Redis     │
                    │  Pub/Sub +   │
                    │ Queue/Cache  │
                    └──────────────┘
```

## 🔄 Cambios Principales

### ❌ Eliminado
- **JWT (tymon/jwt-auth)**: Reemplazado por Sanctum
- **Laravel Reverb/WebSockets**: Reemplazado por MS WebSocket (Node.js)
- **PHP-ML**: Reemplazado por ML Service (Python)

### ✅ Agregado
- **Laravel Sanctum**: Autenticación compartida con MS Auth
- **MS WebSocket (Node.js)**: Socket.IO + Redis Pub/Sub
- **ML Service (Python)**: Flask + scikit-learn

## 🎯 Responsabilidades por Servicio

### 1. MS Despacho (Laravel 12)

**Puerto:** 8001

**Responsabilidades:**
- ✅ Gestión de ambulancias (CRUD)
- ✅ Gestión de personal médico
- ✅ Algoritmo de asignación (ambulancia más cercana)
- ✅ Cálculo de distancias GPS (Haversine)
- ✅ Registro de despachos
- ✅ API GraphQL para consultas complejas
- ✅ API REST para webhooks
- ✅ Publicar eventos a Redis

**Stack:**
- Laravel 12 (PHP 8.2+)
- MySQL/PostgreSQL
- Redis (Queue + Pub/Sub)
- GraphQL (rebing/graphql-laravel)
- GPS (mjaschen/phpgeo)

**Endpoints principales:**
- `POST /api/despachos` - Crear despacho
- `GET /api/despachos/{id}` - Obtener despacho
- `GET /api/ambulancias/disponibles` - Listar disponibles
- `POST /graphql` - Consultas GraphQL

### 2. MS WebSocket (Node.js)

**Puerto:** 3000

**Responsabilidades:**
- ✅ Comunicación en tiempo real con clientes
- ✅ Rastreo GPS de ambulancias
- ✅ Notificaciones push a paramédicos
- ✅ Gestión de rooms por despacho
- ✅ Escuchar eventos de Redis (Laravel)
- ✅ Broadcast a clientes conectados

**Stack:**
- Node.js + Express
- Socket.IO
- Redis (Subscriber)

**Eventos:**
- `join` - Unirse a canal de despacho
- `actualizar.ubicacion` - Enviar ubicación GPS
- `ubicacion.actualizada` - Broadcast nueva ubicación
- `ambulancia.asignada` - Notificar asignación
- `estado.cambiado` - Cambio de estado

**Archivos:**
- `server.js` - Servidor principal
- Ver: `MS_WEBSOCKET_CODE.md`

### 3. ML Service (Python)

**Puerto:** 5000

**Responsabilidades:**
- ✅ Predicción de tiempo de llegada
- ✅ Entrenamiento de modelos ML
- ✅ Evaluación de precisión
- ✅ Reentrenamiento periódico
- ✅ Generación de datos sintéticos (desarrollo)

**Stack:**
- Flask
- scikit-learn (RandomForestRegressor)
- numpy, pandas
- joblib (persistencia de modelos)

**Endpoints:**
- `POST /predict` - Predecir tiempo
- `POST /train` - Entrenar modelo
- `GET /evaluate` - Evaluar modelo
- `GET /health` - Estado del servicio

**Features del modelo:**
1. Distancia (km)
2. Hora del día (0-23)
3. Día de la semana (0-6)
4. Tipo de ambulancia (0-3)
5. Tráfico estimado (0-1)

**Archivos:**
- `app.py` - API Flask
- Ver: `ML_SERVICE_CODE.md`

### 4. MS Autenticación (Sanctum)

**Puerto:** 8003 (gestionado por otro equipo)

**Responsabilidades:**
- ✅ Autenticación de usuarios
- ✅ Generación de tokens Sanctum
- ✅ Validación de tokens
- ✅ Gestión de permisos

**Integración con MS Despacho:**
```php
// Validar token llamando a MS Auth
$response = Http::withToken($token)
    ->get(env('MS_AUTH_URL') . '/api/user');

if ($response->successful()) {
    $user = $response->json();
}
```

## 🔄 Flujo de Datos Completo

### Escenario: Solicitud de Ambulancia

```
1. Cliente WhatsApp → n8n → MS Recepción
   ├─ Solicita ambulancia
   └─ Envía ubicación GPS

2. MS Recepción → GraphQL → MS Despacho
   ├─ solicitud_id: 123
   ├─ ubicacion: {lat, lng}
   └─ tipo_emergencia: "accidente"

3. MS Despacho procesa:
   ├─ Consulta ambulancias disponibles (BD)
   ├─ Calcula distancias GPS (GpsService)
   ├─ Selecciona más cercana (AsignacionService)
   ├─ Llama a ML Service → Predice tiempo
   ├─ Asigna personal disponible
   ├─ Crea registro en BD
   └─ Publica evento a Redis

4. Redis Pub/Sub → MS WebSocket
   └─ Evento: "ambulancia.asignada"

5. MS WebSocket → Socket.IO → App Flutter
   ├─ Notifica a paramédico
   └─ Muestra datos del despacho

6. App Flutter → Socket.IO → MS WebSocket
   ├─ Envía ubicación GPS cada 5 seg
   └─ Evento: "actualizar.ubicacion"

7. MS WebSocket → HTTP → MS Despacho
   ├─ POST /api/despachos/{id}/rastreo
   └─ Guarda en historial_rastreo

8. MS Despacho → Redis → MS WebSocket
   └─ Broadcast a todos los clientes

9. Paramédico llega → App Flutter → MS Despacho
   ├─ Registra evaluación
   └─ Envía datos a MS Decisión

10. MS Decisión → ML (CNN + K-means)
    ├─ Analiza severidad
    ├─ Decide: ambulatoria o traslado
    └─ Si traslado: selecciona hospital

11. MS Decisión → MS Despacho
    └─ Actualiza ruta si es traslado

12. MS Despacho → Redis → MS WebSocket
    └─ Notifica cambio de destino

13. Ambulancia llega → Finaliza despacho
    ├─ Registra tiempo real
    └─ Envía datos a ML Service para reentrenamiento
```

## 🔐 Autenticación y Seguridad

### Flujo de Autenticación

```
1. Usuario → MS Auth
   ├─ POST /api/login
   └─ Recibe token Sanctum

2. Cliente → MS Despacho
   ├─ Header: Authorization: Bearer {token}
   └─ MS Despacho valida con MS Auth

3. MS Despacho → MS Auth
   ├─ GET /api/user (con token)
   └─ Recibe datos del usuario

4. Si válido:
   └─ Procesa request
   
5. Si inválido:
   └─ 401 Unauthorized
```

### Middleware de Validación

```php
// app/Http/Middleware/ValidateSanctumToken.php

public function handle($request, $next)
{
    $token = $request->bearerToken();
    
    if (!$token) {
        return response()->json(['error' => 'Token requerido'], 401);
    }
    
    // Validar con MS Auth
    $response = Http::withToken($token)
        ->get(env('MS_AUTH_URL') . '/api/user');
    
    if ($response->failed()) {
        return response()->json(['error' => 'Token inválido'], 401);
    }
    
    $request->merge(['user' => $response->json()]);
    
    return $next($request);
}
```

## 📡 Comunicación Entre Servicios

### 1. Laravel → Node.js (Redis Pub/Sub)

```php
// Laravel
use Illuminate\Support\Facades\Redis;

Redis::publish('despacho-events', json_encode([
    'event' => 'ubicacion.actualizada',
    'despacho_id' => 123,
    'latitud' => -33.4569,
    'longitud' => -70.6483
]));
```

```javascript
// Node.js
subscriber.subscribe('despacho-events');

subscriber.on('message', (channel, message) => {
    const data = JSON.parse(message);
    io.to(`despacho.${data.despacho_id}`).emit(data.event, data);
});
```

### 2. Laravel → Python (HTTP)

```php
// Laravel
$response = Http::post(env('ML_SERVICE_URL') . '/predict', [
    'distancia' => 10.5,
    'hora_dia' => 14,
    'dia_semana' => 3,
    'tipo_ambulancia' => 'avanzada',
    'trafico_estimado' => 0.7
]);

$tiempoEstimado = $response->json()['tiempo_estimado'];
```

```python
# Python
@app.route('/predict', methods=['POST'])
def predict():
    data = request.json
    features = prepare_features(data)
    prediction = model.predict(features)[0]
    return jsonify({'tiempo_estimado': int(prediction)})
```

### 3. Flutter → Node.js (Socket.IO)

```dart
// Flutter
import 'package:socket_io_client/socket_io_client.dart' as IO;

IO.Socket socket = IO.io('http://localhost:3000');

socket.on('connect', (_) {
  socket.emit('join', {'despacho_id': 123});
});

socket.on('ubicacion.actualizada', (data) {
  print('Nueva ubicación: ${data['latitud']}');
});

socket.emit('actualizar.ubicacion', {
  'despacho_id': 123,
  'lat': -33.4569,
  'lng': -70.6483,
  'velocidad': 60
});
```

## 📊 Base de Datos (MS Despacho)

### Tablas Principales

```sql
-- ambulancias
CREATE TABLE ambulancias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    placa VARCHAR(10) UNIQUE,
    modelo VARCHAR(50),
    tipo_ambulancia ENUM('basica', 'intermedia', 'avanzada', 'uci'),
    estado ENUM('disponible', 'en_servicio', 'mantenimiento', 'fuera_servicio'),
    caracteristicas JSON,
    ubicacion_actual_lat DECIMAL(10,8),
    ubicacion_actual_lng DECIMAL(11,8),
    ultima_actualizacion TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- personal
CREATE TABLE personal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100),
    apellido VARCHAR(100),
    ci VARCHAR(20) UNIQUE,
    rol ENUM('paramedico', 'conductor', 'medico', 'enfermero'),
    especialidad VARCHAR(100),
    experiencia INT,
    estado ENUM('disponible', 'en_servicio', 'descanso', 'vacaciones'),
    telefono VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- despachos
CREATE TABLE despachos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    solicitud_id INT,
    ambulancia_id INT,
    fecha DATETIME,
    ubicacion_origen_lat DECIMAL(10,8),
    ubicacion_origen_lng DECIMAL(11,8),
    ubicacion_destino_lat DECIMAL(10,8),
    ubicacion_destino_lng DECIMAL(11,8),
    distancia_km DECIMAL(6,2),
    tiempo_estimado_min INT,
    tiempo_real_min INT,
    resultado_final ENUM('completado', 'cancelado', 'redirigido'),
    incidente ENUM('accidente', 'emergencia_medica', 'traslado'),
    decision ENUM('ambulatoria', 'traslado'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (ambulancia_id) REFERENCES ambulancias(id)
);

-- historial_rastreo
CREATE TABLE historial_rastreo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    despacho_id INT,
    latitud DECIMAL(10,8),
    longitud DECIMAL(11,8),
    velocidad DECIMAL(5,2),
    timestamp TIMESTAMP,
    created_at TIMESTAMP,
    FOREIGN KEY (despacho_id) REFERENCES despachos(id)
);
```

## 🚀 Comandos de Inicio

### Iniciar todos los servicios

```bash
# Terminal 1: MS Despacho (Laravel)
cd ms-despacho
php artisan serve --port=8001

# Terminal 2: Queue Worker (Laravel)
cd ms-despacho
php artisan queue:work

# Terminal 3: MS WebSocket (Node.js)
cd ms-websocket
node server.js

# Terminal 4: ML Service (Python)
cd ml-service
python app.py

# Terminal 5: Frontend (opcional)
cd ms-despacho
npm run dev
```

### Verificar servicios

```bash
# MS Despacho
curl http://localhost:8001/api/health

# MS WebSocket
curl http://localhost:3000/health

# ML Service
curl http://localhost:5000/health
```

## 📈 Ventajas de esta Arquitectura

### ✅ Separación de Responsabilidades
- Cada servicio tiene una función específica
- Más fácil de mantener y escalar

### ✅ Tecnología Apropiada
- **Laravel**: Lógica de negocio y BD
- **Node.js**: WebSocket en tiempo real
- **Python**: Machine Learning avanzado

### ✅ Escalabilidad
- Cada servicio puede escalar independientemente
- WebSocket puede tener múltiples instancias

### ✅ Desarrollo Paralelo
- Equipos diferentes pueden trabajar en cada servicio
- Menos conflictos de código

### ✅ Resiliencia
- Si un servicio falla, los demás siguen funcionando
- Fallbacks implementados (ej: estimación básica si ML falla)

## 📚 Documentación de Referencia

- **INSTALACION.md**: Guía de instalación completa
- **MS_WEBSOCKET_CODE.md**: Código completo del servicio WebSocket
- **ML_SERVICE_CODE.md**: Código completo del servicio ML
- **ARQUITECTURA.md**: Arquitectura técnica detallada (original)
- **PLAN_IMPLEMENTACION.md**: Plan de desarrollo por fases
- **README.md**: Guía principal del proyecto

## 🎯 Próximos Pasos

1. ✅ Documentación actualizada
2. ⏳ Crear migraciones y modelos (Laravel)
3. ⏳ Implementar servicios core (GPS, Asignación)
4. ⏳ Crear servidor WebSocket (Node.js)
5. ⏳ Crear servicio ML (Python)
6. ⏳ Integrar todos los servicios
7. ⏳ Testing end-to-end
8. ⏳ Deployment

---

**Última actualización:** Octubre 2025  
**Versión:** 2.0 (Arquitectura de Microservicios)
