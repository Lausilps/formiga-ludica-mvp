FROM php:8.2-cli

RUN apt-get update && apt-get install -y libcurl4-openssl-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql curl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY . .

EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]