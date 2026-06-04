#!/usr/bin/env bash
# 09_cron.sh — Cron para Laravel Scheduler
#
# El scheduler ejecuta:
#   - daily-cash:auto-manage → Schedule::command(...)->everyMinute()
# Por eso el cron debe dispararse cada minuto.
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/../install.conf"

info() { echo -e "\033[0;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[0;32m[OK]\033[0m    $*"; }

info "Configurando cron para Laravel Scheduler..."

CRON_FILE="/etc/cron.d/inventra"
cat > "$CRON_FILE" << CRONEOF
# In-Ventra — Laravel Scheduler
# Ejecuta schedule:run cada minuto como www-data
# Comandos activos: daily-cash:auto-manage (everyMinute)
* * * * * www-data cd ${DEPLOY_PATH} && php artisan schedule:run >> /dev/null 2>&1

# Backup diario de MySQL a las 3:00 AM
0 3 * * * root /usr/local/bin/inventra-backup.sh >> /var/log/inventra-backup.log 2>&1

# Limpieza de logs viejos de la app (diaria a las 2:00 AM)
0 2 * * * www-data php ${DEPLOY_PATH}/artisan queue:prune-batches --hours=72 >> /dev/null 2>&1
CRONEOF

chmod 644 "$CRON_FILE"

# ─── Script de backup ─────────────────────────────────────────────────────────
info "Creando script de backup..."
cat > /usr/local/bin/inventra-backup.sh << BACKUPEOF
#!/usr/bin/env bash
# Backup de todas las DBs de In-Ventra (central + tenants)
set -euo pipefail

BACKUP_DIR="/var/backups/inventra"
DATE=\$(date +%Y%m%d_%H%M%S)
RETENTION=${BACKUP_RETENTION_DAYS}

mkdir -p "\$BACKUP_DIR"

# Backup de todas las DBs que empiecen con 'inventra'
mysql -u root -p'${DB_ROOT_PASSWORD}' -e "SHOW DATABASES LIKE 'inventra%';" \
    | grep -v Database \
    | while read DB; do
        OUTFILE="\$BACKUP_DIR/\${DB}_\${DATE}.sql.gz"
        mysqldump -u root -p'${DB_ROOT_PASSWORD}' \
            --single-transaction \
            --routines \
            --triggers \
            "\$DB" | gzip > "\$OUTFILE"
        echo "Backup: \$OUTFILE (\$(du -sh \$OUTFILE | cut -f1))"
    done

# Backup del directorio storage (uploads)
tar -czf "\$BACKUP_DIR/storage_\${DATE}.tar.gz" \
    -C "${DEPLOY_PATH}" storage/app/ 2>/dev/null || true

# Limpiar backups viejos
find "\$BACKUP_DIR" -name "*.gz" -mtime +\$RETENTION -delete
echo "Backups anteriores a \$RETENTION días eliminados."
echo "Backup completado: \$DATE"
BACKUPEOF

chmod +x /usr/local/bin/inventra-backup.sh
mkdir -p /var/backups/inventra

success "Cron configurado. Backup diario a las 3:00 AM."
