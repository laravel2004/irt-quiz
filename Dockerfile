FROM dunglas/frankenphp:php8.4-alpine

# Set server name for FrankenPHP
ENV SERVER_NAME=":80"

# Install essential packages and PHP extensions
RUN install-php-extensions \
    pdo_mysql \
    redis \
    bcmath \
    intl \
    opcache \
    zip \
    pcntl \
    gd

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /app

# Install dependencies (production mode)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R root:root /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Create storage link
RUN php artisan storage:link

# PHP Memory & OPcache configuration for 2GB RAM Server to prevent OOM
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache-recommended.ini
