#!/usr/bin/env bash
# 10_certbot.sh — Wildcard SSL con Certbot + Cloudflare DNS plugin
#
# Se necesita wildcard (*.in-ventra.com) porque el multitenancy usa subdominios:
#   - central.in-ventra.com   (panel admin central)
#   - tenant.in-ventra.com    (cada cliente)
#   - *.in-ventra.com         (wildcards futuros)
#
# El DNS challenge es OBLIGATORIO para wildcards.
# Proveedor: Cloudflare (configurar CF_API_TOKEN en install.conf).
# Si usás otro proveedor DNS, ver: https://certbot.eff.org/docs/using.html#dns-plugins
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/../install.conf"

info() { echo -e "\033[0;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[0;32m[OK]\033[0m    $*"; }
warn()    { echo -e "\033[1;33m[WARN]\033[0m  $*"; }

info "Instalando Certbot con plugin Cloudflare..."
apt-get install -y -qq python3-certbot-apache python3-certbot-dns-cloudflare

info "Configurando credenciales Cloudflare..."
mkdir -p /etc/letsencrypt/cloudflare
cat > /etc/letsencrypt/cloudflare/credentials.ini << CFEOF
# Cloudflare API token para DNS-01 challenge
# Permisos requeridos: Zone:DNS:Edit (solo para la zona ${DOMAIN})
dns_cloudflare_api_token = ${CF_API_TOKEN}
CFEOF
chmod 600 /etc/letsencrypt/cloudflare/credentials.ini

info "Solicitando certificado wildcard para ${DOMAIN} y *.${DOMAIN}..."
certbot certonly \
    --dns-cloudflare \
    --dns-cloudflare-credentials /etc/letsencrypt/cloudflare/credentials.ini \
    --dns-cloudflare-propagation-seconds 30 \
    -d "${DOMAIN}" \
    -d "*.${DOMAIN}" \
    --email "${ADMIN_EMAIL}" \
    --agree-tos \
    --non-interactive \
    --rsa-key-size 4096

info "Habilitando VirtualHost HTTPS..."
a2ensite inventra-ssl
systemctl reload apache2

info "Configurando renovación automática..."
# Certbot instala su propio timer de systemd en Ubuntu 24.04
# Verificar que está activo:
systemctl enable certbot.timer
systemctl start certbot.timer

# Hook post-renovación: recargar Apache
mkdir -p /etc/letsencrypt/renewal-hooks/deploy
cat > /etc/letsencrypt/renewal-hooks/deploy/reload-apache.sh << 'EOF'
#!/bin/bash
systemctl reload apache2
supervisorctl restart inventra-worker:*
EOF
chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-apache.sh

# Probar renovación (simulación)
info "Probando renovación automática (simulación)..."
certbot renew --dry-run && success "Renovación automática configurada correctamente." \
    || warn "Dry-run falló. Verificar logs en /var/log/letsencrypt/letsencrypt.log"

CERT_EXPIRY=$(openssl x509 -enddate -noout -in "/etc/letsencrypt/live/${DOMAIN}/cert.pem" 2>/dev/null | cut -d= -f2)
success "Certificado SSL obtenido. Expira: ${CERT_EXPIRY}"
