#!/usr/bin/env bash
# 05_node.sh — Node.js LTS para compilar assets con Vite
# Necesario para: npm run build (Vite 7 + TailwindCSS 4 + Vue 3)
# Solo se usa en deploy/update, no en runtime de PHP
set -euo pipefail

info() { echo -e "\033[0;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[0;32m[OK]\033[0m    $*"; }

info "Instalando Node.js 22 LTS..."
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y -qq nodejs

NODE_VERSION=$(node --version)
NPM_VERSION=$(npm --version)
success "Node.js $NODE_VERSION / npm $NPM_VERSION instalados."
