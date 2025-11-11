# Estado de la Dockerización de MS-Despacho

## ✅ Completado

1. **Dockerfile creado**
   - PHP 8.2-FPM como base
   - Extensiones PHP instaladas: zip, gd, bcmath, ctype, xml, pdo, pdo_mysql
   - Composer instalado
   - Dependencias de Composer instaladas

2. **docker-compose.yml configurado**
   - Servicio APP (PHP-FPM) en puerto interno 9000
   - Servicio NGINX en puerto 8001 (accesible externamente)
   - Red privada de Docker (despacho-network)
   - Volumes configurados para código compartido

3. **Configuración de Nginx**
   - Reescritura de URLs para Laravel
   - Proxy FastCGI a PHP-FPM
   - Timeouts ajustados (120s)

4. **Variables de entorno**
   - `.env` configurado para usar `host.docker.internal` (acceso a BD local)
   - APP_KEY generada
   - DB_HOST=host.docker.internal
   - Puerto BD: 1433

## ⚠️ Problema Actual

**Timeout en requests HTTP**
- Los contenedores están corriendo correctamente
- Laravel responde con timeout 504 Gateway cuando intenta procesar requests
- Causa probable: Problema de conectividad a la base de datos

## 🔧 Pasos para Solucionar

### Opción 1: Verificar conectividad a BD desde el contenedor

```bash
# Acceder al contenedor
docker-compose exec app bash

# Desde dentro del contenedor, probar conexión
php artisan tinker
# Luego en tinker:
# DB::connection()->getPdo();
```

### Opción 2: Usar IP del host en lugar de host.docker.internal

Si `host.docker.internal` no funciona en Windows WSL2:

```bash
# En .env, cambiar:
DB_HOST=172.17.0.1  # o la IP del host actual
```

### Opción 3: Mover BD adentro de Docker (No recomendado por el usuario)

Crear servicio de BD en docker-compose.yml:

```yaml
mssql:
  image: mcr.microsoft.com/mssql/server:latest
  ports:
    - "1433:1433"
  environment:
    SA_PASSWORD: tu_contraseña
    ACCEPT_EULA: Y
```

## 📝 Archivos Creados

- `Dockerfile` - Imagen Docker para la aplicación
- `docker-compose.yml` - Orquestación de servicios
- `docker/nginx/default.conf` - Configuración de Nginx
- `docker/php/local.ini` - Configuración de PHP
- `.env.docker` - Referencia de variables
- `DOCKER_SETUP.md` - Documentación de uso
- `docker-start.sh` - Script de inicio automatizado

## 🚀 Comandos Útiles

```bash
# Ver estado
docker-compose ps

# Ver logs
docker-compose logs -f app
docker-compose logs -f nginx

# Acceder a consola
docker-compose exec app bash

# Reiniciar
docker-compose restart

# Detener
docker-compose down

# Reconstruir imagen
docker-compose build --no-cache
```

## 💡 Siguiente Paso Recomendado

Verifica si SQL Server está corriendo en tu máquina:
```powershell
netstat -an | findstr :1433
```

Si no está corriendo, inicia SQL Server o usa `host.docker.internal` verificando que funcione desde Windows.
