#!/usr/bin/env bash
# =============================================================================
# In-Ventra — Instalación principal
# Ubuntu 24.04 LTS | Apache | PHP 8.3 | MySQL 8 | Redis | Supervisor
# =============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# ─── Colores ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()    { echo -e "${BLUE}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*" >&2; exit 1; }

# ─── Verificaciones previas ───────────────────────────────────────────────────
[[ $EUID -ne 0 ]] && error "Ejecutar como root: sudo bash install.sh"
[[ ! -f "$SCRIPT_DIR/install.conf" ]] && error "Falta install.conf. Copiá install.conf.example y completalo."

source "$SCRIPT_DIR/install.conf"

# Validar variables obligatorias
: "${DOMAIN:?DOMAIN no configurado en install.conf}"
: "${DEPLOY_PATH:?DEPLOY_PATH no configurado en install.conf}"
: "${DB_ROOT_PASSWORD:?DB_ROOT_PASSWORD no configurado en install.conf}"
: "${DB_APP_PASSWORD:?DB_APP_PASSWORD no configurado en install.conf}"
: "${REDIS_PASSWORD:?REDIS_PASSWORD no configurado en install.conf}"
: "${CF_API_TOKEN:?CF_API_TOKEN no configurado en install.conf (Cloudflare token para SSL wildcard)}"

echo ""
echo "================================================"
echo "  In-Ventra — Instalación del servidor"
echo "  Dominio: $DOMAIN"
echo "  Ruta:    $DEPLOY_PATH"
echo "================================================"
echo ""
warn "Este script modificará el sistema. Presioná Ctrl+C para cancelar (10 seg)."
sleep 10

# ─── Registro de instalación ──────────────────────────────────────────────────
LOG_FILE="/var/log/inventra-install.log"
exec > >(tee -a "$LOG_FILE") 2>&1
info "Log guardado en: $LOG_FILE"

# ─── Ejecución de scripts ─────────────────────────────────────────────────────
run_script() {
    local script="$SCRIPT_DIR/scripts/$1"
    [[ ! -f "$script" ]] && error "Script no encontrado: $script"
    info "Ejecutando: $1"
    bash "$script"
    success "Completado: $1"
}

run_script "01_base.sh"
run_script "02_php.sh"
run_script "03_mysql.sh"
run_script "04_redis.sh"
run_script "05_node.sh"
run_script "06_apache.sh"
run_script "07_supervisor.sh"
run_script "08_security.sh"
run_script "09_cron.sh"
run_script "10_certbot.sh"

echo ""
echo "================================================"
success "Instalación base completa."
echo ""
echo "  Próximos pasos:"
echo "  1. Clonar el repositorio en $DEPLOY_PATH"
echo "  2. Ejecutar: bash $SCRIPT_DIR/deploy_project.sh"
echo "  3. Verificar:  bash $SCRIPT_DIR/verify_installation.sh"
echo "================================================"
