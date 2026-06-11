#!/usr/bin/env bash
# 01_base.sh — Actualización base y configuración inicial del servidor
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/../install.conf"

info() { echo -e "\033[0;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[0;32m[OK]\033[0m    $*"; }

info "Actualizando paquetes..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

info "Instalando herramientas base..."
apt-get install -y -qq \
    curl wget git unzip zip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg lsb-release \
    htop nano vim \
    ufw fail2ban logrotate \
    cron acl

info "Configurando timezone America/Argentina/Buenos_Aires..."
timedatectl set-timezone America/Argentina/Buenos_Aires

info "Configurando locale es_AR..."
locale-gen es_AR.UTF-8
update-locale LANG=es_AR.UTF-8 LC_ALL=es_AR.UTF-8

info "Configurando hostname..."
hostnamectl set-hostname "inventra-prod"

info "Aplicando configuraciones de seguridad del kernel..."
cat >> /etc/sysctl.conf << 'EOF'
# In-Ventra security hardening
net.ipv4.tcp_syncookies = 1
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.default.rp_filter = 1
net.ipv4.icmp_echo_ignore_broadcasts = 1
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.all.send_redirects = 0
# Increase file descriptor limits for high-traffic multitenancy
fs.file-max = 100000
EOF
sysctl -p > /dev/null 2>&1 || true

info "Configurando límites de archivos..."
cat >> /etc/security/limits.conf << 'EOF'
www-data soft nofile 65536
www-data hard nofile 65536
root soft nofile 65536
root hard nofile 65536
EOF

info "Deshabilitando servicios innecesarios..."
systemctl disable --now snapd.service 2>/dev/null || true

success "Base configurada."
