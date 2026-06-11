#!/usr/bin/env bash
# 02_php.sh — PHP 8.3 con todas las extensiones requeridas por In-Ventra
#
# Extensiones requeridas por:
#   - Laravel 12:           mbstring, xml, curl, bcmath, tokenizer, openssl, json, ctype, fileinfo
#   - phpspreadsheet/excel: gd, zip, zlib, dom, simplexml, xmlreader, xmlwriter, iconv, libxml
#   - dompdf:               dom, mbstring
#   - spatie/multitenancy:  pdo, pdo_mysql
#   - phpredis:             redis (client nativo, más performante que predis)
#   - prism-php/prism:      curl (llamadas a Groq API)
#   - intl:                 requerido para localización es_AR
set -euo pipefail

info() { echo -e "\033[0;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[0;32m[OK]\033[0m    $*"; }

info "Agregando repositorio PPA de PHP 8.3 (Ondrej)..."
add-apt-repository -y ppa:ondrej/php
apt-get update -qq

info "Instalando PHP 8.3 y extensiones..."
apt-get install -y -qq \
    php8.3 \
    php8.3-fpm \
    libapache2-mod-php8.3 \
    php8.3-cli \
    php8.3-common \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-zip \
    php8.3-gd \
    php8.3-curl \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-redis \
    php8.3-igbinary \
    php8.3-tokenizer \
    php8.3-ctype \
    php8.3-fileinfo \
    php8.3-iconv \
    php8.3-dom \
    php8.3-simplexml \
    php8.3-xmlreader \
    php8.3-xmlwriter \
    php8.3-opcache \
    php8.3-pcntl \
    php8.3-posix \
    php8.3-readline

info "Configurando PHP para producción..."
PHP_INI="/etc/php/8.3/apache2/php.ini"
PHP_CLI_INI="/etc/php/8.3/cli/php.ini"

# Valores tuneados para SaaS multitenant con carga moderada
configure_php() {
    local ini="$1"
    sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$ini"
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 50M/' "$ini"
    sed -i 's/^post_max_size = .*/post_max_size = 55M/' "$ini"
    sed -i 's/^max_execution_time = .*/max_execution_time = 120/' "$ini"
    sed -i 's/^max_input_time = .*/max_input_time = 120/' "$ini"
    sed -i 's/^;date.timezone.*/date.timezone = America\/Argentina\/Buenos_Aires/' "$ini"
    sed -i 's/^expose_php = .*/expose_php = Off/' "$ini"
    sed -i 's/^display_errors = .*/display_errors = Off/' "$ini"
    sed -i 's/^log_errors = .*/log_errors = On/' "$ini"
    sed -i 's/^;error_log = .*/error_log = \/var\/log\/php\/error.log/' "$ini"
}

configure_php "$PHP_INI"
configure_php "$PHP_CLI_INI"

# PHP para queue workers (CLI): más memoria, más tiempo
PHP_CLI_INI_WORKER="/etc/php/8.3/cli/php.ini"
sed -i 's/^max_execution_time = .*/max_execution_time = 0/' "$PHP_CLI_INI_WORKER"
sed -i 's/^memory_limit = .*/memory_limit = 512M/' "$PHP_CLI_INI_WORKER"

info "Configurando OPcache (mejora de rendimiento 30-50%)..."
cat > /etc/php/8.3/mods-available/opcache.ini << 'EOF'
zend_extension=opcache.so
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.fast_shutdown=1
opcache.jit_buffer_size=128M
opcache.jit=1255
EOF

mkdir -p /var/log/php
chown www-data:www-data /var/log/php

info "Instalando Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
chmod +x /usr/local/bin/composer

PHP_VERSION=$(php8.3 -r "echo PHP_VERSION;")
success "PHP $PHP_VERSION instalado con todas las extensiones."
composer --version
