# Dockerfile para backend-reservaVuelos
# Basado en dunglas/frankenphp (usado por Railpack) y con ext-mongodb instalada via PECL

FROM dunglas/frankenphp:php8.2.29-bookworm

# Instalar dependencias necesarias para pecl y compilación
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

# Copiar composer files primero para aprovechar cache de docker
COPY composer.json composer.lock /srv/app/

# Instalar dependencias con Composer (sin dev, optimizado)
RUN php -v && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copiar el resto de la aplicación
COPY . /srv/app

# Ajustes de permisos (opcional, evita problemas de escritura)
RUN chown -R www-data:www-data /srv/app || true

# Exponer puerto (frankenphp gestiona el servidor)
EXPOSE 80

# Nota: frankenphp ya define CMD; no se sobrescribe para mantener compatibilidad.
