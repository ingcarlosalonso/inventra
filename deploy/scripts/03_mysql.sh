#!/usr/bin/env bash
# 03_mysql.sh — MySQL 8 configurado para multitenancy (múltiples DBs por tenant)
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/../install.conf"

info() { echo -e "\033[0;34m[INFO]\033[0m  $*"; }
success() { echo -e "\033[0;32m[OK]\033[0m    $*"; }

info "Instalando MySQL 8..."
export DEBIAN_FRONTEND=noninteractive
apt-get install -y -qq mysql-server

info "Iniciando MySQL..."
systemctl enable mysql
systemctl start mysql

info "Securizando MySQL y creando usuario de aplicación..."
mysql -u root << SQL
-- Asegurar que root solo acepta conexiones locales
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASSWORD}';

-- Crear base de datos central (landlord)
CREATE DATABASE IF NOT EXISTS \`${DB_APP_DATABASE}\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Crear usuario de aplicación
-- Necesita permisos de CREATE DATABASE para crear DBs de tenants automáticamente
CREATE USER IF NOT EXISTS '${DB_APP_USER}'@'localhost'
    IDENTIFIED WITH mysql_native_password BY '${DB_APP_PASSWORD}';

-- Permisos sobre la DB central
GRANT ALL PRIVILEGES ON \`${DB_APP_DATABASE}\`.* TO '${DB_APP_USER}'@'localhost';

-- Permiso para crear DBs de tenants (in-ventra_tenant_xxx)
-- Se limita por prefijo a nivel de aplicación, aquí damos CREATE a nivel global
-- necesario para spatie/laravel-multitenancy al crear nuevos tenants
GRANT CREATE ON *.* TO '${DB_APP_USER}'@'localhost';

-- Permisos sobre todas las DBs con prefijo tenant (para Spatie multitenancy)
-- Los tenants se crean como: inventra_tenant_{id} o similar
GRANT ALL PRIVILEGES ON \`inventra\_%\`.* TO '${DB_APP_USER}'@'localhost';

FLUSH PRIVILEGES;
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
FLUSH PRIVILEGES;
SQL

info "Configurando MySQL para producción..."
cat > /etc/mysql/conf.d/inventra.cnf << 'MYSQLEOF'
[mysqld]
# Charset
character-set-server  = utf8mb4
collation-server      = utf8mb4_unicode_ci

# Conexiones — ajustar según carga de tenants
max_connections       = 200
connect_timeout       = 10
wait_timeout          = 600
interactive_timeout   = 600

# InnoDB — para multitenancy con muchas DBs pequeñas
innodb_buffer_pool_size       = 512M
innodb_buffer_pool_instances  = 2
innodb_log_file_size          = 128M
innodb_flush_log_at_trx_commit = 2
innodb_file_per_table         = 1

# Query cache
query_cache_type    = 0
query_cache_size    = 0

# Logs lentos (para debugging)
slow_query_log      = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time     = 2

# Seguridad
local_infile        = 0
skip_name_resolve   = 1
sql_mode            = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"

[client]
default-character-set = utf8mb4

[mysql]
default-character-set = utf8mb4
MYSQLEOF

systemctl restart mysql
success "MySQL 8 configurado. Base de datos '${DB_APP_DATABASE}' creada."
