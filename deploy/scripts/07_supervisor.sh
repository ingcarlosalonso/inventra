#!/usr/bin/env bash
# 07_supervisor.sh — Supervisor para queue workers de In-Ventra
#
# In-Ventra usa queue:work con driver 'database' (ver .env QUEUE_CONNECTION=database)
# Los jobs son tenant-aware (spatie/laravel-multitenancy hace el switch automático)
# El único job actual es LowStockNotification.
# Se configura el número de workers desde install.conf (QUEUE_WORKERS).
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/../install.conf"

info() { echo -e "\033[0;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[0;32m[OK]\033[0m    $*"; }

info "Instalando Supervisor..."
apt-get install -y -qq supervisor

info "Configurando worker para In-Ventra..."
cat > /etc/supervisor/conf.d/inventra-worker.conf << SUPEOF
[program:inventra-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${DEPLOY_PATH}/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --memory=256
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=${QUEUE_WORKERS}
redirect_stderr=true
stdout_logfile=${DEPLOY_PATH}/storage/logs/worker.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=5
stopwaitsecs=3600
startsecs=1
startretries=10
SUPEOF

info "Configurando Supervisor global..."
cat >> /etc/supervisor/supervisord.conf << 'EOF'

[inet_http_server]
port=127.0.0.1:9001
username=inventra
password=inventra_supervisor
EOF

systemctl enable supervisor
systemctl start supervisor
supervisorctl reread
supervisorctl update

success "Supervisor configurado con ${QUEUE_WORKERS} worker(s)."
info "Panel Supervisor disponible en 127.0.0.1:9001 (solo local)."
