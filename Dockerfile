# Dockerfile para backend-reservaVuelos
# Basado en dunglas/frankenphp (usado por Railpack) y con ext-mongodb instalada via PECL

### Stage 1: Composer (instala dependencias usando la imagen oficial de composer)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock /app/
# Ejecutar composer ignorando la extensión ext-mongodb en la etapa build
# (la extensión se instalará en la etapa runtime vía PECL)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts --ignore-platform-req=ext-mongodb

### Stage 2: Runtime con frankenphp
FROM dunglas/frankenphp:php8.2.29-bookworm

# Instalar dependencias necesarias para compilar extensiones
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        autoconf \
        libssl-dev \
        pkg-config \
        ca-certificates \
        unzip \
        git \
        build-essential \
    && rm -rf /var/lib/apt/lists/*

# Instalar la extensión mongodb via pecl y habilitarla
RUN pecl install mongodb \
    && mkdir -p /usr/local/etc/php/conf.d \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/20-mongodb.ini

# Directorio de trabajo
WORKDIR /srv/app

# Copiar vendor desde la etapa de composer
COPY --from=vendor /app/vendor /srv/app/vendor
COPY --from=vendor /app/composer.lock /srv/app/composer.lock
COPY --from=vendor /app/composer.json /srv/app/composer.json

# Copiar el resto de la aplicación
COPY . /srv/app

# Note: do not overwrite the base image's default Caddyfile to preserve frankenphp wiring.
# If you need to customize, prefer setting environment variables (CADDY_GLOBAL_OPTIONS, SERVER_NAME)
# or adding files in Caddyfile.d. We intentionally do not copy a Caddyfile here so the image's default
# frankenphp configuration (which properly wires PHP handling) remains intact.

# Ajustes de permisos (opcional, evita problemas de escritura)
RUN chown -R www-data:www-data /srv/app || true

# Exponer puerto (frankenphp gestiona el servidor)
EXPOSE 80

# Nota: frankenphp ya define CMD; no se sobrescribe para mantener compatibilidad.
