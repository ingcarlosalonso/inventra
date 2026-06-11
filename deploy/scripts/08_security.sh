#!/usr/bin/env bash
# 08_security.sh — UFW, Fail2ban y logrotate de la aplicación
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/../install.conf"

info() { echo -e "\033[0;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[0;32m[OK]\033[0m    $*"; }

# ─── UFW (Firewall) ───────────────────────────────────────────────────────────
info "Configurando firewall UFW..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing

# SSH — ajustar el puerto si se cambió
ufw allow 22/tcp comment "SSH"

# Web
ufw allow 80/tcp comment "HTTP"
ufw allow 443/tcp comment "HTTPS"

# MySQL: solo local (no exponer al mundo)
ufw deny 3306/tcp

# Redis: solo local
ufw deny 6379/tcp

ufw --force enable
ufw status verbose

# ─── Fail2ban ────────────────────────────────────────────────────────────────
info "Configurando Fail2ban..."

cat > /etc/fail2ban/jail.d/inventra.conf << FAIL2BANEOF
[DEFAULT]
bantime  = 3600
findtime = 600
maxretry = 5
destemail = ${ADMIN_EMAIL}
action = %(action_mwl)s

[sshd]
enabled  = true
port     = ssh
logpath  = %(sshd_log)s
maxretry = 3
bantime  = 86400

[apache-auth]
enabled  = true
logpath  = /var/log/apache2/inventra*error.log
maxretry = 6

[apache-badbots]
enabled  = true
logpath  = /var/log/apache2/inventra*access.log
maxretry = 2

[apache-noscript]
enabled  = true
logpath  = /var/log/apache2/inventra*error.log

[apache-overflows]
enabled  = true
logpath  = /var/log/apache2/inventra*error.log
maxretry = 2

# Protección específica para rutas de login de In-Ventra
# (central.in-ventra.com/login y tenant_x.in-ventra.com/login)
[inventra-login]
enabled   = true
filter    = inventra-login
logpath   = /var/log/apache2/inventra*access.log
maxretry  = 10
findtime  = 300
bantime   = 1800
FAIL2BANEOF

# Filtro personalizado para ataques a login de In-Ventra
cat > /etc/fail2ban/filter.d/inventra-login.conf << 'EOF'
[Definition]
failregex = ^<HOST> .* "POST /(login|api/login) HTTP.* (401|422|429)
ignoreregex =
EOF

systemctl enable fail2ban
systemctl restart fail2ban

# ─── Logrotate — logs de In-Ventra ────────────────────────────────────────────
info "Configurando logrotate para logs de la aplicación..."
cat > /etc/logrotate.d/inventra-app << LOGEOF
${DEPLOY_PATH}/storage/logs/*.log {
    daily
    missingok
    rotate ${BACKUP_RETENTION_DAYS}
    compress
    delaycompress
    notifempty
    su www-data www-data
    copytruncate
}

/var/log/php/error.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    su root root
    copytruncate
}
LOGEOF

success "UFW, Fail2ban y logrotate configurados."
