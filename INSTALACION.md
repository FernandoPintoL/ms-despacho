# Instalación de Dependencias - MS Despacho

## 🏗️ Arquitectura de Microservicios

Este sistema utiliza una arquitectura distribuida:
- **MS Despacho (Laravel)**: Lógica de negocio y asignación
- **MS WebSocket (Node.js)**: Comunicación en tiempo real
- **ML Service (Python)**: Predicción de tiempos con Machine Learning
- **MS Autenticación (Laravel + Sanctum)**: Gestión de tokens

## 1. Paquetes PHP (Composer) - MS Despacho

```bash
# GraphQL
composer require rebing/graphql-laravel

# Geolocalización y GPS
composer require mjaschen/phpgeo

# Sanctum Authentication (NO JWT)
composer require laravel/sanctum

# Redis para Queue, Cache y Pub/Sub
composer require predis/predis

# NOTA: NO instalar paquetes de ML ni WebSocket
# - ML se maneja con servicio Python separado
# - WebSocket se maneja con servicio Node.js separado
```

## 2. Configuración de Paquetes

### GraphQL
```bash
php artisan vendor:publish --provider="Rebing\GraphQL\GraphQLServiceProvider"
```

### Sanctum Authentication
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

## 2. MS WebSocket (Node.js) - Servicio Separado

### Crear proyecto Node.js
```bash
# En carpeta paralela al MS Despacho
cd ..
mkdir ms-websocket
cd ms-websocket
npm init -y
```

### Instalar dependencias
```bash
npm install express socket.io redis cors dotenv
npm install --save-dev nodemon
```

### Estructura del proyecto
```
ms-websocket/
├── server.js
├── package.json
├── .env
└── README.md
```

## 3. ML Service (Python) - Servicio Separado

### Crear proyecto Python
```bash
# En carpeta paralela al MS Despacho
cd ..
mkdir ml-service
cd ml-service
python -m venv venv
```

### Activar entorno virtual
```bash
# Windows
venv\Scripts\activate

# Linux/Mac
source venv/bin/activate
```

### Instalar dependencias
```bash
pip install flask scikit-learn numpy pandas joblib python-dotenv
pip freeze > requirements.txt
```

### Estructura del proyecto
```
ml-service/
├── app.py
├── requirements.txt
├── models/
│   └── tiempo_llegada.pkl
├── .env
└── README.md
```

## 4. Variables de Entorno

### MS Despacho (.env)

```env
# Microservicio
APP_NAME="MS Despacho"
APP_URL=http://localhost:8001

# Base de datos (SQL Server)
DB_CONNECTION=sqlsrv
DB_HOST=127.0.0.1
DB_PORT=1433
DB_DATABASE=ms_despacho
DB_USERNAME=sa
DB_PASSWORD=
DB_CHARSET=utf8
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=true

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000

# Queue
QUEUE_CONNECTION=redis

# Redis (para Queue y Pub/Sub)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Otros Microservicios
MS_RECEPCION_URL=http://localhost:8000
MS_DECISION_URL=http://localhost:8002
MS_AUTH_URL=http://localhost:8003
MS_WEBSOCKET_URL=http://localhost:3000
ML_SERVICE_URL=http://localhost:5000

# GPS Config
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

## 5. Migraciones y Seeders

```bash
# MS Despacho
cd ms-despacho
php artisan migrate
php artisan db:seed
```

## 6. Iniciar Todos los Servicios

### Terminal 1: MS Despacho (Laravel)
```bash
cd ms-despacho
php artisan serve --port=8001
```

### Terminal 2: Queue Worker (Laravel)
```bash
cd ms-despacho
php artisan queue:work
```

### Terminal 3: MS WebSocket (Node.js)
```bash
cd ms-websocket
node server.js
# O con nodemon para auto-reload:
npm run dev
```

### Terminal 4: ML Service (Python)
```bash
cd ml-service
python app.py
# O con Flask:
flask run --port=5000
```

### Terminal 5: Frontend (Opcional)
```bash
cd ms-despacho
npm run dev
```

### Producción
```bash
# Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Supervisor para Queue Worker
# Ver: https://laravel.com/docs/queues#supervisor-configuration
```

## 7. Testing

```bash
# Ejecutar tests
php artisan test

# Con coverage
php artisan test --coverage
```

## 8. Estructura de Carpetas

### MS Despacho (Laravel)
```bash
mkdir -p app/Services
mkdir -p app/GraphQL/Queries
mkdir -p app/GraphQL/Mutations
mkdir -p app/GraphQL/Types
mkdir -p app/Events
mkdir -p app/Jobs
```

### Estructura completa de microservicios
```
micro_servicios/
├── ms-despacho/          # Laravel
├── ms-websocket/         # Node.js
├── ml-service/           # Python
├── ms-recepcion/         # Laravel (otro equipo)
├── ms-decision/          # Laravel (otro equipo)
└── ms-auth/              # Laravel + Sanctum (otro equipo)
```

## 9. Paquetes NPM (Frontend - opcional)

Si necesitas dashboard de administración:

```bash
npm install @apollo/client graphql
npm install socket.io-client
npm install leaflet react-leaflet  # Para mapas
```

## 10. Docker (Opcional)

Si prefieres usar Docker:

```bash
# Laravel Sail ya incluido
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

## Notas Importantes

1. **Arquitectura de Microservicios**: Este sistema utiliza 3 servicios separados:
   - **MS Despacho (Laravel)**: Lógica de negocio
   - **MS WebSocket (Node.js)**: Comunicación en tiempo real
   - **ML Service (Python)**: Predicciones con Machine Learning

2. **Autenticación**: 
   - Sanctum desde MS Autenticación (compartido)
   - Validar tokens llamando a MS Auth o usando shared database
   - NO usar JWT, usar Sanctum para consistencia

3. **WebSocket con Node.js**:
   - Socket.IO para comunicación bidireccional
   - Redis Pub/Sub para comunicación Laravel ↔ Node.js
   - Más escalable que Laravel Reverb para este caso

4. **Machine Learning en Python**:
   - Flask/FastAPI para API REST
   - scikit-learn para modelos ML
   - Mucho más potente que PHP-ML
   - Comunicación vía HTTP desde Laravel

5. **Redis**: CRÍTICO para este sistema:
   - Queue (Laravel)
   - Cache (Laravel)
   - Pub/Sub (Laravel → Node.js)

6. **Base de Datos**: Cambia de SQLite a MySQL/PostgreSQL para producción.

7. **Puertos por defecto**:
   - MS Despacho: 8001
   - MS WebSocket: 3000
   - ML Service: 5000
   - MS Recepción: 8000
   - MS Decisión: 8002
   - MS Auth: 8003
