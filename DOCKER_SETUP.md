# Docker Setup para MS-Despacho

## Configuración y Ejecución

### 1. **Requisitos previos**
- Docker instalado en tu máquina
- SQL Server/Postgres corriendo en tu máquina local en el puerto 1433 (SQL Server)
- La aplicación se ejecutará en el puerto 8001

### 2. **Compilar la imagen Docker**

```bash
docker-compose build
```

### 3. **Iniciar los contenedores**

```bash
docker-compose up -d
```

Este comando inicia:
- **PHP-FPM** en puerto 9000 (interno)
- **Nginx** en puerto 8001

### 4. **Ejecutar migraciones de base de datos (primera vez)**

```bash
docker-compose exec app php artisan migrate
```

### 5. **Generar clave de la aplicación (si es necesario)**

```bash
docker-compose exec app php artisan key:generate
```

### 6. **Instalar dependencias adicionales dentro del contenedor**

```bash
docker-compose exec app composer install
```

---

## Accediendo a la aplicación

- **URL**: http://localhost:8001
- **Base de datos**: Se conecta a tu máquina local via `host.docker.internal`

---

## Comandos útiles

### Ver logs de los contenedores
```bash
docker-compose logs -f app
docker-compose logs -f nginx
```

### Ejecutar comandos Artisan dentro del contenedor
```bash
docker-compose exec app php artisan {comando}
```

### Acceder a la terminal del contenedor
```bash
docker-compose exec app bash
```

### Detener los contenedores
```bash
docker-compose down
```

### Reconstruir la imagen (si cambias dependencias)
```bash
docker-compose up -d --build
```

---

## Configuración de la Base de Datos

El archivo `.env` está configurado para conectarse a tu máquina local:

```env
DB_HOST=host.docker.internal    # Acceso a tu máquina desde Docker
DB_PORT=1433                    # Puerto de SQL Server
DB_DATABASE=despacho            # Nombre de la BD
DB_USERNAME=despacho            # Usuario de BD
DB_PASSWORD=desp@cho1          # Contraseña de BD
```

### Si usas una BD local diferente:
Edita el `.env` y cambia:
```env
DB_HOST=host.docker.internal
DB_PORT=1433
DB_DATABASE=tu_base_de_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

---

## Solución de problemas

### La aplicación no puede conectarse a la BD
- Verifica que SQL Server esté corriendo en tu máquina
- Confirma que el puerto 1433 es accesible
- Revisa los logs: `docker-compose logs app`

### Puerto 8001 ya está en uso
Cambia el puerto en `docker-compose.yml`:
```yaml
ports:
  - "8002:80"  # Cambiar 8001 a otro puerto disponible
```

### Permisos de archivo
Si tienes problemas de permisos en `storage/` o `bootstrap/cache/`:
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
```
