# Image demo QuickCare (backend Laravel + frontend statis dalam satu server)
FROM php:8.3-cli

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip libsqlite3-dev libzip-dev \
 && docker-php-ext-install pdo_sqlite zip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY backend backend
COPY frontend frontend

RUN cd backend && composer install --no-dev --optimize-autoloader --no-interaction --no-progress

COPY docker/start.sh /start.sh
RUN sed -i 's/\r$//' /start.sh && chmod +x /start.sh

ENV PORT=8000
EXPOSE 8000
CMD ["/start.sh"]