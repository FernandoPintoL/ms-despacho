# 📚 Documentación API - MS Despacho

## 🌐 Base URL

```
http://localhost:8001/api/v1
```

## 📡 GraphQL Endpoint

```
http://localhost:8001/graphql
```

GraphiQL (interfaz web): `http://localhost:8001/graphiql`

---

## 🚑 REST API Endpoints

### Ambulancias

#### 1. Listar Ambulancias

```http
GET /api/v1/ambulancias
```

**Query Parameters:**
- `estado` (opcional): `disponible`, `en_servicio`, `mantenimiento`, `fuera_servicio`
- `tipo_ambulancia` (opcional): `basica`, `intermedia`, `avanzada`, `uci`
- `disponibles` (opcional): `true` o `false`

**Ejemplo:**
```bash
curl http://localhost:8001/api/v1/ambulancias?disponibles=true
```

**Respuesta:**
```json
[
  {
    "id": 1,
    "placa": "AMB-001",
    "modelo": "Mercedes-Benz Sprinter 2023",
    "tipo_ambulancia": "avanzada",
    "estado": "disponible",
    "ubicacion_actual_lat": -16.5000,
    "ubicacion_actual_lng": -68.1500,
    "ultima_actualizacion": "2025-10-25T05:00:00Z"
  }
]
```

#### 2. Obtener Ambulancia por ID

```http
GET /api/v1/ambulancias/{id}
```

**Ejemplo:**
```bash
curl http://localhost:8001/api/v1/ambulancias/1
```

#### 3. Actualizar Ubicación

```http
POST /api/v1/ambulancias/{id}/ubicacion
```

**Body:**
```json
{
  "latitud": -16.5050,
  "longitud": -68.1450
}
```

**Ejemplo:**
```bash
curl -X POST http://localhost:8001/api/v1/ambulancias/1/ubicacion \
  -H "Content-Type: application/json" \
  -d '{"latitud": -16.5050, "longitud": -68.1450}'
```

#### 4. Actualizar Estado

```http
PATCH /api/v1/ambulancias/{id}/estado
```

**Body:**
```json
{
  "estado": "en_servicio"
}
```

---

### Despachos

#### 1. Listar Despachos

```http
GET /api/v1/despachos
```

**Query Parameters:**
- `estado` (opcional): `pendiente`, `asignado`, `en_camino`, `en_sitio`, `trasladando`, `completado`, `cancelado`
- `prioridad` (opcional): `baja`, `media`, `alta`, `critica`
- `activos` (opcional): `true` o `false`
- `per_page` (opcional): número de resultados por página (default: 15)

**Ejemplo:**
```bash
curl http://localhost:8001/api/v1/despachos?activos=true
```

**Respuesta:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "solicitud_id": 123,
      "ambulancia": {
        "id": 1,
        "placa": "AMB-001",
        "tipo_ambulancia": "avanzada"
      },
      "personal_asignado": [
        {
          "id": 1,
          "nombre": "Juan",
          "apellido": "Pérez",
          "rol": "paramedico"
        }
      ],
      "estado": "en_camino",
      "distancia_km": 5.2,
      "tiempo_estimado_min": 12
    }
  ],
  "total": 1
}
```

#### 2. Crear Despacho

```http
POST /api/v1/despachos
```

**Body:**
```json
{
  "solicitud_id": 123,
  "ubicacion_origen_lat": -16.5000,
  "ubicacion_origen_lng": -68.1500,
  "direccion_origen": "Av. 6 de Agosto, La Paz",
  "ubicacion_destino_lat": -16.5100,
  "ubicacion_destino_lng": -68.1400,
  "direccion_destino": "Hospital de Clínicas",
  "incidente": "emergencia_medica",
  "prioridad": "alta",
  "tipo_ambulancia": "avanzada",
  "observaciones": "Paciente con dolor torácico"
}
```

**Campos requeridos:**
- `ubicacion_origen_lat`: Latitud del origen (-90 a 90)
- `ubicacion_origen_lng`: Longitud del origen (-180 a 180)

**Campos opcionales:**
- `solicitud_id`: ID de la solicitud del MS Recepción
- `direccion_origen`: Dirección textual del origen
- `ubicacion_destino_lat`: Latitud del destino
- `ubicacion_destino_lng`: Longitud del destino
- `direccion_destino`: Dirección textual del destino
- `incidente`: `accidente`, `emergencia_medica`, `traslado`, `otro` (default: `emergencia_medica`)
- `prioridad`: `baja`, `media`, `alta`, `critica` (default: `media`)
- `tipo_ambulancia`: `basica`, `intermedia`, `avanzada`, `uci`
- `observaciones`: Texto libre

**Ejemplo:**
```bash
curl -X POST http://localhost:8001/api/v1/despachos \
  -H "Content-Type: application/json" \
  -d '{
    "ubicacion_origen_lat": -16.5000,
    "ubicacion_origen_lng": -68.1500,
    "direccion_origen": "Av. 6 de Agosto",
    "incidente": "emergencia_medica",
    "prioridad": "alta"
  }'
```

**Respuesta exitosa (201):**
```json
{
  "message": "Despacho creado exitosamente",
  "data": {
    "id": 1,
    "solicitud_id": null,
    "ambulancia": {
      "id": 1,
      "placa": "AMB-001",
      "tipo_ambulancia": "avanzada"
    },
    "personal_asignado": [
      {
        "id": 1,
        "nombre": "Juan",
        "apellido": "Pérez",
        "rol": "paramedico",
        "pivot": {
          "es_responsable": true
        }
      },
      {
        "id": 4,
        "nombre": "Pedro",
        "apellido": "Mamani",
        "rol": "conductor",
        "pivot": {
          "es_responsable": false
        }
      }
    ],
    "estado": "asignado",
    "distancia_km": 5.2,
    "tiempo_estimado_min": 12,
    "fecha_solicitud": "2025-10-25T05:00:00Z",
    "fecha_asignacion": "2025-10-25T05:00:01Z"
  }
}
```

**Respuesta de error (503):**
```json
{
  "error": "No se pudo crear el despacho",
  "message": "No hay recursos disponibles"
}
```

#### 3. Obtener Despacho por ID

```http
GET /api/v1/despachos/{id}
```

**Ejemplo:**
```bash
curl http://localhost:8001/api/v1/despachos/1
```

**Respuesta:**
```json
{
  "id": 1,
  "ambulancia": {...},
  "personal_asignado": [...],
  "historial_rastreo": [
    {
      "id": 1,
      "latitud": -16.5010,
      "longitud": -68.1490,
      "velocidad": 45.5,
      "timestamp_gps": "2025-10-25T05:05:00Z"
    }
  ],
  "estado": "en_camino",
  "distancia_km": 5.2,
  "tiempo_estimado_min": 12
}
```

#### 4. Actualizar Estado del Despacho

```http
PATCH /api/v1/despachos/{id}
```

**Body:**
```json
{
  "estado": "en_camino"
}
```

**Estados válidos:**
- `pendiente`: Despacho creado pero no asignado
- `asignado`: Ambulancia y personal asignados
- `en_camino`: Ambulancia en ruta al origen
- `en_sitio`: Ambulancia llegó al sitio del incidente
- `trasladando`: Trasladando paciente al hospital
- `completado`: Despacho finalizado exitosamente
- `cancelado`: Despacho cancelado

**Ejemplo:**
```bash
curl -X PATCH http://localhost:8001/api/v1/despachos/1 \
  -H "Content-Type: application/json" \
  -d '{"estado": "en_sitio"}'
```

**Respuesta:**
```json
{
  "message": "Estado actualizado exitosamente",
  "data": {
    "id": 1,
    "estado": "en_sitio",
    "fecha_llegada": "2025-10-25T05:12:00Z"
  }
}
```

#### 5. Registrar Rastreo GPS

```http
POST /api/v1/despachos/{id}/rastreo
```

**Body:**
```json
{
  "latitud": -16.5010,
  "longitud": -68.1490,
  "velocidad": 45.5,
  "altitud": 3650.0,
  "precision": 10.0
}
```

**Campos requeridos:**
- `latitud`: Latitud GPS (-90 a 90)
- `longitud`: Longitud GPS (-180 a 180)

**Campos opcionales:**
- `velocidad`: Velocidad en km/h
- `altitud`: Altitud en metros
- `precision`: Precisión del GPS en metros

**Ejemplo:**
```bash
curl -X POST http://localhost:8001/api/v1/despachos/1/rastreo \
  -H "Content-Type: application/json" \
  -d '{
    "latitud": -16.5010,
    "longitud": -68.1490,
    "velocidad": 45.5
  }'
```

**Respuesta:**
```json
{
  "message": "Ubicación registrada exitosamente",
  "data": {
    "id": 1,
    "despacho_id": 1,
    "latitud": -16.5010,
    "longitud": -68.1490,
    "velocidad": 45.5,
    "timestamp_gps": "2025-10-25T05:05:00Z"
  }
}
```

---

### Health Check

#### Verificar Estado del Servicio

```http
GET /api/v1/health
```

**Ejemplo:**
```bash
curl http://localhost:8001/api/v1/health
```

**Respuesta:**
```json
{
  "status": "ok",
  "service": "ms-despacho",
  "timestamp": "2025-10-25T05:00:00Z"
}
```

---

## 🔮 GraphQL API

### Queries

#### 1. Obtener Ambulancia

```graphql
query {
  ambulancia(id: 1) {
    id
    placa
    modelo
    tipo_ambulancia
    estado
    ubicacion_actual_lat
    ubicacion_actual_lng
  }
}
```

#### 2. Listar Ambulancias

```graphql
query {
  ambulancias(disponibles: true, tipo_ambulancia: "avanzada") {
    id
    placa
    modelo
    tipo_ambulancia
    estado
  }
}
```

#### 3. Obtener Despacho

```graphql
query {
  despacho(id: 1) {
    id
    estado
    distancia_km
    tiempo_estimado_min
    ambulancia {
      placa
      tipo_ambulancia
    }
    personal_asignado {
      nombre_completo
      rol
    }
  }
}
```

#### 4. Listar Despachos

```graphql
query {
  despachos(activos: true, limit: 10) {
    id
    estado
    prioridad
    distancia_km
    ambulancia {
      placa
    }
  }
}
```

### Mutations

#### 1. Crear Despacho

```graphql
mutation {
  crearDespacho(
    ubicacion_origen_lat: -16.5000
    ubicacion_origen_lng: -68.1500
    direccion_origen: "Av. 6 de Agosto"
    incidente: "emergencia_medica"
    prioridad: "alta"
  ) {
    id
    estado
    distancia_km
    tiempo_estimado_min
    ambulancia {
      placa
      tipo_ambulancia
    }
    personal_asignado {
      nombre_completo
      rol
    }
  }
}
```

#### 2. Actualizar Estado de Despacho

```graphql
mutation {
  actualizarEstadoDespacho(id: 1, estado: "en_camino") {
    id
    estado
    fecha_asignacion
  }
}
```

#### 3. Actualizar Ubicación de Ambulancia

```graphql
mutation {
  actualizarUbicacionAmbulancia(
    id: 1
    latitud: -16.5050
    longitud: -68.1450
  ) {
    id
    placa
    ubicacion_actual_lat
    ubicacion_actual_lng
    ultima_actualizacion
  }
}
```

---

## 🔒 Autenticación (Próximamente)

Las rutas protegidas requerirán un token Sanctum del MS Autenticación:

```http
Authorization: Bearer {token}
```

---

## 📊 Códigos de Estado HTTP

- `200 OK`: Solicitud exitosa
- `201 Created`: Recurso creado exitosamente
- `400 Bad Request`: Solicitud mal formada
- `404 Not Found`: Recurso no encontrado
- `422 Unprocessable Entity`: Validación fallida
- `500 Internal Server Error`: Error del servidor
- `503 Service Unavailable`: Servicio no disponible (ej: sin recursos)

---

## 🧪 Ejemplos de Uso Completo

### Flujo Completo: Crear y Rastrear Despacho

```bash
# 1. Crear despacho
DESPACHO_ID=$(curl -X POST http://localhost:8001/api/v1/despachos \
  -H "Content-Type: application/json" \
  -d '{
    "ubicacion_origen_lat": -16.5000,
    "ubicacion_origen_lng": -68.1500,
    "prioridad": "alta"
  }' | jq -r '.data.id')

# 2. Actualizar estado a "en_camino"
curl -X PATCH http://localhost:8001/api/v1/despachos/$DESPACHO_ID \
  -H "Content-Type: application/json" \
  -d '{"estado": "en_camino"}'

# 3. Registrar ubicación GPS cada 5 segundos
curl -X POST http://localhost:8001/api/v1/despachos/$DESPACHO_ID/rastreo \
  -H "Content-Type: application/json" \
  -d '{
    "latitud": -16.5010,
    "longitud": -68.1490,
    "velocidad": 45.5
  }'

# 4. Actualizar a "en_sitio" al llegar
curl -X PATCH http://localhost:8001/api/v1/despachos/$DESPACHO_ID \
  -H "Content-Type: application/json" \
  -d '{"estado": "en_sitio"}'

# 5. Finalizar despacho
curl -X PATCH http://localhost:8001/api/v1/despachos/$DESPACHO_ID \
  -H "Content-Type: application/json" \
  -d '{"estado": "completado"}'
```

---

## 📝 Notas

- Todas las fechas están en formato ISO 8601 (UTC)
- Las coordenadas GPS usan el sistema WGS84
- Los tiempos están en minutos
- Las distancias están en kilómetros
- Las velocidades están en km/h

---

**Última actualización:** Octubre 2025  
**Versión API:** v1
