# 🔗 Integración con Microservicios

Este documento describe cómo MS Despacho se comunica con otros microservicios del sistema.

---

## 📋 Microservicios Requeridos

### 1. **MS Autenticación** (Puerto 8003)
- **Estado**: ⚠️ Pendiente de implementación
- **Propósito**: Validación de tokens JWT
- **Comunicación**: HTTP REST

### 2. **MS WebSocket** (Puerto 3000 - Node.js)
- **Estado**: ⚠️ Pendiente de implementación
- **Propósito**: Notificaciones en tiempo real
- **Comunicación**: HTTP POST → WebSocket Broadcast

### 3. **MS Decisión** (Puerto 8002)
- **Estado**: ⚠️ Pendiente de implementación
- **Propósito**: Recibir notificaciones de eventos de despacho
- **Comunicación**: HTTP Webhooks

### 4. **Servicio ML** (Puerto 5000 - Python)
- **Estado**: ⚠️ Pendiente de implementación
- **Propósito**: Predicción de tiempos de llegada
- **Comunicación**: HTTP REST

---

## 🔐 MS Autenticación

### Configuración

```env
MS_AUTH_URL=http://localhost:8003
MS_AUTH_TIMEOUT=10
MS_AUTH_VERIFY_ENDPOINT=/api/verify-token
```

### Endpoints Esperados

#### 1. Verificar Token
```http
POST /api/verify-token
Authorization: Bearer {token}
```

**Respuesta Exitosa (200):**
```json
{
  "id": 1,
  "email": "usuario@example.com",
  "name": "Juan Pérez",
  "role": "operador",
  "permissions": ["crear_despacho", "ver_despachos"]
}
```

**Respuesta Error (401):**
```json
{
  "error": "Token inválido o expirado"
}
```

#### 2. Health Check
```http
GET /api/health
```

**Respuesta:**
```json
{
  "status": "ok",
  "service": "ms-auth"
}
```

### Uso en MS Despacho

#### Middleware de Autenticación
```php
// Rutas protegidas
Route::middleware('verify.token')->group(function () {
    Route::post('/despachos', [DespachoController::class, 'store']);
});
```

#### Acceder a datos del usuario autenticado
```php
public function store(Request $request)
{
    $userId = $request->attributes->get('user_id');
    $userEmail = $request->attributes->get('user_email');
    $userRole = $request->attributes->get('user_role');
    
    // O acceder al objeto completo
    $authUser = $request->input('auth_user');
}
```

---

## 🌐 MS WebSocket (Node.js)

### Configuración

```env
MS_WEBSOCKET_URL=http://localhost:3000
MS_WEBSOCKET_TIMEOUT=5
MS_WEBSOCKET_ENABLED=true
```

### Endpoints Esperados

#### 1. Recibir Notificación
```http
POST /api/notificar
Content-Type: application/json
```

**Body:**
```json
{
  "evento": "despacho.creado",
  "datos": {
    "id": 1,
    "ambulancia_placa": "AMB-001",
    "estado": "asignado",
    "prioridad": "alta"
  },
  "despacho_id": 1,
  "timestamp": "2025-10-25T05:00:00Z"
}
```

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "message": "Notificación enviada",
  "clients_notified": 5
}
```

#### 2. Health Check
```http
GET /health
```

### Eventos que MS Despacho Envía

| Evento | Descripción | Cuándo se dispara |
|--------|-------------|-------------------|
| `despacho.creado` | Nuevo despacho creado | Al crear despacho |
| `despacho.estado.cambiado` | Estado cambió | Al actualizar estado |
| `despacho.finalizado` | Despacho finalizado | Al completar/cancelar |
| `ambulancia.ubicacion.actualizada` | GPS actualizado | Al actualizar ubicación |

### Uso en MS Despacho

Los eventos se envían automáticamente mediante Jobs:

```php
// Automático al crear despacho
event(new DespachoCreado($despacho));

// Automático al cambiar estado
$despacho->cambiarEstado('en_camino');

// Automático al actualizar ubicación
$ambulancia->actualizarUbicacion($lat, $lng);
```

### Implementación Sugerida para MS WebSocket

```javascript
// Node.js + Socket.io
const express = require('express');
const http = require('http');
const socketIo = require('socket.io');

const app = express();
const server = http.createServer(app);
const io = socketIo(server);

app.use(express.json());

// Endpoint para recibir notificaciones
app.post('/api/notificar', (req, res) => {
  const { evento, datos, despacho_id } = req.body;
  
  // Broadcast a todos los clientes conectados
  io.emit(evento, datos);
  
  // O enviar solo a sala específica
  if (despacho_id) {
    io.to(`despacho_${despacho_id}`).emit(evento, datos);
  }
  
  res.json({
    success: true,
    message: 'Notificación enviada',
    clients_notified: io.engine.clientsCount
  });
});

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok', service: 'ms-websocket' });
});

server.listen(3000, () => {
  console.log('MS WebSocket escuchando en puerto 3000');
});
```

---

## 🎯 MS Decisión

### Configuración

```env
MS_DECISION_URL=http://localhost:8002
MS_DECISION_TIMEOUT=10
MS_DECISION_WEBHOOK_ENDPOINT=/api/webhook/despacho
```

### Endpoints Esperados

#### 1. Webhook de Despacho
```http
POST /api/webhook/despacho
Content-Type: application/json
```

**Body:**
```json
{
  "evento": "despacho_creado",
  "despacho_id": 1,
  "solicitud_id": 123,
  "estado": "asignado",
  "ambulancia": {
    "id": 1,
    "placa": "AMB-001"
  },
  "tiempo_real_min": null,
  "timestamp": "2025-10-25T05:00:00Z"
}
```

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "message": "Webhook procesado"
}
```

#### 2. Health Check
```http
GET /api/health
```

### Eventos que MS Despacho Notifica

| Evento | Cuándo | Datos Incluidos |
|--------|--------|-----------------|
| `despacho_creado` | Al crear | ID, ambulancia, estado |
| `despacho_finalizado` | Al finalizar | ID, resultado, tiempos |

---

## 🤖 Servicio ML (Python)

### Configuración

```env
ML_SERVICE_URL=http://localhost:5000
ML_SERVICE_TIMEOUT=10
ML_USE_FALLBACK=true
```

### Endpoints Esperados

#### 1. Predecir Tiempo de Llegada
```http
POST /predict
Content-Type: application/json
```

**Body:**
```json
{
  "distancia_km": 10.5,
  "tipo_ambulancia": "avanzada",
  "trafico_estimado": 0.6,
  "hora_dia": 14,
  "dia_semana": 3
}
```

**Respuesta:**
```json
{
  "tiempo_estimado": 18,
  "confianza": 0.85
}
```

#### 2. Enviar Datos para Reentrenamiento
```http
POST /feedback
Content-Type: application/json
```

**Body:**
```json
{
  "despacho_id": 1,
  "distancia_km": 10.5,
  "tiempo_estimado_min": 18,
  "tiempo_real_min": 16,
  "tipo_ambulancia": "avanzada",
  "prioridad": "alta"
}
```

#### 3. Health Check
```http
GET /health
```

---

## 🧪 Verificar Conexiones

### Endpoint de Verificación

```bash
curl http://localhost:8001/api/v1/health/microservices
```

**Respuesta:**
```json
{
  "status": "degraded",
  "services": {
    "auth": {
      "nombre": "MS Autenticación",
      "url": "http://localhost:8003",
      "disponible": false,
      "mensaje": "Servicio no disponible"
    },
    "websocket": {
      "nombre": "MS WebSocket",
      "url": "http://localhost:3000",
      "disponible": false,
      "mensaje": "Servicio no disponible"
    },
    "decision": {
      "nombre": "MS Decisión",
      "url": "http://localhost:8002",
      "disponible": false,
      "mensaje": "Servicio no disponible"
    },
    "ml": {
      "nombre": "Servicio ML",
      "url": "http://localhost:5000",
      "disponible": false,
      "mensaje": "Servicio no disponible (usando fallback)"
    }
  },
  "timestamp": "2025-10-25T05:00:00Z"
}
```

---

## 📊 Arquitectura de Comunicación

```
┌─────────────────┐
│   MS Despacho   │
│  (Laravel - PHP)│
└────────┬────────┘
         │
         ├──────────────────────────────────────┐
         │                                      │
         ▼                                      ▼
┌─────────────────┐                  ┌──────────────────┐
│ MS Autenticación│◄─────────────────┤   Cliente Web    │
│  (Laravel - PHP)│  1. Login        │   (Frontend)     │
└────────┬────────┘  2. Get Token    └──────────────────┘
         │           3. Use Token              │
         │                                     │
         ▼                                     ▼
┌─────────────────┐                  ┌──────────────────┐
│  MS WebSocket   │◄─────────────────┤  WebSocket       │
│  (Node.js)      │  Real-time       │  Connection      │
└─────────────────┘  Notifications   └──────────────────┘
         ▲
         │
         │ HTTP POST
         │
┌────────┴────────┐
│   MS Despacho   │
│   (Events/Jobs) │
└─────────────────┘
```

---

## 🔧 Configuración Completa

### Archivo `.env`

```env
# MS Despacho
APP_PORT=8001

# MS Autenticación
MS_AUTH_URL=http://localhost:8003
MS_AUTH_TIMEOUT=10
MS_AUTH_VERIFY_ENDPOINT=/api/verify-token

# MS WebSocket
MS_WEBSOCKET_URL=http://localhost:3000
MS_WEBSOCKET_TIMEOUT=5
MS_WEBSOCKET_ENABLED=true

# MS Decisión
MS_DECISION_URL=http://localhost:8002
MS_DECISION_TIMEOUT=10
MS_DECISION_WEBHOOK_ENDPOINT=/api/webhook/despacho

# Servicio ML
ML_SERVICE_URL=http://localhost:5000
ML_SERVICE_TIMEOUT=10
ML_USE_FALLBACK=true

# Queue (para Jobs asíncronos)
QUEUE_CONNECTION=database
```

---

## ✅ Checklist de Implementación

### MS Despacho (Este servicio) ✅
- [x] Configuración de endpoints
- [x] AuthService para verificar tokens
- [x] Middleware de autenticación
- [x] Jobs para notificaciones
- [x] Health checks
- [x] Documentación

### MS Autenticación ⚠️
- [ ] Endpoint `/api/verify-token`
- [ ] Endpoint `/api/health`
- [ ] Generación de tokens JWT
- [ ] CRUD de usuarios

### MS WebSocket ⚠️
- [ ] Endpoint `/api/notificar`
- [ ] Endpoint `/health`
- [ ] Socket.io configurado
- [ ] Salas por despacho

### MS Decisión ⚠️
- [ ] Endpoint `/api/webhook/despacho`
- [ ] Endpoint `/api/health`
- [ ] Procesamiento de eventos

### Servicio ML ⚠️
- [ ] Endpoint `/predict`
- [ ] Endpoint `/feedback`
- [ ] Endpoint `/health`
- [ ] Modelo entrenado

---

## 📝 Notas Importantes

1. **Fallback**: MS Despacho funciona sin los otros servicios, con funcionalidad reducida
2. **Cache**: Los tokens se cachean por 5 minutos para reducir llamadas
3. **Reintentos**: Los Jobs tienen reintentos automáticos
4. **Timeouts**: Todos los servicios tienen timeouts configurables
5. **Logs**: Todos los errores de comunicación se registran en logs

---

**Última actualización:** Octubre 2025  
**Versión:** 1.0.0
