# Minimal container to run the PHP app (development / small-prod)
FROM php:8.1-apache

# metadata
LABEL org.opencontainers.image.title="AUR-GAME"
LABEL org.opencontainers.image.source="https://github.com/willie-bullish/Agecoin_TG-game"

# system deps (if you need ext-mysqli)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    unzip \
 && docker-php-ext-install mysqli opcache zip \
 && rm -rf /var/lib/apt/lists/*

# copy app
WORKDIR /var/www/html
COPY . /var/www/html/

# recommended permissions for logs (container runtime should supply writable dir)
RUN mkdir -p storage/logs && chown -R www-data:www-data storage && chmod -R 750 storage || true

# enable simple apache rewrite & set docroot
RUN a2enmod rewrite headers

EXPOSE 80

# healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s CMD php /var/www/html/api/health.php || exit 1

CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html"]
