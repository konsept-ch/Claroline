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

# snapshot.debian.org is slow and often overloaded (504, 503): apt retries every
# download. See docs/60-infra-run/construction-images-docker.md (CEP workspace).
RUN printf '%s\n' \
  'Acquire::Retries "5";' \
  'Acquire::http::Timeout "60";' \
  'Acquire::https::Timeout "60";' \
  > /etc/apt/apt.conf.d/80-retries

RUN a2enmod rewrite expires ssl headers

# Node.js 16 repository, configured explicitly. The former `setup_16.x | bash`
# ran its own `apt update` against the snapshot and exited 0 when it failed: the
# repository was silently missing, Debian's nodejs 12 got installed without npm,
# and the build broke later on `npm install`.
RUN curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key -o /usr/share/keyrings/nodesource.asc \
  && echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/nodesource.asc] https://deb.nodesource.com/node_16.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list \
  && printf '%s\n' 'Package: nodejs' 'Pin: origin deb.nodesource.com' 'Pin-Priority: 600' \
    > /etc/apt/preferences.d/nodejs

# If the snapshot is still unavailable after a second attempt, fall back to
# archive.debian.org, the official home of bullseye since its end of life: same
# package versions, served by a far more reliable mirror.
# autofs and nfs-common are what Jelastic installs at every redeploy (jem docker
# update) to mount the NFS volume on files/: preinstalled, so a mirror outage can
# no longer leave the container without its files and run as a fresh install.
# The final image keeps archive.debian.org as its apt source, for anything
# installed after the build.
RUN install_packages() { \
    apt-get update && apt-get install -y \
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
      nodejs \
      autofs \
      nfs-common; \
  } \
  && { install_packages \
    || { echo "snapshot.debian.org failed, retrying in 30s" && sleep 30 && install_packages; } \
    || { echo "snapshot.debian.org unavailable, falling back to archive.debian.org" \
      && printf '%s\n' \
        "deb [check-valid-until=no] https://archive.debian.org/debian bullseye main" \
        "deb [check-valid-until=no] https://archive.debian.org/debian bullseye-updates main" \
        "deb [check-valid-until=no] https://archive.debian.org/debian-security bullseye-security main" \
        > /etc/apt/sources.list \
      && install_packages; }; } \
  && rm -rf /var/lib/apt/lists/* \
  && printf '%s\n' \
    "deb [check-valid-until=no] https://archive.debian.org/debian bullseye main" \
    "deb [check-valid-until=no] https://archive.debian.org/debian bullseye-updates main" \
    "deb [check-valid-until=no] https://archive.debian.org/debian-security bullseye-security main" \
    > /etc/apt/sources.list \
  && { npm -v >/dev/null 2>&1 || { echo "npm missing: Node.js was not installed from deb.nodesource.com" && exit 1; }; } \
  && echo "node $(node -v), npm $(npm -v)"

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
