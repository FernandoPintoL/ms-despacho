#!/bin/bash

echo "=========================================="
echo "  Iniciando MS-Despacho en Docker"
echo "=========================================="
echo ""

# Verificar si Docker está corriendo
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado o no está en el PATH"
    exit 1
fi

echo "✓ Docker detectado"
echo ""

# Construir imagen si es necesario
echo "1️⃣  Construyendo imagen Docker..."
docker-compose build

if [ $? -ne 0 ]; then
    echo "❌ Error al construir la imagen"
    exit 1
fi

echo "✓ Imagen construida"
echo ""

# Iniciar contenedores
echo "2️⃣  Iniciando contenedores..."
docker-compose up -d

if [ $? -ne 0 ]; then
    echo "❌ Error al iniciar contenedores"
    exit 1
fi

echo "✓ Contenedores iniciados"
echo ""

# Esperar a que los contenedores estén listos
echo "⏳ Esperando a que la aplicación esté lista..."
sleep 3

# Ejecutar migraciones
echo "3️⃣  Ejecutando migraciones de base de datos..."
docker-compose exec -T app php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "⚠️  Las migraciones no se ejecutaron (es normal si ya existe la BD)"
fi

echo ""
echo "=========================================="
echo "  ✅ ¡MS-Despacho está corriendo!"
echo "=========================================="
echo ""
echo "Acceder a: http://localhost:8001"
echo ""
echo "Comandos útiles:"
echo "  docker-compose logs -f app       → Ver logs"
echo "  docker-compose exec app bash     → Entrar al contenedor"
echo "  docker-compose down              → Detener contenedores"
echo ""
