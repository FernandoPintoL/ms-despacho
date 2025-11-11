# Etapa base
FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP nativas
RUN docker-php-ext-install \
    zip \
    gd \
    bcmath \
    ctype \
    xml \
    pdo \
    pdo_mysql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /app

# Copiar archivos de la aplicación
COPY . .

# Instalar dependencias de PHP (ignorar requerimientos de plataforma para SQL Server)
RUN composer install --no-interaction --no-dev --optimize-autoloader --ignore-platform-reqs 2>&1 || true

# Establecer permisos
RUN chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Exponer puerto (usado por php-fpm)
EXPOSE 9000

# Comando por defecto
CMD ["php-fpm"]
