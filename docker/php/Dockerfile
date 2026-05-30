# ==============================================================================
# Modern PHP-FPM Dockerfile
# ==============================================================================
ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-fpm-alpine

# Set main working directory
WORKDIR /var/www/html

# Install system utilities and the official PHP Extension Installer
# https://github.com/mlocati/docker-php-extension-installer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
    gd \
    zip \
    intl \
    opcache \
    apcu \
    pdo_mysql \
    mysqli \
    xdebug \
    @composer

# Copy custom configurations
COPY php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini

# Use the default development configuration as base
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Set proper permissions for the working directory
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
