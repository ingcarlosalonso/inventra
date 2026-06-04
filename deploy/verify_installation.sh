#!/usr/bin/env bash
# verify_installation.sh — Verificación completa del servidor In-Ventra
set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
[[ -f "$SCRIPT_DIR/install.conf" ]] && source "$SCRIPT_DIR/install.conf" || {
    DOMAIN="in-ventra.com"
    DEPLOY_PATH="/var/www/inventra"
    DB_APP_USER="inventra"
    DB_APP_PASSWORD=""
    DB_APP_DATABASE="inventra"
    REDIS_PASSWORD=""
}

GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'

PASS=0; FAIL=0; WARN=0

check() {
    local desc="$1"; local cmd="$2"; local expected="${3:-}"
    if eval "$cmd" > /dev/null 2>&1; then
        echo -e "  ${GREEN}✓${NC} $desc"
        ((PASS++))
    else
        echo -e "  ${RED}✗${NC} $desc"
        ((FAIL++))
    fi
}

check_output() {
    local desc="$1"; local cmd="$2"; local expected="$3"
    local result
    result=$(eval "$cmd" 2>/dev/null || echo "")
    if echo "$result" | grep -q "$expected"; then
        echo -e "  ${GREEN}✓${NC} $desc → $result"
        ((PASS++))
    else
        echo -e "  ${RED}✗${NC} $desc (esperado: $expected, obtenido: $result)"
        ((FAIL++))
    fi
}

warn_check() {
    local desc="$1"; local cmd="$2"
    if eval "$cmd" > /dev/null 2>&1; then
        echo -e "  ${GREEN}✓${NC} $desc"
        ((PASS++))
    else
        echo -e "  ${YELLOW}⚠${NC} $desc (no crítico)"
        ((WARN++))
    fi
}

echo ""
echo "══════════════════════════════════════════════════"
echo "  In-Ventra — Verificación de instalación"
echo "══════════════════════════════════════════════════"
echo ""

# ─── PHP ──────────────────────────────────────────────────────────────────────
echo -e "${BLUE}▶ PHP${NC}"
check_output "PHP versión" "php -r 'echo phpversion();'" "8."
check "ext-mbstring"    "php -m | grep mbstring"
check "ext-xml"        "php -m | grep simplexml"
check "ext-zip"        "php -m | grep zip"
check "ext-gd"         "php -m | grep gd"
check "ext-curl"       "php -m | grep curl"
check "ext-bcmath"     "php -m | grep bcmath"
check "ext-intl"       "php -m | grep intl"
check "ext-redis"      "php -m | grep redis"
check "ext-pdo_mysql"  "php -m | grep pdo_mysql"
check "ext-dom"        "php -m | grep dom"
check "ext-ctype"      "php -m | grep ctype"
check "ext-fileinfo"   "php -m | grep fileinfo"
check "ext-iconv"      "php -m | grep iconv"
check "ext-opcache"    "php -m | grep opcache"
check "Composer"       "composer --version"
echo ""

# ─── Apache ───────────────────────────────────────────────────────────────────
echo -e "${BLUE}▶ Apache${NC}"
check "Apache corriendo"            "systemctl is-active --quiet apache2"
check "mod_rewrite habilitado"      "apache2ctl -M 2>/dev/null | grep rewrite"
check "mod_ssl habilitado"          "apache2ctl -M 2>/dev/null | grep ssl"
check "mod_headers habilitado"      "apache2ctl -M 2>/dev/null | grep headers"
check "VirtualHost inventra activo" "apache2ctl -S 2>/dev/null | grep ${DOMAIN}"
check "Sintaxis Apache OK"          "apache2ctl configtest 2>&1 | grep -q 'Syntax OK'"
echo ""

# ─── MySQL ────────────────────────────────────────────────────────────────────
echo -e "${BLUE}▶ MySQL${NC}"
check "MySQL corriendo"         "systemctl is-active --quiet mysql"
check_output "MySQL versión"    "mysql --version" "8."
check "DB central existe"       "mysql -u${DB_APP_USER} -p${DB_APP_PASSWORD} -e 'USE ${DB_APP_DATABASE};' 2>/dev/null"
check "Usuario app puede conectar" "mysql -u${DB_APP_USER} -p${DB_APP_PASSWORD} -e 'SELECT 1;' 2>/dev/null"
echo ""

# ─── Redis ────────────────────────────────────────────────────────────────────
echo -e "${BLUE}▶ Redis${NC}"
check "Redis corriendo"      "systemctl is-active --quiet redis-server"
check "Redis responde PING"  "redis-cli -a '${REDIS_PASSWORD}' ping 2>/dev/null | grep -q PONG"
check "Redis autenticado"    "redis-cli -a '${REDIS_PASSWORD}' set test_key test_val 2>/dev/null | grep -q OK"
echo ""

# ─── Supervisor ───────────────────────────────────────────────────────────────
echo -e "${BLUE}▶ Supervisor${NC}"
check "Supervisor corriendo"         "systemctl is-active --quiet supervisor"
warn_check "Workers inventra activos" "supervisorctl status inventra-worker 2>/dev/null | grep -q RUNNING"
echo ""

# ─── Cron ─────────────────────────────────────────────────────────────────────
echo -e "${BLUE}▶ Cron${NC}"
check "Cron corriendo"        "systemctl is-active --quiet cron"
check "Cron inventra existe"  "test -f /etc/cron.d/inventra"
echo ""

# ─── SSL ──────────────────────────────────────────────────────────────────────
echo -e "${BLUE}▶ SSL${NC}"
check "Certificado exists"   "test -f /etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
check "Certbot timer activo" "systemctl is-active --quiet certbot.timer"
if [[ -f "/etc/letsencrypt/live/${DOMAIN}/cert.pem" ]]; then
    EXPIRY=$(openssl x509 -enddate -noout -in "/etc/letsencrypt/live/${DOMAIN}/cert.pem" 2>/dev/null | cut -d= -f2)
    DAYS=$(( ( $(date -d "$EXPIRY" +%s) - $(date +%s) ) / 86400 ))
    if [[ $DAYS -gt 30 ]]; then
        echo -e "  ${GREEN}✓${NC} Certificado expira en ${DAYS} días (${EXPIRY})"
        ((PASS++))
    else
        echo -e "  ${RED}✗${NC} Certificado expira en ${DAYS} días — RENOVAR URGENTE"
        ((FAIL++))
    fi
fi
echo ""

# ─── Seguridad ────────────────────────────────────────────────────────────────
echo -e "${BLUE}▶ Seguridad${NC}"
check "UFW activo"         "ufw status | grep -q 'Status: active'"
check "Fail2ban corriendo" "systemctl is-active --quiet fail2ban"
check "Puerto 3306 cerrado" "! ss -tlnp | grep ':3306'"
check "Puerto 6379 cerrado" "! ss -tlnp | grep ':6379'"
echo ""

# ─── Aplicación Laravel ───────────────────────────────────────────────────────
echo -e "${BLUE}▶ Aplicación Laravel${NC}"
if [[ -d "$DEPLOY_PATH" ]]; then
    check "Directorio existe"        "test -d ${DEPLOY_PATH}"
    check ".env existe"              "test -f ${DEPLOY_PATH}/.env"
    check "vendor/ existe"           "test -d ${DEPLOY_PATH}/vendor"
    check "public/build existe"      "test -d ${DEPLOY_PATH}/public/build"
    check "storage/ writable"        "test -w ${DEPLOY_PATH}/storage/logs"
    check "bootstrap/cache writable" "test -w ${DEPLOY_PATH}/bootstrap/cache"
    check "storage/app/public link"  "test -L ${DEPLOY_PATH}/public/storage"
    check "config cacheada"          "test -f ${DEPLOY_PATH}/bootstrap/cache/config.php"
    check "routes cacheadas"         "test -f ${DEPLOY_PATH}/bootstrap/cache/routes-v7.php"
    warn_check "APP_DEBUG=false"     "grep -q 'APP_DEBUG=false' ${DEPLOY_PATH}/.env"
    warn_check "APP_ENV=production"  "grep -q 'APP_ENV=production' ${DEPLOY_PATH}/.env"
else
    echo -e "  ${YELLOW}⚠${NC} Aplicación no desplegada aún. Ejecutar deploy_project.sh --first-run"
    ((WARN++))
fi
echo ""

# ─── Node.js ──────────────────────────────────────────────────────────────────
echo -e "${BLUE}▶ Node.js (para builds)${NC}"
check_output "Node.js versión" "node --version" "v2"
check "npm disponible"         "npm --version"
echo ""

# ─── Resumen ──────────────────────────────────────────────────────────────────
echo "══════════════════════════════════════════════════"
echo -e "  ${GREEN}✓ Pasados: ${PASS}${NC}  |  ${RED}✗ Fallidos: ${FAIL}${NC}  |  ${YELLOW}⚠ Advertencias: ${WARN}${NC}"
echo "══════════════════════════════════════════════════"
echo ""

if [[ $FAIL -eq 0 ]]; then
    echo -e "${GREEN}El servidor está listo para producción.${NC}"
else
    echo -e "${RED}Hay ${FAIL} verificaciones fallidas. Revisar antes de continuar.${NC}"
    exit 1
fi
