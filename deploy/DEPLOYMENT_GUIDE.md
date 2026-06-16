# In-Ventra — Guía de Deployment

> El servidor de producción ya está provisionado y la app está corriendo. Esta guía cubre
> **operación continua** (deploys de nuevas versiones, mantenimiento, monitoreo, backups).
> La instalación inicial desde cero (DNS, Apache, PHP, MySQL, Redis, Certbot, etc.) ya no es
> necesaria y no forma parte de este repo — si en el futuro hay que levantar un server nuevo
> (ej. staging), recrear esos scripts de provisión en base a esta arquitectura.

## Arquitectura del servidor

| Componente | Versión   | Rol                                      |
|------------|-----------|------------------------------------------|
| Ubuntu     | 24.04 LTS | Sistema operativo                        |
| Apache     | 2.4       | Web server + wildcard vhost              |
| PHP        | 8.3       | Runtime (mod_php)                        |
| MySQL      | 8.0       | DB central + DBs por tenant             |
| Redis      | 7.x       | Cache (phpredis)                         |
| Supervisor | 4.x       | Queue workers (2 procesos)               |
| Certbot    | Moderno   | SSL wildcard via Cloudflare DNS (gratis) |
| UFW        | —         | Firewall                                 |
| Fail2ban   | —         | Protección brute-force                   |

## Estructura de dominios

```
in-ventra.com          → Laravel (redirige a HTTPS)
central.in-ventra.com  → Panel admin central (guard: central, modelo: Admin)
*.in-ventra.com        → Tenants (un subdominio por empresa cliente)
```

Un único VirtualHost Apache con `ServerAlias *.in-ventra.com` maneja todos los subdominios. Laravel identifica el tenant
vía `DomainTenantFinder` de spatie/laravel-multitenancy.

## Deploy de nuevas versiones

```bash
# Desde el servidor, como root o con sudo:
bash /root/inventra-deploy/deploy_project.sh

# El script hace:
# 1. php artisan down (modo mantenimiento con bypass secret)
# 2. git pull origin main
# 3. composer install --no-dev
# 4. npm ci && npm run build
# 5. php artisan migrate --force (central)
# 6. php artisan tenants:artisan "migrate ..." (todos los tenants)
# 7. deploy/release_tasks.sh (tareas puntuales de la versión que se está deployando)
# 8. Caches Laravel
# 9. Reinicio de queue workers
# 10. php artisan up

# Para acceder durante mantenimiento como admin:
# Visitar: https://in-ventra.com?secret=inventra-deploy-secret
```

## Tareas puntuales por release (`release_tasks.sh`)

Las migraciones estándar (central + todos los tenants) ya corren solas en cada deploy — no hace falta
declararlas. `deploy/release_tasks.sh` es para lo que una versión necesita **además** de eso: un
seeder puntual, un comando artisan ad-hoc, una limpieza de caché específica, etc.

**Flujo de trabajo:**

1. Al armar el release, agregás un bloque al final de `deploy/release_tasks.sh` bajo un heading con la
   versión (ej. `## v1.1`), llamando a la función `run_once "v1.1-nombre-tarea" -- comando...`.
2. `run_once` chequea contra `storage/app/release_tasks_done` (en el server) si esa tarea ya corrió; si
   ya corrió, la saltea — así el archivo queda en el repo para siempre sin repetir trabajo en deploys futuros.
3. `deploy_project.sh` ejecuta `release_tasks.sh` automáticamente en cada deploy, después de las migraciones.
4. No hay que borrar ni resetear nada después del deploy — simplemente seguís agregando bloques nuevos
   para la próxima versión.

Ver comentarios dentro de `deploy/release_tasks.sh` para el detalle de la función `run_once`.

## Renovación de SSL

El certificado wildcard se renueva **automáticamente** cada 60 días vía:

- `certbot.timer` (systemd) — se ejecuta dos veces al día
- Hook post-renovación: `reload apache2` + restart workers

**Verificar estado:**

```bash
systemctl status certbot.timer
certbot certificates
openssl x509 -enddate -noout -in /etc/letsencrypt/live/in-ventra.com/cert.pem
```

**Forzar renovación manual:**

```bash
certbot renew --force-renewal
systemctl reload apache2
```

## Agregar un nuevo tenant

Los tenants se crean desde el panel central (`central.in-ventra.com`):

1. Login como Admin
2. Ir a `/tenants` → "Nuevo tenant"
3. In-Ventra crea automáticamente la DB del tenant (usando spatie/laravel-multitenancy)
4. El subdominio `nombre.in-ventra.com` ya funciona con el wildcard SSL existente

**No hace falta tocar el servidor para nuevos tenants.**

Si necesitás crear un tenant por CLI:

```bash
cd /var/www/inventra
php artisan tinker
>>> App\Models\Tenant::create(['name' => 'Empresa X', 'database' => 'inventra_empresa_x', ...])
```

## Monitoreo del sistema

```bash
# Estado de todos los servicios críticos
systemctl status apache2 mysql redis-server supervisor fail2ban

# Queue workers
supervisorctl status

# Logs de la aplicación (últimas 50 líneas)
tail -50 /var/www/inventra/storage/logs/laravel.log

# Logs de Apache
tail -50 /var/log/apache2/inventra-ssl-error.log

# IPs baneadas por Fail2ban
fail2ban-client status inventra-login
fail2ban-client status sshd

# Uso de disco
df -h

# Redis stats
redis-cli -a TU_PASSWORD INFO server | grep -E "used_memory_human|connected_clients"

# MySQL: procesos activos
mysql -uroot -p -e "SHOW PROCESSLIST;"

# PHP OPcache stats
php -r "print_r(opcache_get_status());" | grep -A3 "opcache_hit_rate"
```

## Backup y restauración

### Backup manual

```bash
bash /usr/local/bin/inventra-backup.sh
ls -lh /var/backups/inventra/
```

### Restaurar una DB

```bash
# Listar backups disponibles
ls /var/backups/inventra/

# Restaurar DB central
gunzip < /var/backups/inventra/inventra_20260601_030000.sql.gz | mysql -u root -p inventra

# Restaurar DB de un tenant específico
gunzip < /var/backups/inventra/inventra_tenant_5_20260601_030000.sql.gz | mysql -u root -p inventra_tenant_5
```

### Restaurar storage (uploads)

```bash
tar -xzf /var/backups/inventra/storage_20260601_030000.tar.gz -C /var/www/inventra/
chown -R www-data:www-data /var/www/inventra/storage/
```

## Mantenimiento del servidor (mensual)

```bash
bash /root/inventra-deploy/update_server.sh
```

Esto actualiza paquetes del SO, verifica servicios, rota logs y verifica el SSL.

## Troubleshooting frecuente

### La app da 500

```bash
tail -20 /var/www/inventra/storage/logs/laravel.log
tail -20 /var/log/apache2/inventra-ssl-error.log
# Verificar permisos:
ls -la /var/www/inventra/storage/
ls -la /var/www/inventra/bootstrap/cache/
```

### Los tenants no resuelven

```bash
# Verificar que Apache recibe el subdominio:
apache2ctl -S | grep in-ventra
# Verificar DNS wildcard:
dig tenant1.in-ventra.com
```

### Los jobs no se procesan

```bash
supervisorctl status inventra-worker:*
# Si están STOPPED:
supervisorctl start inventra-worker:*
# Ver logs del worker:
tail -50 /var/www/inventra/storage/logs/worker.log
```

### El scheduler no corre

```bash
# Verificar que el cron existe y está activo:
cat /etc/cron.d/inventra
systemctl status cron
# Probar manualmente:
sudo -u www-data php /var/www/inventra/artisan schedule:run
```

### SSL no renueva

```bash
# Verificar credenciales Cloudflare:
cat /etc/letsencrypt/cloudflare/credentials.ini

# Verificar que el token sigue siendo válido (en cloudflare.com → My Profile → API Tokens)
# Si el token fue revocado, crear uno nuevo y actualizar el archivo credentials.ini

# Probar renovación:
certbot renew --dry-run
# Ver logs:
tail -50 /var/log/letsencrypt/letsencrypt.log
```

> **Nota**: Si los registros DNS en Cloudflare tienen el proxy activado (nube naranja),
> el DNS-01 challenge va a fallar. Verificar que los registros A estén en modo **DNS only** (nube gris).

## Extensiones PHP instaladas (justificación)

| Extensión                                 | Paquete que la requiere                          |
|-------------------------------------------|--------------------------------------------------|
| mbstring                                  | Laravel core, dompdf                             |
| xml, simplexml, dom, xmlreader, xmlwriter | phpspreadsheet (Excel)                           |
| zip, zlib                                 | phpspreadsheet (xlsx)                            |
| gd                                        | phpspreadsheet (imágenes en Excel)               |
| curl                                      | Laravel HTTP client, prism-php (Groq API)        |
| bcmath                                    | Laravel (cálculos monetarios)                    |
| intl                                      | Localización es_AR                               |
| redis                                     | phpredis (cliente nativo, más rápido que predis) |
| pdo_mysql                                 | Conexiones MySQL (central + tenants)             |
| ctype, fileinfo, iconv                    | phpspreadsheet                                   |
| opcache                                   | Rendimiento (30-50% menos tiempo de respuesta)   |
| pcntl, posix                              | Queue workers (señales del proceso)              |
