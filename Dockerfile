# syntax=docker/dockerfile:1

# ---- Stage 1: build front-end assets (Vite/Tailwind) ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Stage 2: PHP application ----
FROM php:8.2-cli AS app

# System libraries needed to compile the PHP extensions this app uses:
#   pdo_pgsql -> PostgreSQL   gd -> DomPDF images   zip/mbstring/bcmath -> Laravel
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip \
        libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype-dev libwebp-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql gd zip bcmath mbstring \
    && rm -rf /var/lib/apt/lists/*

# Composer (copied from the official Composer image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy the application source, then install PHP dependencies (runs Laravel's
# package:discover, so the full source must be present first).
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Bring in the compiled assets from the Node stage.
COPY --from=assets /app/public/build ./public/build

# Laravel needs these writable at runtime.
RUN chmod -R 775 storage bootstrap/cache

# Start-up script runs migrations + storage link, then serves the app.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Railway provides $PORT at runtime; default to 8080 for local runs.
ENV PORT=8080
EXPOSE 8080

CMD ["docker-entrypoint.sh"]
