FROM php:8.2-apache

LABEL maintainer="Donovan Tengblad"

ENV APACHE_DOCUMENT_ROOT=/var/www/html/claroline/public \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    NODE_OPTIONS=--max-old-space-size=4096

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends gnupg2 ca-certificates; \
    curl -fsSL https://deb.nodesource.com/setup_16.x | bash -; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git \
        nodejs \
        mariadb-client \
        wget \
        unzip \
        zip \
        ssl-cert \
        xz-utils \
        xfonts-75dpi \
        libxml2-dev \
        libcurl4-gnutls-dev \
        libldap2-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        zlib1g-dev \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        g++ \
        tzdata \
        rsync \
        && rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        xml \
        mysqli \
        curl \
        zip \
        mbstring \
        gettext \
        pdo_mysql \
        gd \
        exif \
        intl \
        opcache

COPY --from=composer:2.3 /usr/bin/composer /usr/local/bin/composer

RUN printf "\n" | pecl install apcu \
    && echo "extension=apcu.so" > /usr/local/etc/php/conf.d/apcu.ini

RUN set -eux; \
    { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.max_file_size=0'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY ./.docker/web/config/php.ini /usr/local/etc/php/conf.d/claroline-production.ini
COPY ./.docker/web/files/apache2/claroline.conf /etc/apache2/sites-available/
COPY ./.docker/web/files/apache2/claroline-ssl.conf /etc/apache2/sites-available/

RUN set -eux; \
    printf "ServerName claroline.local\n" > /etc/apache2/conf-available/servername.conf; \
    a2enconf servername; \
    a2enmod rewrite expires ssl headers http2; \
    a2dissite 000-default default-ssl || true; \
    a2ensite claroline.conf claroline-ssl.conf

WORKDIR /var/www/html/claroline
COPY . /var/www/html/claroline

RUN php bin/configure --default

RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader; \
    composer bundles; \
    composer dump-autoload -o; \
    rm -f ./config/parameters.yml

RUN npm install --legacy-peer-deps; \
    npm run webpack

RUN chown -R www-data:www-data var files config; \
    chmod -R 750 var files config; \
    chmod -R 755 public

COPY ./config /var/www/html/initial/config

COPY ./.docker/web/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
