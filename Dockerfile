FROM php:8.2-cli

RUN apt-get update && apt-get install -y libpng-dev libzip-dev unzip git curl && docker-php-ext-install pdo_mysql zip gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 80
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]