# MS WebSocket (Node.js) - Código Completo

## 📁 Estructura del Proyecto

```
ms-websocket/
├── server.js
├── package.json
├── .env
├── .gitignore
└── README.md
```

## 📦 package.json

```json
{
  "name": "ms-websocket",
  "version": "1.0.0",
  "description": "Microservicio WebSocket para rastreo de ambulancias en tiempo real",
  "main": "server.js",
  "scripts": {
    "start": "node server.js",
    "dev": "nodemon server.js"
  },
  "keywords": ["websocket", "socket.io", "ambulancia", "rastreo"],
  "author": "SWII Team",
  "license": "MIT",
  "dependencies": {
    "express": "^4.18.2",
    "socket.io": "^4.6.1",
    "redis": "^4.6.5",
    "cors": "^2.8.5",
    "dotenv": "^16.0.3"
  },
  "devDependencies": {
    "nodemon": "^3.0.1"
  }
}
```

## 🔧 .env

```env
PORT=3000
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
LARAVEL_API_URL=http://localhost:8001
CORS_ORIGIN=*
NODE_ENV=development
```

## 🚀 server.js

```javascript
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');
const redis = require('redis');
const cors = require('cors');
require('dotenv').config();

const app = express();
const server = http.createServer(app);

// Configurar CORS
app.use(cors({
    origin: process.env.CORS_ORIGIN || '*',
    methods: ['GET', 'POST']
}));

// Socket.IO con CORS
const io = socketIO(server, {
    cors: {
        origin: process.env.CORS_ORIGIN || '*',
        methods: ['GET', 'POST'],
        credentials: true
    }
});

// Cliente Redis para Pub/Sub
const subscriber = redis.createClient({
    socket: {
        host: process.env.REDIS_HOST || 'localhost',
        port: process.env.REDIS_PORT || 6379
    },
    password: process.env.REDIS_PASSWORD || undefined
});

// Conectar a Redis
subscriber.connect().then(() => {
    console.log('✅ Conectado a Redis');
    
    // Suscribirse al canal de eventos de Laravel
    subscriber.subscribe('despacho-events', (message) => {
        try {
            const data = JSON.parse(message);
            console.log('📨 Evento recibido de Laravel:', data.event);
            
            // Broadcast según el tipo de evento
            switch(data.event) {
                case 'ubicacion.actualizada':
                    io.to(`despacho.${data.despacho_id}`).emit('ubicacion.actualizada', data);
                    break;
                    
                case 'ambulancia.asignada':
                    io.to(`despacho.${data.despacho_id}`).emit('ambulancia.asignada', data);
                    break;
                    
                case 'estado.cambiado':
                    io.to(`despacho.${data.despacho_id}`).emit('estado.cambiado', data);
                    break;
                    
                case 'despacho.finalizado':
                    io.to(`despacho.${data.despacho_id}`).emit('despacho.finalizado', data);
                    break;
                    
                default:
                    console.log('⚠️  Evento desconocido:', data.event);
            }
        } catch (error) {
            console.error('❌ Error procesando mensaje de Redis:', error);
        }
    });
}).catch(err => {
    console.error('❌ Error conectando a Redis:', err);
});

// Almacenar clientes conectados
const connectedClients = new Map();

// Manejo de conexiones Socket.IO
io.on('connection', (socket) => {
    console.log(`🔌 Cliente conectado: ${socket.id}`);
    
    // Almacenar información del cliente
    connectedClients.set(socket.id, {
        id: socket.id,
        connectedAt: new Date(),
        rooms: []
    });
    
    // Unirse a un canal de despacho específico
    socket.on('join', (data) => {
        const { despacho_id, user_id, user_type } = data;
        
        if (!despacho_id) {
            socket.emit('error', { message: 'despacho_id es requerido' });
            return;
        }
        
        const room = `despacho.${despacho_id}`;
        socket.join(room);
        
        // Actualizar información del cliente
        const client = connectedClients.get(socket.id);
        if (client) {
            client.rooms.push(room);
            client.despacho_id = despacho_id;
            client.user_id = user_id;
            client.user_type = user_type;
        }
        
        console.log(`📍 Cliente ${socket.id} unido a ${room} (${user_type})`);
        
        socket.emit('joined', {
            room,
            despacho_id,
            message: 'Conectado al canal de rastreo'
        });
        
        // Notificar a otros en el room
        socket.to(room).emit('user.joined', {
            user_id,
            user_type,
            timestamp: new Date().toISOString()
        });
    });
    
    // Salir de un canal
    socket.on('leave', (data) => {
        const { despacho_id } = data;
        const room = `despacho.${despacho_id}`;
        
        socket.leave(room);
        console.log(`👋 Cliente ${socket.id} salió de ${room}`);
        
        socket.emit('left', { room, despacho_id });
    });
    
    // Recibir actualización de ubicación desde cliente (App Flutter)
    socket.on('actualizar.ubicacion', async (data) => {
        const { despacho_id, lat, lng, velocidad, token } = data;
        
        if (!despacho_id || !lat || !lng) {
            socket.emit('error', { message: 'Datos incompletos' });
            return;
        }
        
        console.log(`📍 Ubicación actualizada - Despacho ${despacho_id}: [${lat}, ${lng}]`);
        
        try {
            // Enviar a Laravel via HTTP para persistir en BD
            const response = await fetch(
                `${process.env.LARAVEL_API_URL}/api/despachos/${despacho_id}/rastreo`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ lat, lng, velocidad })
                }
            );
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            // Broadcast a todos los clientes en el room (excepto el emisor)
            socket.to(`despacho.${despacho_id}`).emit('ubicacion.actualizada', {
                despacho_id,
                latitud: lat,
                longitud: lng,
                velocidad,
                timestamp: new Date().toISOString()
            });
            
            // Confirmar al emisor
            socket.emit('ubicacion.confirmada', {
                despacho_id,
                timestamp: new Date().toISOString()
            });
            
        } catch (error) {
            console.error('❌ Error enviando ubicación a Laravel:', error);
            socket.emit('error', {
                message: 'Error al actualizar ubicación',
                error: error.message
            });
        }
    });
    
    // Ping/Pong para mantener conexión viva
    socket.on('ping', () => {
        socket.emit('pong', { timestamp: Date.now() });
    });
    
    // Desconexión
    socket.on('disconnect', (reason) => {
        console.log(`🔌 Cliente desconectado: ${socket.id} - Razón: ${reason}`);
        
        const client = connectedClients.get(socket.id);
        if (client) {
            // Notificar a los rooms donde estaba
            client.rooms.forEach(room => {
                socket.to(room).emit('user.left', {
                    user_id: client.user_id,
                    user_type: client.user_type,
                    timestamp: new Date().toISOString()
                });
            });
            
            connectedClients.delete(socket.id);
        }
    });
    
    // Manejo de errores
    socket.on('error', (error) => {
        console.error(`❌ Error en socket ${socket.id}:`, error);
    });
});

// Endpoint de salud
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        service: 'MS WebSocket',
        uptime: process.uptime(),
        connectedClients: connectedClients.size,
        timestamp: new Date().toISOString()
    });
});

// Endpoint para estadísticas
app.get('/stats', (req, res) => {
    const rooms = {};
    io.sockets.adapter.rooms.forEach((value, key) => {
        if (!key.includes('.')) return; // Ignorar rooms de socket IDs
        rooms[key] = value.size;
    });
    
    res.json({
        connectedClients: connectedClients.size,
        rooms,
        timestamp: new Date().toISOString()
    });
});

// Iniciar servidor
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`
╔═══════════════════════════════════════════╗
║   🚀 MS WebSocket Server                  ║
║   Puerto: ${PORT}                            ║
║   Entorno: ${process.env.NODE_ENV || 'development'}              ║
║   Redis: ${process.env.REDIS_HOST}:${process.env.REDIS_PORT}        ║
╚═══════════════════════════════════════════╝
    `);
});

// Manejo de errores no capturados
process.on('uncaughtException', (error) => {
    console.error('❌ Uncaught Exception:', error);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ Unhandled Rejection at:', promise, 'reason:', reason);
});

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('⚠️  SIGTERM recibido, cerrando servidor...');
    server.close(() => {
        console.log('✅ Servidor cerrado');
        subscriber.quit();
        process.exit(0);
    });
});
```

## 📝 .gitignore

```
node_modules/
.env
.env.local
.env.production
npm-debug.log*
yarn-debug.log*
yarn-error.log*
.DS_Store
```

## 📖 README.md

```markdown
# MS WebSocket - Rastreo de Ambulancias

Microservicio Node.js con Socket.IO para comunicación en tiempo real.

## Instalación

\`\`\`bash
npm install
\`\`\`

## Configuración

Copiar `.env.example` a `.env` y configurar variables.

## Iniciar

\`\`\`bash
# Desarrollo
npm run dev

# Producción
npm start
\`\`\`

## Eventos

### Cliente → Servidor

- `join`: Unirse a canal de despacho
- `leave`: Salir de canal
- `actualizar.ubicacion`: Enviar ubicación GPS
- `ping`: Verificar conexión

### Servidor → Cliente

- `ubicacion.actualizada`: Nueva ubicación de ambulancia
- `ambulancia.asignada`: Ambulancia asignada a despacho
- `estado.cambiado`: Estado de despacho cambió
- `despacho.finalizado`: Despacho completado
- `pong`: Respuesta a ping

## Testing

\`\`\`bash
# Conectar con cliente de prueba
node test-client.js
\`\`\`
```

## 🧪 test-client.js (Cliente de Prueba)

```javascript
const io = require('socket.io-client');

const socket = io('http://localhost:3000', {
    transports: ['websocket']
});

socket.on('connect', () => {
    console.log('✅ Conectado al servidor WebSocket');
    
    // Unirse a un despacho
    socket.emit('join', {
        despacho_id: 123,
        user_id: 1,
        user_type: 'paramedico'
    });
});

socket.on('joined', (data) => {
    console.log('📍 Unido a:', data);
    
    // Simular envío de ubicación cada 5 segundos
    setInterval(() => {
        socket.emit('actualizar.ubicacion', {
            despacho_id: 123,
            lat: -33.4569 + (Math.random() * 0.01),
            lng: -70.6483 + (Math.random() * 0.01),
            velocidad: 40 + (Math.random() * 20),
            token: 'test-token'
        });
    }, 5000);
});

socket.on('ubicacion.actualizada', (data) => {
    console.log('📍 Nueva ubicación:', data);
});

socket.on('error', (error) => {
    console.error('❌ Error:', error);
});

socket.on('disconnect', () => {
    console.log('🔌 Desconectado');
});
```

## 🚀 Comandos Útiles

```bash
# Instalar dependencias
npm install

# Desarrollo con auto-reload
npm run dev

# Producción
npm start

# Ver logs
npm start | bunyan  # Si usas bunyan para logs

# PM2 para producción
pm2 start server.js --name ms-websocket
pm2 logs ms-websocket
pm2 restart ms-websocket
```
