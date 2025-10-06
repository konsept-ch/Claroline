FROM php:8.2-apache-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip libzip-dev curl libicu-dev libjpeg62-turbo-dev libpng-dev libfreetype6-dev; \
    docker-php-ext-configure zip; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install pdo_mysql mysqli zip gd intl; \
    a2enmod rewrite; \
    rm -rf /var/lib/apt/lists/*

RUN { \
    echo 'display_errors = Off'; \
    echo 'display_startup_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_reporting = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED'; \
    echo 'memory_limit = 512M'; \
} > /usr/local/etc/php/conf.d/claroline.ini

WORKDIR /var/www/html

COPY . /var/www/html
RUN mkdir -p /var/www/html/files/config
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN set -eux; \
    TARGET=/var/www/html; \
    if [ -d /var/www/html/public ]; then TARGET=/var/www/html/public; fi; \
    if [ -d /var/www/html/web ]; then TARGET=/var/www/html/web; fi; \
    sed -ri -e "s!DocumentRoot /var/www/html!DocumentRoot ${TARGET}!g" /etc/apache2/sites-available/000-default.conf; \
    printf '<Directory %s>\nOptions Indexes FollowSymLinks\nAllowOverride All\nRequire all granted\n</Directory>\n' "${TARGET}" > /etc/apache2/conf-enabled/zzz-docroot.conf; \
    echo "Using Apache DocumentRoot: ${TARGET}"

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --retries=10 CMD curl -fsS http://127.0.0.1/ || exit 1

