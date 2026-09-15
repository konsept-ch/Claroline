FROM php:8.0-apache

LABEL maintainer="Donovan Tengblad"

# Temporary continuity measure: Debian 11 reached end of life on 2026-08-31.
# The snapshot makes the legacy package set reproducible while the PHP/Debian
# upgrade described in docs/incident_docker_build_debian11_eol.md is prepared.
ARG DEBIAN_SNAPSHOT=20260831T235959Z
RUN printf '%s\n' \
  "deb [check-valid-until=no] https://snapshot.debian.org/archive/debian/${DEBIAN_SNAPSHOT} bullseye main" \
  "deb [check-valid-until=no] https://snapshot.debian.org/archive/debian/${DEBIAN_SNAPSHOT} bullseye-updates main" \
  "deb [check-valid-until=no] https://snapshot.debian.org/archive/debian-security/${DEBIAN_SNAPSHOT} bullseye-security main" \
  > /etc/apt/sources.list \
  && rm -rf /etc/apt/sources.list.d/* /var/lib/apt/lists/*

RUN a2enmod rewrite expires ssl headers

RUN curl -sL https://deb.nodesource.com/setup_16.x | bash
RUN apt-get update && apt-get install -y \
  git \
  mariadb-client \
  wget \
  unzip \
  zip \
  libxml2-dev \
  libmcrypt-dev \
  libcurl4-gnutls-dev \
  xz-utils \
  xfonts-75dpi \
  libldap2-dev \
  libpng-dev \
  libjpeg-dev \
  zlib1g-dev \
  libicu-dev \
  libzip-dev \
  libonig-dev \
  g++ \
  ssl-cert \
  curl \
  nodejs\
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) xml mysqli curl zip mbstring gettext pdo_mysql gd exif mbstring
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl

# RUN curl -sS https://getcomposer.org/installer | php
# RUN mv composer.phar /usr/local/bin/composer

COPY --from=composer:2.3 /usr/bin/composer /usr/local/bin/composer

RUN pecl install apcu-beta \
  && echo extension=apcu.so > /usr/local/etc/php/conf.d/apcu.ini

COPY ./.docker/web/config/php.ini /usr/local/etc/php/
COPY ./.docker/web/files/apache2/claroline.conf /etc/apache2/sites-available/
COPY ./.docker/web/files/apache2/claroline-ssl.conf /etc/apache2/sites-available/
RUN a2dissite 000-default && a2dissite default-ssl && a2ensite claroline.conf

COPY . /var/www/html/claroline
WORKDIR /var/www/html/claroline

RUN mkdir -p var files/config config
RUN chown -R www-data:www-data var files config
RUN chmod -R 750 var files config
RUN chmod -R 755 public

RUN chmod 644 ./.docker/mysql

RUN composer install --no-dev --optimize-autoloader
RUN rm ./config/parameters.yml

RUN npm install --legacy-peer-deps
RUN npm run webpack

COPY ./config /var/www/html/initial/config

COPY ./.docker/web/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
