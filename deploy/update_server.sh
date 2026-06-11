#!/usr/bin/env bash
# update_server.sh — Actualizaciones de seguridad y mantenimiento del servidor
# Ejecutar mensualmente o ante CVEs críticos
set -euo pipefail

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()    { echo -e "${BLUE}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }

[[ $EUID -ne 0 ]] && { echo "Ejecutar como root"; exit 1; }

LOG="/var/log/inventra-server-update.log"
exec > >(tee -a "$LOG") 2>&1
echo "=== Actualización: $(date) ==="

# ─── Sistema operativo ────────────────────────────────────────────────────────
info "Actualizando paquetes del sistema..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq --with-new-pkgs
apt-get autoremove -y -qq
apt-get autoclean -qq
success "Sistema actualizado."

# ─── Fail2ban — actualizar reglas ─────────────────────────────────────────────
info "Reiniciando Fail2ban..."
systemctl restart fail2ban
success "Fail2ban actualizado."

# ─── Verificar servicios ─────────────────────────────────────────────────────
info "Verificando servicios críticos..."
SERVICES=("apache2" "mysql" "redis-server" "supervisor" "cron" "fail2ban")
for svc in "${SERVICES[@]}"; do
    if systemctl is-active --quiet "$svc"; then
        echo -e "  ${GREEN}✓${NC} $svc corriendo"
    else
        warn "$svc NO está corriendo — reiniciando..."
        systemctl restart "$svc"
    fi
done

# ─── Verificar espacio en disco ───────────────────────────────────────────────
info "Espacio en disco:"
df -h / | tail -1
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | tr -d '%')
if [[ $DISK_USAGE -gt 80 ]]; then
    warn "Disco al ${DISK_USAGE}%. Limpiar backups o logs viejos."
fi

# ─── Rotar logs ───────────────────────────────────────────────────────────────
info "Rotando logs..."
logrotate -f /etc/logrotate.d/inventra-app 2>/dev/null || true
logrotate -f /etc/logrotate.d/inventra-apache 2>/dev/null || true

# ─── Verificar SSL ────────────────────────────────────────────────────────────
info "Verificando SSL..."
CONF_FILE="/var/www/html/inventra/deploy/install.conf"
[[ -f "$CONF_FILE" ]] && source "$CONF_FILE"
DOMAIN="${DOMAIN:-in-ventra.com}"
CERT_FILE="/etc/letsencrypt/live/${DOMAIN}/cert.pem"
if [[ -f "$CERT_FILE" ]]; then
    EXPIRY=$(openssl x509 -enddate -noout -in "$CERT_FILE" | cut -d= -f2)
    DAYS=$(( ( $(date -d "$EXPIRY" +%s) - $(date +%s) ) / 86400 ))
    if [[ $DAYS -lt 20 ]]; then
        warn "Certificado expira en ${DAYS} días — forzando renovación..."
        certbot renew --force-renewal
        systemctl reload apache2
    else
        success "Certificado SSL válido por ${DAYS} días más."
    fi
fi

# ─── Limpiar caché de OPcache ─────────────────────────────────────────────────
info "Limpiando OPcache..."
php -r "if (function_exists('opcache_reset')) opcache_reset();" 2>/dev/null || true

echo ""
success "Actualización del servidor completada: $(date)"
