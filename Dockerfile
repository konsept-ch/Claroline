FROM php:8.2-apache

# Enable Apache rewrite and required PHP extensions for MySQL
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends curl; \
    rm -rf /var/lib/apt/lists/*; \
    a2enmod rewrite; \
    docker-php-ext-install pdo_mysql mysqli

# Set document root to /var/www/html/public if it exists (Symfony/Laravel style)
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN { echo 'ServerName localhost'; echo 'DirectoryIndex index.php index.html'; } > /etc/apache2/conf-enabled/zzz-defaults.conf

WORKDIR /var/www/html

# Copy application source
COPY . /var/www/html

# Now that sources are present, set the correct Apache DocumentRoot
RUN set -eux; \
    TARGET=/var/www/html; \
    if [ -d /var/www/html/public ]; then TARGET=/var/www/html/public; fi; \
    if [ -d /var/www/html/web ]; then TARGET=/var/www/html/web; fi; \
    sed -ri -e "s!DocumentRoot /var/www/html!DocumentRoot ${TARGET}!g" /etc/apache2/sites-available/000-default.conf; \
    printf '<Directory %s>\nOptions Indexes FollowSymLinks\nAllowOverride All\nRequire all granted\n</Directory>\n' "${TARGET}" > /etc/apache2/conf-enabled/zzz-docroot.conf; \
    echo "Using Apache DocumentRoot: ${TARGET}"

# Expose Apache port
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --retries=10 CMD curl -fsS http://127.0.0.1/ || exit 1
