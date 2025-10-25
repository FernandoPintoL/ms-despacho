# 🚑 Microservicio de Despacho de Ambulancias

Sistema inteligente de asignación y rastreo de ambulancias basado en geolocalización GPS y Machine Learning.

## 📋 Descripción

El MS Despacho es responsable de:
- **Asignación inteligente** de ambulancias basada en proximidad GPS
- **Predicción de tiempo de llegada** usando Machine Learning supervisado
- **Rastreo en tiempo real** de ambulancias mediante WebSocket
- **Gestión de personal** médico y paramédico
- **Integración** con otros microservicios (Recepción, Decisión, Auth)

## 🏗️ Arquitectura

### Stack Tecnológico

**MS Despacho (Laravel 12):**
- **Backend**: PHP 8.2+ con Laravel 12
- **Frontend**: React 19 + Inertia.js + TailwindCSS
- **Base de Datos**: MySQL/PostgreSQL
- **Cache/Queue**: Redis
- **GraphQL**: rebing/graphql-laravel
- **GPS**: mjaschen/phpgeo
- **Auth**: Laravel Sanctum (compartido con MS Auth)

**MS WebSocket (Node.js):**
- **Framework**: Express + Socket.IO
- **Pub/Sub**: Redis
- **Puerto**: 3000

**ML Service (Python):**
- **Framework**: Flask
- **ML**: scikit-learn, numpy, pandas
- **Puerto**: 5000

### Componentes Principales

```
MS-DESPACHO/
├── Services/
│   ├── DespachoService       # Lógica principal de despacho
│   ├── AsignacionService     # Algoritmo de asignación óptima
│   ├── GpsService            # Cálculos de distancia GPS
│   └── MLPredictionService   # Predicción de tiempos con ML
├── GraphQL/                  # API GraphQL
├── Events/                   # WebSocket events
└── Jobs/                     # Tareas asíncronas
```

## 🚀 Instalación Rápida

### Requisitos Previos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL/PostgreSQL
- Redis (opcional, recomendado)

### Pasos de Instalación

```bash
# 1. Clonar repositorio
git clone <repo-url>
cd ms-despacho

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias Node
npm install

# 4. Configurar entorno
cp .env.example .env
php artisan key:generate

# 5. Configurar base de datos en .env
# DB_CONNECTION=mysql
# DB_DATABASE=ms_despacho
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Ejecutar migraciones
php artisan migrate

# 7. Seeders (datos de prueba)
php artisan db:seed

# 8. Generar JWT secret
php artisan jwt:secret

# 9. Iniciar servicios
php artisan serve --port=8001
```

## 📦 Instalación de Paquetes

### MS Despacho (Laravel)
```bash
# GraphQL
composer require rebing/graphql-laravel
php artisan vendor:publish --provider="Rebing\GraphQL\GraphQLServiceProvider"

# GPS y Geolocalización
composer require mjaschen/phpgeo

# Sanctum Authentication
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Redis
composer require predis/predis
```

### MS WebSocket (Node.js)
```bash
cd ../ms-websocket
npm install express socket.io redis cors dotenv
```

### ML Service (Python)
```bash
cd ../ml-service
pip install flask scikit-learn numpy pandas joblib python-dotenv flask-cors
```

## 🔧 Configuración

### MS Despacho (.env)

```env
# Aplicación
APP_NAME="MS Despacho"
APP_URL=http://localhost:8001

# Base de Datos (SQL Server)
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=ms_despacho
DB_USERNAME=sa
DB_PASSWORD=
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=true

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000

# Queue
QUEUE_CONNECTION=redis

# Redis (Queue y Pub/Sub)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Otros Microservicios
MS_RECEPCION_URL=http://localhost:8000
MS_DECISION_URL=http://localhost:8002
MS_AUTH_URL=http://localhost:8003
MS_WEBSOCKET_URL=http://localhost:3000
ML_SERVICE_URL=http://localhost:5000

# GPS
GPS_DISTANCE_UNIT=km
GPS_MAX_DISTANCE=50
```

### MS WebSocket (.env)

```env
PORT=3000
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
LARAVEL_API_URL=http://localhost:8001
CORS_ORIGIN=*
```

### ML Service (.env)

```env
FLASK_APP=app.py
FLASK_ENV=development
PORT=5000
MODEL_PATH=models/tiempo_llegada.pkl
```

## 🎯 Funcionalidades Principales

### 1. Asignación de Ambulancia

El sistema selecciona automáticamente la ambulancia más cercana disponible:

```php
// Ejemplo de uso
$despacho = DespachoService::asignarAmbulancia([
    'solicitud_id' => 123,
    'ubicacion_lat' => -33.4569,
    'ubicacion_lng' => -70.6483,
    'tipo_emergencia' => 'accidente'
]);
```

**Algoritmo:**
1. Filtra ambulancias disponibles por tipo
2. Calcula distancia GPS (Haversine) a cada ambulancia
3. Ordena por distancia ascendente
4. Selecciona la más cercana
5. Predice tiempo de llegada con ML
6. Asigna personal disponible
7. Notifica via WebSocket

### 2. Predicción de Tiempo con ML

Machine Learning supervisado para estimar tiempo de llegada:

```php
// Features utilizados
$tiempoEstimado = MLPredictionService::predecirTiempoLlegada([
    'distancia' => 5.2,           // km
    'hora_dia' => 14,             // 0-23
    'dia_semana' => 3,            // 0-6 (lunes-domingo)
    'tipo_ambulancia' => 'avanzada',
    'trafico_estimado' => 0.7     // 0-1
]);
```

**Entrenamiento del modelo:**
```bash
php artisan ml:entrenar
```

### 3. Rastreo en Tiempo Real

WebSocket con Node.js y Socket.IO:

```javascript
// App Flutter - Conectar y escuchar
import 'package:socket_io_client/socket_io_client.dart' as IO;

IO.Socket socket = IO.io('http://localhost:3000');

socket.on('connect', (_) {
  socket.emit('join', {'despacho_id': 123, 'user_type': 'paramedico'});
});

socket.on('ubicacion.actualizada', (data) {
  print('Nueva ubicación: ${data['latitud']}, ${data['longitud']}');
  actualizarMapa(data);
});

// Enviar ubicación
socket.emit('actualizar.ubicacion', {
  'despacho_id': 123,
  'lat': -33.4569,
  'lng': -70.6483,
  'velocidad': 60,
  'token': authToken
});
```

```php
// Laravel - Publicar evento a Redis
use Illuminate\Support\Facades\Redis;

Redis::publish('despacho-events', json_encode([
    'event' => 'ubicacion.actualizada',
    'despacho_id' => $despacho->id,
    'latitud' => -33.4569,
    'longitud' => -70.6483,
    'velocidad' => 60
]));

// Node.js captura el evento y lo envía via Socket.IO
```

## 🔌 APIs

### GraphQL

**Endpoint:** `http://localhost:8001/graphql`

**Ejemplo de Mutation:**
```graphql
mutation {
  crearDespacho(input: {
    solicitudId: 123
    ubicacionOrigen: {
      lat: -33.4569
      lng: -70.6483
    }
    tipoEmergencia: "accidente"
  }) {
    id
    ambulanciaAsignada {
      id
      placa
      tipo
    }
    tiempoEstimado
  }
}
```

**Ejemplo de Query:**
```graphql
query {
  despacho(id: 123) {
    id
    ambulancia {
      placa
      ubicacionActual {
        lat
        lng
      }
    }
    estado
    distanciaRestante
  }
}
```

### REST API

**Base URL:** `http://localhost:8001/api`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/despachos` | Crear despacho |
| GET | `/despachos/{id}` | Obtener despacho |
| PUT | `/despachos/{id}/estado` | Actualizar estado |
| POST | `/despachos/{id}/rastreo` | Actualizar ubicación GPS |
| GET | `/ambulancias/disponibles` | Listar ambulancias disponibles |
| POST | `/ml/entrenar` | Entrenar modelo ML |

**Ejemplo de Request:**
```bash
curl -X POST http://localhost:8001/api/despachos \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "solicitud_id": 123,
    "ubicacion_lat": -33.4569,
    "ubicacion_lng": -70.6483,
    "tipo_emergencia": "accidente"
  }'
```

## 🗄️ Modelo de Datos

### Tablas Principales

- **ambulancias**: Vehículos de emergencia
- **personal**: Paramédicos, conductores, médicos
- **despachos**: Registros de despacho
- **asignacion_personal**: Relación despacho-personal
- **historial_rastreo**: Tracking GPS histórico
- **estado_despacho**: Estado actual de cada despacho

Ver diagrama completo en la imagen del proyecto.

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Tests específicos
php artisan test --filter=DespachoTest

# Con coverage
php artisan test --coverage
```

## 🚀 Desarrollo

### Iniciar Servicios

```bash
# Opción 1: Comando único (recomendado)
composer dev

# Opción 2: Servicios individuales
# Terminal 1: Servidor Laravel
php artisan serve --port=8001

# Terminal 2: Queue Worker
php artisan queue:work

# Terminal 3: WebSocket Server
php artisan reverb:start

# Terminal 4: Frontend (Vite)
npm run dev
```

### Comandos Útiles

```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Generar código
php artisan make:model NombreModelo -m
php artisan make:controller NombreController
php artisan make:service NombreService

# Migraciones
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed

# ML
php artisan ml:entrenar
php artisan ml:evaluar
```

## 📊 Monitoreo y Métricas

El sistema registra:
- ✅ Tiempo promedio de asignación
- ✅ Precisión de predicción ML (MAE, RMSE)
- ✅ Ambulancias disponibles en tiempo real
- ✅ Tasa de éxito de despachos
- ✅ Latencia de WebSocket

## 🔐 Seguridad

- **JWT**: Autenticación entre microservicios
- **CORS**: Configurado para origins permitidos
- **Rate Limiting**: 60 requests/minuto por IP
- **Validación**: Todos los inputs validados
- **Logs**: Registro de operaciones críticas

## 📚 Documentación Adicional

- [INSTALACION.md](./INSTALACION.md) - Guía detallada de instalación
- [ARQUITECTURA.md](./ARQUITECTURA.md) - Arquitectura técnica completa
- [PLAN_IMPLEMENTACION.md](./PLAN_IMPLEMENTACION.md) - Plan de desarrollo por fases

## 🤝 Integración con Otros Microservicios

### MS Recepción
- **Recibe**: Solicitudes de despacho desde n8n/WhatsApp
- **Envía**: Confirmación de asignación

### MS Decisión
- **Recibe**: Datos de despacho y evaluación paramédica
- **Envía**: Decisión (ambulatoria/traslado) y hospital destino

### MS Auth
- **Recibe**: Tokens JWT para validación
- **Envía**: Información de usuarios autenticados

## 🐛 Troubleshooting

### Error: "No ambulancias disponibles"
- Verificar que existan ambulancias con estado "disponible" en BD
- Ejecutar: `php artisan db:seed --class=AmbulanciaSeeder`

### Error: "ML model not found"
- Entrenar modelo: `php artisan ml:entrenar`
- Verificar que existan datos históricos en tabla `despachos`

### WebSocket no conecta
- Verificar que Reverb esté corriendo: `php artisan reverb:start`
- Revisar configuración en `.env`: `BROADCAST_DRIVER=reverb`

## 📄 Licencia

MIT License

## 👥 Equipo

Desarrollado para el sistema de despacho de ambulancias - SWII

## 📞 Soporte

Para dudas o problemas, contactar al equipo de desarrollo.

---

**Versión:** 1.0.0  
**Última actualización:** Octubre 2025
