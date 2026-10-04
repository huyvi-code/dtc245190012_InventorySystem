# ==============================================================================
# Production Dockerfile for PHP Inventory Management System
# DTC245190012 - Đề 5: Quản lý Kho hàng
# ==============================================================================

FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install required system packages and PHP extensions
RUN apk add --no-cache \
    curl \
    fcgi \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql opcache \
    && rm -rf /var/cache/apk/*

# Production Security Configurations for PHP
RUN { \
    echo 'expose_php = Off'; \
    echo 'display_errors = Off'; \
    echo 'display_startup_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/log/php_errors.log'; \
    echo 'memory_limit = 256M'; \
    echo 'upload_max_filesize = 25M'; \
    echo 'post_max_size = 30M'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_secure = 1'; \
    echo 'session.use_strict_mode = 1'; \
    echo 'opcache.enable = 1'; \
    echo 'opcache.memory_consumption = 128'; \
    echo 'opcache.interned_strings_buffer = 8'; \
    echo 'opcache.max_accelerated_files = 4000'; \
    echo 'opcache.validate_timestamps = 0'; \
} > /usr/local/etc/php/conf.d/security-hardening.ini

# Copy application code
COPY ./src /var/www/html

# Permissions: Set ownership to non-root user (www-data)
RUN touch /var/log/php_errors.log \
    && chown -R www-data:www-data /var/www/html /var/log/php_errors.log \
    && chmod -R 755 /var/www/html

# Hardening: Run as non-root user
USER www-data

# Expose PHP-FPM default port
EXPOSE 9000

CMD ["php-fpm"]
