#!/usr/bin/env bash
# 04_redis.sh — Redis con autenticación para In-Ventra
# Uso actual: cache disponible (driver database por defecto, Redis como upgrade)
# Cliente: phpredis (más performante que predis, configurado en .env REDIS_CLIENT=phpredis)
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/../install.conf"

info() { echo -e "\033[0;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[0;32m[OK]\033[0m    $*"; }

info "Instalando Redis..."
apt-get install -y -qq redis-server

info "Configurando Redis para producción..."
REDIS_CONF="/etc/redis/redis.conf"

# Backup de la config original
cp "$REDIS_CONF" "${REDIS_CONF}.backup"

cat > "$REDIS_CONF" << REDISEOF
# In-Ventra Redis configuration
bind 127.0.0.1
port 6379
protected-mode yes

# Autenticación
requirepass ${REDIS_PASSWORD}

# Persistencia: RDB para snapshots periódicos
save 900 1
save 300 10
save 60 10000
dbfilename dump.rdb
dir /var/lib/redis

# Logs
loglevel notice
logfile /var/log/redis/redis-server.log

# Límite de memoria — ajustar según RAM disponible
maxmemory 256mb
maxmemory-policy allkeys-lru

# Conexiones
maxclients 100
timeout 300
tcp-keepalive 300

# Performance
databases 16
tcp-backlog 511
hz 10

# Requerido para queue workers de Laravel con multitenancy
notify-keyspace-events ""

# Seguridad: deshabilitar comandos peligrosos
rename-command FLUSHALL ""
rename-command FLUSHDB ""
rename-command DEBUG ""
rename-command CONFIG "CONFIG_PROTECTED_${REDIS_PASSWORD:0:8}"
REDISEOF

systemctl enable redis-server
systemctl restart redis-server

# Verificar que Redis arrancó correctamente
sleep 1
if redis-cli -a "$REDIS_PASSWORD" ping | grep -q "PONG"; then
    success "Redis corriendo y autenticado."
else
    echo -e "\033[0;31m[ERROR]\033[0m Redis no responde." >&2
    exit 1
fi
