#!/usr/bin/env bash
# 06_apache.sh — Apache con VirtualHosts wildcard para multitenancy
# Arquitectura: un único VirtualHost *.in-ventra.com → Laravel maneja el routing por subdominio
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/../install.conf"

info() { echo -e "\033[0;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[0;32m[OK]\033[0m    $*"; }

info "Instalando Apache..."
apt-get install -y -qq apache2

info "Habilitando módulos necesarios..."
a2enmod rewrite headers ssl deflate expires proxy_fcgi setenvif
a2enconf php8.3-fpm 2>/dev/null || true

# Usar MPM prefork con mod_php (más simple y estable para este caso)
a2dismod mpm_event 2>/dev/null || true
a2dismod mpm_worker 2>/dev/null || true
a2enmod mpm_prefork
a2enmod php8.3

info "Configurando Apache global..."
cat > /etc/apache2/conf-available/security.conf << 'EOF'
ServerTokens Prod
ServerSignature Off
TraceEnable Off
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options SAMEORIGIN
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
EOF
a2enconf security

# Optimización de rendimiento para mod_php + MPM prefork
cat > /etc/apache2/mods-available/mpm_prefork.conf << 'EOF'
<IfModule mpm_prefork_module>
    StartServers             5
    MinSpareServers          5
    MaxSpareServers         15
    MaxRequestWorkers       75
    MaxConnectionsPerChild 1000
</IfModule>
EOF

info "Creando estructura de directorios..."
mkdir -p "${DEPLOY_PATH}"
mkdir -p "${DEPLOY_PATH}/storage/logs"
chown -R www-data:www-data "${DEPLOY_PATH}"

info "Creando VirtualHost HTTP (in-ventra.com y *.in-ventra.com)..."
cat > "/etc/apache2/sites-available/inventra.conf" << APACHEEOF
# ─── In-Ventra — HTTP → redirige a HTTPS ──────────────────────────────────
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias *.${DOMAIN}

    # Redirigir todo a HTTPS excepto acme-challenge para renovación SSL
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/.well-known/acme-challenge/
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

    ErrorLog  \${APACHE_LOG_DIR}/inventra-error.log
    CustomLog \${APACHE_LOG_DIR}/inventra-access.log combined
</VirtualHost>
APACHEEOF

info "Creando VirtualHost HTTPS wildcard..."
cat > "/etc/apache2/sites-available/inventra-ssl.conf" << APACHEEOF
# ─── In-Ventra — HTTPS Wildcard ───────────────────────────────────────────
<VirtualHost *:443>
    ServerName ${DOMAIN}
    ServerAlias *.${DOMAIN}
    DocumentRoot ${DEPLOY_PATH}/public

    # SSL — Certbot completa estos paths después
    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/${DOMAIN}/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/${DOMAIN}/privkey.pem
    Include /etc/letsencrypt/options-ssl-apache.conf

    # Cabeceras de seguridad
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"

    # Content Security Policy (ajustar si se integran CDNs)
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self';"

    <Directory ${DEPLOY_PATH}/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks

        # Pasar headers de autorización a PHP (requerido por Sanctum)
        CGIPassAuth On
    </Directory>

    # Proteger archivos sensibles
    <FilesMatch "\.(env|log|json|lock|md|sh|sql|conf)$">
        Require all denied
    </FilesMatch>

    # Gzip para performance
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/plain text/css
        AddOutputFilterByType DEFLATE text/javascript application/javascript
        AddOutputFilterByType DEFLATE application/json application/xml
        AddOutputFilterByType DEFLATE image/svg+xml
    </IfModule>

    # Cache de assets compilados por Vite
    <LocationMatch "\.(js|css|woff2?|ttf|otf|eot|svg|png|jpg|gif|ico|webp)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
        Header append Cache-Control "public, immutable"
    </LocationMatch>

    ErrorLog  \${APACHE_LOG_DIR}/inventra-ssl-error.log
    CustomLog \${APACHE_LOG_DIR}/inventra-ssl-access.log combined
</VirtualHost>
APACHEEOF

info "Habilitando VirtualHosts..."
a2dissite 000-default
a2ensite inventra

# El VirtualHost SSL se habilita después de obtener el certificado
info "VirtualHost SSL (inventra-ssl.conf) se habilitará tras obtener el certificado."

info "Configurando logrotate para Apache..."
cat > /etc/logrotate.d/inventra-apache << 'EOF'
/var/log/apache2/inventra*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    sharedscripts
    postrotate
        if /etc/init.d/apache2 status > /dev/null 2>&1; then
            /etc/init.d/apache2 reload > /dev/null 2>&1 || true
        fi
    endscript
}
EOF

systemctl enable apache2
systemctl restart apache2
success "Apache configurado."
