FROM php:8.4-apache

# konfigurasi PHP produksi
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# ekstensi PostgreSQL untuk PDO
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

# Apache mendengarkan di port 8080
ENV PORT=8080

RUN sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf \
 && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' \
      /etc/apache2/sites-available/000-default.conf \
 && echo 'FallbackResource /index.php' > /etc/apache2/conf-enabled/fallback.conf

# kode aplikasi
COPY . /var/www/html/

EXPOSE 8080