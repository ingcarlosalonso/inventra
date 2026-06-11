#!/usr/bin/env bash
# deploy_project.sh — Deploy/actualización de In-Ventra en producción
#
# Usar para:
#   1. Primer deploy (clona el repo y configura todo)
#   2. Actualizaciones (git pull + migraciones + rebuild assets)
#
# Uso:
#   bash deploy_project.sh              → deploy completo / actualización
#   bash deploy_project.sh --first-run  → primer deploy (genera .env, crea storage link)
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
[[ -f "$SCRIPT_DIR/install.conf" ]] && source "$SCRIPT_DIR/install.conf" || {
    echo "ERROR: Falta install.conf"; exit 1
}

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()    { echo -e "${BLUE}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[ERROR]${NC} $*" >&2; exit 1; }

FIRST_RUN=false
[[ "${1:-}" == "--first-run" ]] && FIRST_RUN=true

GIT_REPO="${GIT_REPO:-}"        # Ej: git@github.com:tuusuario/inventra.git
GIT_BRANCH="${GIT_BRANCH:-main}"

# ─── Mantenimiento ON ─────────────────────────────────────────────────────────
info "Activando modo mantenimiento..."
[[ -d "$DEPLOY_PATH" ]] && sudo -u www-data php "$DEPLOY_PATH/artisan" down \
    --render="errors::503" \
    --retry=60 \
    --secret="inventra-deploy-secret" 2>/dev/null || true

# ─── Función de cleanup en caso de error ─────────────────────────────────────
cleanup_on_error() {
    warn "Error durante el deploy. Restaurando modo activo..."
    sudo -u www-data php "$DEPLOY_PATH/artisan" up 2>/dev/null || true
}
trap cleanup_on_error ERR

# ─── Primer deploy ────────────────────────────────────────────────────────────
if $FIRST_RUN; then
    [[ -z "$GIT_REPO" ]] && error "Configurar GIT_REPO en install.conf para primer deploy."

    info "Clonando repositorio en $DEPLOY_PATH..."
    git clone --branch "$GIT_BRANCH" "$GIT_REPO" "$DEPLOY_PATH"
    chown -R www-data:www-data "$DEPLOY_PATH"

    info "Configurando .env..."
    cp "$DEPLOY_PATH/.env.example" "$DEPLOY_PATH/.env"

    # Actualizar .env con valores de producción
    sed -i "s|APP_ENV=.*|APP_ENV=production|" "$DEPLOY_PATH/.env"
    sed -i "s|APP_DEBUG=.*|APP_DEBUG=false|" "$DEPLOY_PATH/.env"
    sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" "$DEPLOY_PATH/.env"
    sed -i "s|CENTRAL_DOMAIN=.*|CENTRAL_DOMAIN=${CENTRAL_SUBDOMAIN}.${DOMAIN}|" "$DEPLOY_PATH/.env"
    sed -i "s|SUBDOMAIN_URL=.*|SUBDOMAIN_URL=|" "$DEPLOY_PATH/.env"
    sed -i "s|EXT_SUBDOMAIN_URL=.*|EXT_SUBDOMAIN_URL=.${DOMAIN}|" "$DEPLOY_PATH/.env"
    sed -i "s|WEBDOMAIN=.*|WEBDOMAIN=${DOMAIN}|" "$DEPLOY_PATH/.env"
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_APP_DATABASE}|" "$DEPLOY_PATH/.env"
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_APP_USER}|" "$DEPLOY_PATH/.env"
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_APP_PASSWORD}|" "$DEPLOY_PATH/.env"
    sed -i "s|TENANCY_DATABASE=.*|TENANCY_DATABASE=${DB_APP_DATABASE}|" "$DEPLOY_PATH/.env"
    sed -i "s|REDIS_PASSWORD=.*|REDIS_PASSWORD=${REDIS_PASSWORD}|" "$DEPLOY_PATH/.env"
    sed -i "s|REDIS_CLIENT=.*|REDIS_CLIENT=phpredis|" "$DEPLOY_PATH/.env"

    warn "Revisá y completá manualmente el .env antes de continuar:"
    warn "  - APP_KEY (se genera abajo)"
    warn "  - MAIL_* (configuración de email)"
    warn "  - GROQ_API_KEY (si usás el asistente IA)"
    warn "  - SANCTUM_TOKEN_EXPIRATION"
fi

# ─── Git pull (para actualizaciones) ─────────────────────────────────────────
if ! $FIRST_RUN; then
    info "Actualizando código desde git..."
    cd "$DEPLOY_PATH"
    sudo -u www-data git fetch origin
    sudo -u www-data git checkout "$GIT_BRANCH"
    sudo -u www-data git pull origin "$GIT_BRANCH"
fi

cd "$DEPLOY_PATH"

# ─── Permisos ─────────────────────────────────────────────────────────────────
info "Aplicando permisos..."
chown -R www-data:www-data "$DEPLOY_PATH"
find "$DEPLOY_PATH" -type f -exec chmod 644 {} \;
find "$DEPLOY_PATH" -type d -exec chmod 755 {} \;
chmod -R 775 "$DEPLOY_PATH/storage"
chmod -R 775 "$DEPLOY_PATH/bootstrap/cache"
chmod 600 "$DEPLOY_PATH/.env"

# ─── Dependencias PHP ─────────────────────────────────────────────────────────
info "Instalando dependencias PHP (sin dev)..."
sudo -u www-data composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --quiet

# ─── Generar clave (solo primer deploy) ───────────────────────────────────────
if $FIRST_RUN; then
    info "Generando APP_KEY..."
    sudo -u www-data php artisan key:generate --force
fi

# ─── Assets frontend ──────────────────────────────────────────────────────────
info "Compilando assets con Vite..."
npm ci --silent
npm run build --silent

# Limpiar node_modules después del build (no son necesarios en runtime)
rm -rf node_modules

# ─── Migraciones ──────────────────────────────────────────────────────────────
info "Ejecutando migraciones de la base de datos central..."
sudo -u www-data php artisan migrate --force

if $FIRST_RUN; then
    info "Primera instalación — creando tablas de caché y sesiones..."
    sudo -u www-data php artisan cache:table 2>/dev/null || true
    sudo -u www-data php artisan session:table 2>/dev/null || true
    sudo -u www-data php artisan queue:table 2>/dev/null || true
    sudo -u www-data php artisan migrate --force
fi

info "Ejecutando migraciones en todos los tenants..."
sudo -u www-data php artisan tenants:artisan \
    "migrate --path=database/migrations/tenant --database=tenant --force" \
    2>/dev/null || warn "Ningún tenant existe aún (normal en primer deploy)"

# ─── Storage link ─────────────────────────────────────────────────────────────
if $FIRST_RUN; then
    info "Creando enlace simbólico de storage..."
    sudo -u www-data php artisan storage:link
fi

# ─── Optimización Laravel ─────────────────────────────────────────────────────
info "Optimizando Laravel para producción..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan event:cache

# ─── Reiniciar workers ────────────────────────────────────────────────────────
info "Reiniciando queue workers..."
sudo -u www-data php artisan queue:restart
supervisorctl restart "inventra-worker:*" 2>/dev/null || true

# ─── Mantenimiento OFF ────────────────────────────────────────────────────────
info "Desactivando modo mantenimiento..."
sudo -u www-data php artisan up

success "Deploy completado exitosamente."
[[ "$FIRST_RUN" == "true" ]] && warn "Recordá crear el admin central: php artisan create:central-admin"
