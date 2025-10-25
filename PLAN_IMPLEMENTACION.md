# Plan de Implementación - MS Despacho

## 📋 Resumen Ejecutivo

Este documento detalla el plan de implementación del Microservicio de Despacho de Ambulancias, que incluye:
- Asignación inteligente de ambulancias basada en GPS
- Machine Learning para predicción de tiempos de llegada
- Rastreo en tiempo real con WebSocket
- Integración con otros microservicios vía GraphQL y REST

## 🎯 Objetivos del Microservicio

1. **Asignación Óptima**: Seleccionar la ambulancia más cercana disponible
2. **Predicción Precisa**: Estimar tiempo de llegada con ML
3. **Rastreo Real-Time**: Seguimiento GPS continuo de ambulancias
4. **Integración Seamless**: Comunicación fluida con MS Recepción, MS Decisión, MS Auth

## 📦 Stack Tecnológico

### Backend
- **Framework**: Laravel 12 (PHP 8.2+)
- **Base de Datos**: MySQL/PostgreSQL
- **Cache/Queue**: Redis
- **GraphQL**: rebing/graphql-laravel
- **WebSocket**: Laravel Reverb (oficial)
- **ML**: php-ai/php-ml o Rubix ML
- **GPS**: mjaschen/phpgeo
- **Auth**: tymon/jwt-auth

### Frontend (Dashboard Admin - Opcional)
- **Framework**: React 19 (ya incluido con Inertia.js)
- **UI**: TailwindCSS + shadcn/ui
- **Mapas**: Leaflet / React-Leaflet
- **WebSocket**: Laravel Echo + Pusher

## 🗓️ Fases de Implementación

### **FASE 1: Configuración Base (1-2 días)**

#### 1.1 Instalación de Dependencias
```bash
# Instalar paquetes PHP
composer require rebing/graphql-laravel
composer require mjaschen/phpgeo
composer require php-ai/php-ml
composer require tymon/jwt-auth
composer require predis/predis

# Publicar configuraciones
php artisan vendor:publish --provider="Rebing\GraphQL\GraphQLServiceProvider"
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

#### 1.2 Configuración de Base de Datos
- Cambiar de SQLite a MySQL en `.env`
- Configurar credenciales de BD
- Configurar Redis para cache y queue

#### 1.3 Estructura de Carpetas
```bash
mkdir -p app/Services
mkdir -p app/GraphQL/{Queries,Mutations,Types}
mkdir -p app/Events
mkdir -p app/Jobs
mkdir -p storage/ml_models
mkdir -p tests/Unit/Services
mkdir -p tests/Feature/GraphQL
```

### **FASE 2: Modelos y Migraciones (2-3 días)**

#### 2.1 Crear Migraciones
```bash
php artisan make:migration create_ambulancias_table
php artisan make:migration create_personal_table
php artisan make:migration create_despachos_table
php artisan make:migration create_asignacion_personal_table
php artisan make:migration create_historial_rastreo_table
php artisan make:migration create_estado_despacho_table
```

#### 2.2 Crear Modelos
```bash
php artisan make:model Ambulancia
php artisan make:model Personal
php artisan make:model Despacho
php artisan make:model AsignacionPersonal
php artisan make:model HistorialRastreo
php artisan make:model EstadoDespacho
```

#### 2.3 Definir Relaciones
- Ambulancia hasMany Despachos
- Despacho belongsTo Ambulancia
- Despacho hasMany AsignacionPersonal
- Despacho hasMany HistorialRastreo
- Personal hasMany AsignacionPersonal

#### 2.4 Seeders
```bash
php artisan make:seeder AmbulanciaSeeder
php artisan make:seeder PersonalSeeder
```

### **FASE 3: Servicios Core (3-4 días)**

#### 3.1 GpsService
```bash
php artisan make:class Services/GpsService
```
**Funcionalidades:**
- `calcularDistancia($lat1, $lng1, $lat2, $lng2)` - Haversine
- `calcularRuta($origen, $destino)` - Ruta óptima
- `validarCoordenadas($lat, $lng)` - Validación

#### 3.2 AsignacionService
```bash
php artisan make:class Services/AsignacionService
```
**Funcionalidades:**
- `obtenerAmbulanciasDisponibles($tipoEmergencia)`
- `calcularAmbulanciaOptima($ambulancias, $ubicacion)`
- `asignarPersonal($ambulancia, $despacho)`
- `validarDisponibilidad($ambulancia)`

#### 3.3 DespachoService
```bash
php artisan make:class Services/DespachoService
```
**Funcionalidades:**
- `crearDespacho($solicitud)`
- `actualizarEstado($despachoId, $estado)`
- `finalizarDespacho($despachoId, $tiempoReal)`
- `obtenerDespachoActivo($ambulanciaId)`

#### 3.4 MLPredictionService
```bash
php artisan make:class Services/MLPredictionService
```
**Funcionalidades:**
- `predecirTiempoLlegada($features)`
- `entrenarModelo()`
- `evaluarModelo()`
- `obtenerMetricas()`

### **FASE 4: GraphQL API (2-3 días)**

#### 4.1 Definir Types
```bash
php artisan make:graphql:type Ambulancia
php artisan make:graphql:type Despacho
php artisan make:graphql:type Personal
php artisan make:graphql:type EstadoDespacho
```

#### 4.2 Crear Queries
```bash
php artisan make:graphql:query AmbulanciasQuery
php artisan make:graphql:query DespachoQuery
php artisan make:graphql:query DespachosQuery
php artisan make:graphql:query PersonalQuery
```

#### 4.3 Crear Mutations
```bash
php artisan make:graphql:mutation CrearDespacho
php artisan make:graphql:mutation ActualizarEstadoDespacho
php artisan make:graphql:mutation FinalizarDespacho
php artisan make:graphql:mutation ActualizarUbicacionAmbulancia
```

#### 4.4 Configurar Schema
Editar `config/graphql.php` para registrar types, queries y mutations

### **FASE 5: WebSocket / Broadcasting (2 días)**

#### 5.1 Configurar Laravel Reverb
```bash
php artisan reverb:install
```

#### 5.2 Crear Events
```bash
php artisan make:event AmbulanciaAsignada
php artisan make:event UbicacionActualizada
php artisan make:event DespachoFinalizado
php artisan make:event EstadoCambiado
```

#### 5.3 Configurar Channels
Editar `routes/channels.php` para definir canales privados

#### 5.4 Frontend WebSocket
```javascript
// resources/js/echo.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
});

// Escuchar eventos
window.Echo.channel('despacho.123')
    .listen('.ubicacion.actualizada', (e) => {
        console.log('Nueva ubicación:', e);
    });
```

### **FASE 6: Machine Learning (3-4 días)**

#### 6.1 Recolección de Datos
- Crear comando para generar datos sintéticos iniciales
- Definir features: distancia, hora, día, tipo ambulancia, tráfico

#### 6.2 Entrenamiento del Modelo
```bash
php artisan make:command EntrenarModeloML
```

#### 6.3 Evaluación y Ajuste
- Implementar métricas: MAE, RMSE, R²
- Ajustar hiperparámetros
- Validación cruzada

#### 6.4 Integración
- Cargar modelo en MLPredictionService
- Endpoint para reentrenar modelo
- Logging de predicciones vs realidad

### **FASE 7: REST API (1-2 días)**

#### 7.1 Crear Controllers
```bash
php artisan make:controller Api/DespachoController
php artisan make:controller Api/AmbulanciaController
php artisan make:controller Api/RastreoController
php artisan make:controller Api/MLController
```

#### 7.2 Definir Rutas
```php
// routes/api.php
Route::middleware('jwt.auth')->group(function () {
    Route::apiResource('despachos', DespachoController::class);
    Route::post('despachos/{id}/rastreo', [RastreoController::class, 'actualizar']);
    Route::get('ambulancias/disponibles', [AmbulanciaController::class, 'disponibles']);
    Route::post('ml/entrenar', [MLController::class, 'entrenar']);
});
```

#### 7.3 Validación
```bash
php artisan make:request CrearDespachoRequest
php artisan make:request ActualizarRastreoRequest
```

### **FASE 8: Integración con Otros MS (2-3 días)**

#### 8.1 Cliente HTTP
```bash
php artisan make:class Services/MicroserviceClient
```

#### 8.2 Comunicación con MS Recepción
- Recibir solicitudes de despacho
- Enviar confirmación de asignación

#### 8.3 Comunicación con MS Decisión
- Enviar datos de despacho
- Recibir decisión (ambulatoria/traslado)
- Actualizar ruta si es traslado

#### 8.4 Comunicación con MS Auth
- Validar tokens JWT
- Obtener información de usuarios

#### 8.5 Webhooks
```bash
php artisan make:controller WebhookController
```

### **FASE 9: Testing (2-3 días)**

#### 9.1 Unit Tests
```bash
php artisan make:test Unit/Services/GpsServiceTest --unit
php artisan make:test Unit/Services/AsignacionServiceTest --unit
php artisan make:test Unit/Services/MLPredictionServiceTest --unit
```

#### 9.2 Feature Tests
```bash
php artisan make:test Feature/DespachoTest
php artisan make:test Feature/RastreoTest
php artisan make:test Feature/GraphQL/DespachoMutationTest
```

#### 9.3 Integration Tests
- Probar comunicación con otros MS
- Probar WebSocket
- Probar GraphQL

### **FASE 10: Dashboard Admin (3-4 días) - OPCIONAL**

#### 10.1 Vistas React
```bash
# Crear componentes
resources/js/Pages/Despachos/Index.tsx
resources/js/Pages/Despachos/Show.tsx
resources/js/Pages/Ambulancias/Index.tsx
resources/js/Pages/Mapa/Rastreo.tsx
```

#### 10.2 Mapa en Tiempo Real
```bash
npm install leaflet react-leaflet
```

#### 10.3 Dashboard Métricas
- Ambulancias disponibles
- Despachos activos
- Tiempo promedio de respuesta
- Gráficos de rendimiento

### **FASE 11: Deployment (1-2 días)**

#### 11.1 Configuración Producción
- Optimizar autoloader
- Cache de configuración
- Cache de rutas

#### 11.2 Docker (Opcional)
```dockerfile
# Dockerfile
FROM php:8.2-fpm
# ... configuración
```

#### 11.3 CI/CD
- GitHub Actions
- Tests automáticos
- Deploy automático

## 📊 Cronograma Estimado

| Fase | Duración | Dependencias |
|------|----------|--------------|
| 1. Configuración Base | 1-2 días | - |
| 2. Modelos y Migraciones | 2-3 días | Fase 1 |
| 3. Servicios Core | 3-4 días | Fase 2 |
| 4. GraphQL API | 2-3 días | Fase 3 |
| 5. WebSocket | 2 días | Fase 3 |
| 6. Machine Learning | 3-4 días | Fase 2, 3 |
| 7. REST API | 1-2 días | Fase 3 |
| 8. Integración MS | 2-3 días | Fase 4, 7 |
| 9. Testing | 2-3 días | Todas |
| 10. Dashboard (Opcional) | 3-4 días | Fase 4, 5 |
| 11. Deployment | 1-2 días | Fase 9 |

**Total: 19-30 días (sin dashboard) o 22-34 días (con dashboard)**

## 🎯 Prioridades

### Alta Prioridad (MVP)
1. ✅ Modelos y migraciones
2. ✅ GpsService (cálculo distancias)
3. ✅ AsignacionService (algoritmo asignación)
4. ✅ DespachoService (lógica principal)
5. ✅ REST API básica
6. ✅ WebSocket para rastreo

### Media Prioridad
7. GraphQL API completa
8. MLPredictionService básico
9. Integración con MS Recepción
10. Testing básico

### Baja Prioridad
11. ML avanzado con reentrenamiento
12. Dashboard admin
13. Integración completa con todos MS
14. Testing exhaustivo

## 🚀 Comandos Rápidos

```bash
# Setup inicial
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed

# Desarrollo
php artisan serve --port=8001
php artisan queue:work
php artisan reverb:start

# Testing
php artisan test
php artisan test --coverage

# Producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## 📝 Checklist de Implementación

- [ ] Fase 1: Configuración Base
- [ ] Fase 2: Modelos y Migraciones
- [ ] Fase 3: Servicios Core
- [ ] Fase 4: GraphQL API
- [ ] Fase 5: WebSocket
- [ ] Fase 6: Machine Learning
- [ ] Fase 7: REST API
- [ ] Fase 8: Integración MS
- [ ] Fase 9: Testing
- [ ] Fase 10: Dashboard (Opcional)
- [ ] Fase 11: Deployment

## 🎓 Recursos Adicionales

- [Laravel Documentation](https://laravel.com/docs)
- [GraphQL Laravel](https://github.com/rebing/graphql-laravel)
- [Laravel Reverb](https://laravel.com/docs/broadcasting)
- [PHP-ML](https://php-ml.readthedocs.io/)
- [PHPGeo](https://phpgeo.marcusjaschen.de/)

## 💡 Recomendaciones

1. **Empezar Simple**: Implementar MVP primero, luego agregar features
2. **Testing Continuo**: Escribir tests desde el inicio
3. **Documentación**: Documentar APIs y servicios
4. **Logs**: Implementar logging robusto
5. **Monitoreo**: Configurar métricas y alertas
6. **Seguridad**: Validar todos los inputs, usar JWT correctamente
7. **Performance**: Usar cache, queue para tareas pesadas
8. **Escalabilidad**: Diseñar pensando en crecimiento

## 🔄 Próximos Pasos

1. **Revisar y aprobar este plan**
2. **Configurar entorno de desarrollo**
3. **Iniciar Fase 1: Configuración Base**
4. **Daily standups para seguimiento**
5. **Iteraciones semanales con demos**
