# Dockerfile untuk deploy ke Render (tidak perlu dijalankan di laptop).
FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip intl bcmath opcache \
    && a2enmod rewrite \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'opcache.enable=1\nopcache.memory_consumption=128\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=0\n' > "$PHP_INI_DIR/conf.d/opcache-his.ini" \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

# Apache melayani folder public/ milik Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY docker/start.sh /usr/local/bin/start.sh
# sed: buang akhir baris Windows (CRLF) supaya skrip tetap jalan walau disimpan dari Windows
RUN sed -i 's/\r$//' /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
