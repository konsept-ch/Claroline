FROM php:8.1-apache

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
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=2'; \
        echo 'opcache.max_file_size=0'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

COPY ./.docker.dev/web/config/php.ini /usr/local/etc/php/conf.d/claroline-development.ini
COPY ./.docker.dev/web/files/apache2/claroline.conf /etc/apache2/sites-available/
COPY ./.docker.dev/web/files/apache2/claroline-ssl.conf /etc/apache2/sites-available/

RUN set -eux; \
    printf "ServerName claroline.local\n" > /etc/apache2/conf-available/servername.conf; \
    a2enconf servername; \
    a2enmod rewrite expires ssl headers http2; \
    a2dissite 000-default default-ssl || true; \
    a2ensite claroline.conf claroline-ssl.conf

COPY . /var/www/html/claroline
WORKDIR /var/www/html/claroline

RUN wget https://cs.symfony.com/download/php-cs-fixer-v2.phar -O /php-cs-fixer; \
    echo "diffs=\$(git diff --name-only) && php /php-cs-fixer fix --dry-run --diff --config=.php_cs \$diffs && /var/www/html/claroline/node_modules/.bin/eslint --ext js --ext jsx \$diffs" > /usr/local/bin/check-code; \
    echo "diffs=\$(git diff --name-only) && php /php-cs-fixer fix --diff --config=.php_cs \$diffs && /var/www/html/claroline/node_modules/.bin/eslint --fix --ext js --ext jsx \$diffs" > /usr/local/bin/fix-code; \
    chmod +x /usr/local/bin/check-code /usr/local/bin/fix-code

COPY ./.docker.dev/web/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
