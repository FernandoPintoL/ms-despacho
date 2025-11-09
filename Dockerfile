# Stage 1: Build dependencies
FROM php:8.2-fpm-alpine as builder

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    linux-headers \
    g++ \
    make \
    autoconf \
    postgresql-dev \
    libpq

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    opcache \
    pcntl \
    bcmath

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies (production)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-progress \
    --no-interaction

# Copy the rest of the application
COPY . .

# Generate autoloader
RUN composer dump-autoload --optimize

# Stage 2: Runtime
FROM php:8.2-fpm-alpine

# Install runtime dependencies only
RUN apk add --no-cache \
    postgresql-client \
    libpq \
    redis \
    curl \
    bash

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    opcache \
    pcntl \
    bcmath

# Configure PHP-FPM
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Configure PHP
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Set working directory
WORKDIR /app

# Copy from builder
COPY --from=builder --chown=www-data:www-data /app .

# Create required directories with proper permissions
RUN mkdir -p storage/logs storage/cache bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:9000/api/health || exit 1

# Expose port
EXPOSE 9000

# Switch to non-root user
USER www-data

# Run PHP-FPM
CMD ["php-fpm"]
