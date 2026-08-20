FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

EXPOSE 8081

CMD ["php", "-S", "0.0.0.0:8081", "-t", "public"]
