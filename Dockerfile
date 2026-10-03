# ============================================================
# Stage 1: Build frontend assets
# ============================================================
FROM node:22-alpine AS assets

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build


# ============================================================
# Stage 2: Laravel application
# ============================================================
FROM php:8.4-apache

# Install required system packages and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    curl \
    supervisor \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
    && a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' >> /etc/apache2/apache2.conf \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# ============================================================
# Composer
# ============================================================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# ============================================================
# Laravel application
# ============================================================
WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

COPY . .


# ============================================================
# Copy compiled Vite assets
# ============================================================
COPY --from=assets /app/public/build ./public/build


# ============================================================
# Laravel optimization
# ============================================================
RUN composer dump-autoload \
        --no-dev \
        --optimize \
    && mkdir -p storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache


# ============================================================
# Supervisor
# ============================================================
COPY docker/supervisord.conf \
    /etc/supervisor/conf.d/supervisord.conf


# ============================================================
# Startup script
# ============================================================
COPY docker/start.sh /start.sh

RUN sed -i 's/\r$//' /start.sh \
    && chmod +x /start.sh


# Render will expose the container through port 80
EXPOSE 80


# ============================================================
# Start Laravel + Apache + scheduler
# ============================================================
CMD ["/start.sh"]