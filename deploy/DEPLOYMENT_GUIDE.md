# In-Ventra — Guía de Deployment

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

## Configuración DNS (hacer ANTES de instalar el servidor)

El sistema usa subdominios por tenant (`empresa.in-ventra.com`), lo que requiere un **certificado SSL wildcard**
(`*.in-ventra.com`). Let's Encrypt solo emite wildcards via DNS-01 challenge, que necesita una API DNS.

**Don Web no tiene API DNS pública**, así que se usa Cloudflare únicamente como gestor de DNS
(plan free, sin proxy, sin CDN). Don Web sigue siendo el registrar del dominio.

### Paso 1 — Crear cuenta Cloudflare y agregar el dominio

1. Ir a [cloudflare.com](https://cloudflare.com) → crear cuenta gratuita
2. "Add a site" → ingresar `in-ventra.com` → elegir plan **Free**
3. Cloudflare escanea los DNS actuales e importa los registros existentes
4. Al final te da **dos nameservers**, algo como:
   ```
   aria.ns.cloudflare.com
   bob.ns.cloudflare.com
   ```
   (los nombres exactos los asigna Cloudflare, anotalos)

### Paso 2 — Cambiar nameservers en Don Web

1. Entrar al panel de Don Web → **Dominios** → seleccionar `in-ventra.com`
2. Ir a **Servidores de nombres (DNS)** o similar
3. Reemplazar los nameservers actuales por los dos que te dio Cloudflare
4. Guardar — la propagación tarda entre 15 minutos y 2 horas

### Paso 3 — Configurar los registros DNS en Cloudflare

En el panel de Cloudflare → DNS → agregar estos registros (reemplazar `1.2.3.4` con la IP del VPS):

| Tipo | Nombre | Valor   | Proxy         |
|------|--------|---------|---------------|
| A    | `@`    | `1.2.3.4` | **DNS only** (nube gris) |
| A    | `*`    | `1.2.3.4` | **DNS only** (nube gris) |
| A    | `www`  | `1.2.3.4` | **DNS only** (nube gris) |

> **Importante**: El ícono de nube debe quedar **gris** (DNS only), NO naranja. El proxy de Cloudflare
> interfiere con el SSL del servidor. Si lo dejás naranja, HTTPS va a fallar.

### Paso 4 — Crear el API Token de Cloudflare

Este token lo usa certbot para obtener y renovar el certificado wildcard automáticamente.

1. En Cloudflare → click en tu avatar (arriba derecha) → **My Profile**
2. Ir a **API Tokens** → **Create Token**
3. Usar la plantilla **"Edit zone DNS"**
4. En "Zone Resources" → seleccionar **Specific zone** → `in-ventra.com`
5. Crear el token y **copiarlo** (solo se muestra una vez)
6. Guardarlo en `install.conf` como `CF_API_TOKEN`

### Paso 5 — Verificar que el DNS propagó

```bash
# Desde tu máquina local, verificar que resuelve al VPS:
dig in-ventra.com +short
dig central.in-ventra.com +short
dig cualquiercosa.in-ventra.com +short
# Los tres deben devolver la IP del VPS
```

---

## Pre-requisitos antes de instalar

1. **VPS Ubuntu 24.04** con acceso root por SSH
2. **DNS configurado en Cloudflare** apuntando al VPS (ver sección anterior)
3. **API Token de Cloudflare** con permiso `Zone:DNS:Edit` para `in-ventra.com`
4. **Repositorio Git** accesible desde el VPS (SSH key o HTTPS)

## Instalación desde cero

```bash
# 1. Subir la carpeta deploy/ al servidor
scp -r deploy/ root@IP_DEL_VPS:/root/inventra-deploy/

# 2. En el servidor
cd /root/inventra-deploy

# 3. Crear configuración
cp install.conf.example install.conf
nano install.conf    # Completar con tus valores reales

# 4. Agregar repo Git al config
echo 'GIT_REPO="git@github.com:tuusuario/inventra.git"' >> install.conf
echo 'GIT_BRANCH="main"' >> install.conf

# 5. Ejecutar instalación
chmod +x install.sh scripts/*.sh
bash install.sh

# 6. Agregar SSH key del servidor a GitHub (si usás SSH)
cat ~/.ssh/id_rsa.pub   # Agregar en GitHub → Settings → Deploy Keys

# 7. Deploy de la aplicación
bash deploy_project.sh --first-run

# 8. Verificar todo
bash verify_installation.sh
```

## Primer deploy paso a paso

```bash
# Después de install.sh, ejecutar:
bash deploy_project.sh --first-run

# El script hace:
# 1. git clone del repo
# 2. Copia .env.example → .env y llena valores desde install.conf
# 3. composer install --no-dev
# 4. npm ci && npm run build
# 5. php artisan key:generate
# 6. php artisan migrate (DB central)
# 7. php artisan migrate (tenants — ninguno aún, OK)
# 8. php artisan storage:link
# 9. Caches de config/routes/views

# Crear el admin central:
cd /var/www/inventra
php artisan create:central-admin
```

## Deploy de nuevas versiones

```bash
# Desde el servidor, como root o con sudo:
bash /root/inventra-deploy/deploy_project.sh

# El script hace (sin --first-run):
# 1. php artisan down (modo mantenimiento con bypass secret)
# 2. git pull origin main
# 3. composer install --no-dev
# 4. npm ci && npm run build
# 5. php artisan migrate --force (central)
# 6. php artisan tenants:artisan "migrate ..." (todos los tenants)
# 7. Caches Laravel
# 8. Reinicio de queue workers
# 9. php artisan up

# Para acceder durante mantenimiento como admin:
# Visitar: https://in-ventra.com?secret=inventra-deploy-secret
```

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
