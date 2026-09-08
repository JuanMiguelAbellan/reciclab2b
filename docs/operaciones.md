# Manual de operaciones — ReciclaB2B

Documentación técnica para quien mantiene la plataforma: comandos del día a día, cómo revisar logs, cómo hacer backups, y qué falta y cómo hacerlo para lanzar a producción. No es para los usuarios finales de la app (para eso está [`manual-usuario.md`](manual-usuario.md)).

## Índice

1. [Comandos del día a día (entorno local)](#1-comandos-del-día-a-día-entorno-local)
2. [Cómo revisar logs](#2-cómo-revisar-logs)
3. [Base de datos: backups, restaurar, inspección](#3-base-de-datos-backups-restaurar-inspección)
4. [El primer superadministrador (y por qué NO usar el seeder normal en producción)](#4-el-primer-superadministrador-y-por-qué-no-usar-el-seeder-normal-en-producción)
5. [Estado actual: qué hay y qué falta para lanzar](#5-estado-actual-qué-hay-y-qué-falta-para-lanzar)
6. [Checklist de lanzamiento a producción](#6-checklist-de-lanzamiento-a-producción)
7. [Variables de entorno de producción](#7-variables-de-entorno-de-producción)
8. [Mantenimiento y emergencias](#8-mantenimiento-y-emergencias)

---

## 1. Comandos del día a día (entorno local)

Todo corre en Docker (Sail), no hace falta PHP/Composer/Node instalados en el sistema. `./vendor/bin/sail` no funciona en Git Bash/Windows sin WSL2 en esta máquina — se usa `docker compose` directamente.

```bash
# Levantar / parar contenedores
docker compose up -d
docker compose down

# Artisan, Composer, npm (siempre dentro del contenedor laravel.test)
docker compose exec laravel.test php artisan <comando>
docker compose exec laravel.test composer <comando>
docker compose exec laravel.test npm <comando>

# Migraciones
docker compose exec laravel.test php artisan migrate
docker compose exec laravel.test php artisan migrate:rollback   # deshace el último batch

# Tests
docker compose exec laravel.test php artisan test
docker compose exec laravel.test php artisan test --filter=NombreDelTest

# Calidad de código
docker compose exec laravel.test ./vendor/bin/pint          # formateo PHP
docker compose exec laravel.test npx tsc --noEmit            # comprobación de tipos TS
docker compose exec laravel.test npm run build               # build de producción del frontend

# Frontend en desarrollo (hot reload)
docker compose exec laravel.test npm run dev

# WebSockets (Reverb) — necesario para mensajería/pedidos/badge en tiempo real
docker compose exec -d -u sail laravel.test php artisan reverb:start --host=0.0.0.0 --port=8080

# Consola interactiva (para inspeccionar/crear datos a mano)
docker compose exec laravel.test php artisan tinker
```

**Cuentas de prueba** (solo tras `php artisan migrate:fresh --seed`, ver [sección 4](#4-el-primer-superadministrador-y-por-qué-no-usar-el-seeder-normal-en-producción) sobre por qué esto no debe hacerse en producción): contraseña `password` para todas, emails `superadmin@reciclab2b.test`, `admin@reciclab2b.test`, `productor@reciclab2b.test`, `comprador@reciclab2b.test`, `test@example.com`.

---

## 2. Cómo revisar logs

### 2.1 En vivo, mientras trabajas (recomendado)

El proyecto ya incluye **Laravel Pail** (`laravel/pail`), pensado justo para esto:

```bash
docker compose exec laravel.test php artisan pail
```

Muestra en tiempo real las excepciones y líneas de log de la aplicación, con colores y trazas legibles — mejor experiencia que leer el archivo plano.

### 2.2 El archivo de log de la aplicación

Todo lo que Laravel registra (errores, excepciones no capturadas, `Log::info()`/`Log::error()` que se añadan) va a:

```bash
docker compose exec laravel.test tail -f storage/logs/laravel.log
```

Se rota por defecto (`LOG_STACK=single` en `.env` — un único archivo que crece; si hace falta rotación diaria, cambiar a `LOG_STACK=daily` genera un archivo por día automáticamente).

### 2.3 Logs de los contenedores Docker

```bash
docker compose logs -f laravel.test   # PHP-FPM/Nginx del contenedor principal
docker compose logs -f mysql
docker compose logs -f mailpit
```

Útil para errores que ocurren *antes* de que Laravel llegue a arrancar (fallo de conexión a MySQL al inicio, etc.), que no aparecen en `laravel.log`.

### 2.4 Correos enviados

Ningún correo sale a internet en desarrollo — se capturan en **Mailpit**: <http://localhost:8025>. Ahí se ve el asunto, destinatario y contenido exacto de cada invitación/notificación enviada, tal cual llegaría a un buzón real.

### 2.5 Reverb (WebSockets)

**Aviso importante:** tal y como está documentado hoy (ver README), Reverb se arranca con `docker compose exec -d ...`, es decir, como un proceso *ejecutado dentro* del contenedor pero que no es el proceso principal — su salida **no** aparece en `docker compose logs laravel.test`. Si necesitas ver qué hace Reverb (conexiones, errores de canal, etc.), arráncalo redirigiendo la salida a un archivo:

```bash
docker compose exec -d -u sail laravel.test bash -c \
  "php artisan reverb:start --host=0.0.0.0 --port=8080 >> storage/logs/reverb.log 2>&1"

# y luego
docker compose exec laravel.test tail -f storage/logs/reverb.log
```

---

## 3. Base de datos: backups, restaurar, inspección

### 3.1 Backup manual (mysqldump)

```bash
docker compose exec mysql mysqldump -u sail -p"$(grep ^DB_PASSWORD .env | cut -d= -f2)" laravel > backup_$(date +%Y%m%d_%H%M%S).sql
```

Genera un `.sql` en tu máquina (fuera del contenedor, en el directorio actual). Guárdalo fuera del propio servidor (otro disco, S3, etc.) — un backup que vive solo en el mismo servidor que falla no sirve de mucho.

### 3.2 Restaurar un backup

```bash
docker compose exec -T mysql mysql -u sail -p"$(grep ^DB_PASSWORD .env | cut -d= -f2)" laravel < backup_20260101_120000.sql
```

**Aviso:** esto sobrescribe los datos actuales de la tabla afectada sin pedir confirmación. Antes de restaurar sobre una base con datos reales, haz primero un backup del estado actual.

### 3.3 Inspeccionar datos rápidamente

```bash
docker compose exec laravel.test php artisan tinker
```

```php
// Ejemplos
\App\Models\User::where('status', \App\Enums\UserStatus::Pending)->get(['id', 'email']);
\App\Models\Company::where('status', \App\Enums\CompanyStatus::Pending)->count();
\App\Models\Order::latest()->take(5)->get();
```

### 3.4 ⚠️ Comandos destructivos — nunca en producción sin confirmarlo antes

- `php artisan migrate:fresh` — **borra todas las tablas** y las vuelve a crear vacías.
- `php artisan migrate:fresh --seed` — lo anterior + crea las 5 cuentas de prueba con contraseña `password` (ver [sección 4](#4-el-primer-superadministrador-y-por-qué-no-usar-el-seeder-normal-en-producción)).
- `php artisan db:wipe` — borra todas las tablas sin volver a crearlas.

Estos son perfectos en local para reiniciar de cero. En producción, con datos reales de empresas/pedidos, borrarían todo sin posibilidad de deshacer salvo restaurando un backup.

---

## 4. El primer superadministrador (y por qué NO usar el seeder normal en producción)

`php artisan db:seed` (el `DatabaseSeeder` por defecto) crea automáticamente 5 cuentas con contraseña **`password`** y emails predecibles (`superadmin@reciclab2b.test`, etc.) — perfecto para desarrollo, **pero esos emails y esa contraseña están documentados en el README público de este repositorio**, así que ejecutar ese seeder en producción dejaría el panel de administración accesible para cualquiera que lea el código. **No lo ejecutes en producción.**

En su lugar, en producción:

**1. Solo sembrar los roles/permisos** (esto sí hace falta, no crea usuarios):

```bash
docker compose exec laravel.test php artisan db:seed --class=RolesAndPermissionsSeeder
```

**2. Crear el primer superadministrador a mano**, con un email y contraseña reales, vía `tinker`:

```bash
docker compose exec laravel.test php artisan tinker
```

```php
$user = new \App\Models\User();
$user->first_name = 'Tu nombre';
$user->last_name = 'Tus apellidos';
$user->email = 'tu-email-real@tudominio.com';
$user->password = 'una-contraseña-fuerte-de-verdad'; // se hashea solo (cast 'hashed')
$user->status = \App\Enums\UserStatus::Approved;
$user->approved_at = now();
$user->email_verified_at = now();
$user->save();
$user->assignRole(\App\Enums\RoleName::SuperAdmin->value);
```

A partir de ahí, entra en `/admin` con ese email y ya puedes aprobar el resto de altas (usuarios y empresas reales) desde la interfaz, sin volver a tocar `tinker`.

---

## 5. Estado actual: qué hay y qué falta para lanzar

Para que quede claro qué es real hoy y qué no, antes del checklist:

**Ya existe y está probado:**
- Toda la aplicación (151 tests pasando), corriendo en Docker/Sail — pero configurado para **desarrollo**, no para producción (`APP_ENV=local`, `APP_DEBUG=true`, sin HTTPS, puertos de MySQL expuestos, etc. — ver `compose.yaml`).
- Envío de correo funcional (invitaciones y notificaciones) usando Mailpit — captura local, no un proveedor real.
- **Preparación para producción (código e infraestructura), agnóstica del proveedor final:**
  - `TrustProxies` configurado (`bootstrap/app.php`) + `URL::forceScheme('https')` en producción (`AppServiceProvider`) — necesario en cuanto haya un proxy inverso delante.
  - Páginas de error Inertia propias en español (403/404/419/500/503, `resources/js/pages/errors/Error.tsx`) en vez de la página HTML genérica de Laravel.
  - Sentry (`sentry/sentry-laravel`) integrado pero **inactivo sin `SENTRY_LARAVEL_DSN`** — no hace nada hasta que se rellene esa variable.
  - `compose.prod.yaml` + `docker/caddy/Caddyfile`: overlay de producción sobre el mismo patrón de Sail — Caddy delante (HTTPS automático), MySQL sin exponer al exterior, Reverb como servicio propio con `restart: always`.
  - `.env.production.example`: plantilla con todas las variables de producción (ver [sección 7](#7-variables-de-entorno-de-producción)).
  - CI en GitHub Actions (`.github/workflows/ci.yml`): tests + Pint + `tsc --noEmit` + build del frontend en cada push/PR a `main`.
  - Script de backup (`scripts/backup-db.sh`), listo para cron — guarda `.sql.gz` local y purga los de más de 14 días; la subida a almacenamiento externo se añade cuando se decida el hosting.

**No existe todavía (depende de dos decisiones pendientes, no de más código):**
- Ningún servidor de producción, dominio ni proveedor de correo transaccional real elegidos — hasta entonces, `APP_DOMAIN`, `REVERB_HOST`/`MAIL_HOST` y demás quedan como placeholders en `.env.production.example`.
- Backups fuera del propio servidor (S3/Backblaze/etc.) — el script ya hace la parte local, falta el destino externo.
- Alta en Sentry (o el proveedor de monitorización que se elija) — el código ya está, solo falta el DSN.

En cuanto se decidan esas dos cosas, desplegar es: rellenar `.env` de producción con las credenciales reales, `docker compose -f compose.prod.yaml up -d`, y los pasos de la sección 6.2.

---

## 6. Checklist de lanzamiento a producción

### 6.1 Elegir dónde se aloja

No hay que decidir esto ahora mismo, pero tres caminos razonables, de menos a más gestionado:

| Opción | Qué implica | Cuándo tiene sentido |
|---|---|---|
| **VPS propio + Docker Compose** | Un servidor (Hetzner, DigitalOcean, OVH...), reutilizando prácticamente el mismo `compose.yaml` con una capa de producción encima (Nginx/Caddy delante para HTTPS, `restart: always`, sin exponer el puerto de MySQL). | Es la continuación más directa de lo que ya tienes montado — mismo Docker, mismos comandos `docker compose exec ...` de este documento. |
| **Laravel Forge / similar** | Servicio que gestiona el servidor (VPS de tu elección) por ti: SSL automático, despliegues por git push, colas y Reverb supervisados sin configurarlo a mano. | Si prefieres no mantener tú la infraestructura Docker en producción. Tiene coste mensual del propio Forge además del VPS. |
| **Laravel Vapor / hosting serverless** | Sin servidor que mantener, todo en AWS Lambda. Requiere adaptar sesiones/broadcasting (Reverb no encaja bien en serverless — habría que migrar a Pusher/Ably para WebSockets). | Solo si se prevé mucho tráfico variable y se acepta ese cambio de mensajería en tiempo real. |

Para este proyecto, dado que ya todo está en Docker Compose y usa Reverb (WebSockets propios), la opción de **VPS + Docker Compose** es la que menos cambios de arquitectura pide.

### 6.2 Pasos concretos (asumiendo VPS + Docker Compose)

Ya preparado en el repo (`compose.prod.yaml`, `docker/caddy/Caddyfile`, `.env.production.example`, `scripts/backup-db.sh`) — estos pasos son para el día del despliegue:

1. **Servidor**: aprovisionar un VPS (2 GB RAM mínimo razonable), instalar Docker + Docker Compose, clonar el repo.
2. **Dominio**: apuntar un DNS `A` al servidor.
3. **`.env` de producción**: copiar `.env.production.example` a `.env` y rellenar los valores reales (dominio, `DB_PASSWORD`, credenciales de correo — ver [sección 7](#7-variables-de-entorno-de-producción)).
4. **Proxy inverso con HTTPS**: ya resuelto por el servicio `caddy` de `compose.prod.yaml` — pide el certificado a Let's Encrypt solo con que `APP_DOMAIN` apunte al dominio real y el DNS ya resuelva a este servidor. También reenvía el WebSocket de Reverb (`/app/*`) al servicio `reverb`.
5. **Build de producción**:
   ```bash
   docker compose -f compose.prod.yaml exec laravel.test composer install --no-dev --optimize-autoloader
   docker compose -f compose.prod.yaml exec laravel.test npm run build
   docker compose -f compose.prod.yaml exec laravel.test php artisan config:cache
   docker compose -f compose.prod.yaml exec laravel.test php artisan route:cache
   docker compose -f compose.prod.yaml exec laravel.test php artisan view:cache
   ```
6. **Base de datos**: `compose.prod.yaml` ya no expone el puerto 3306 al exterior (a diferencia de `compose.yaml`, el de desarrollo); solo accesible entre contenedores.
7. **Migraciones + roles + primer superadmin**: sección 4 de este documento.
8. **Reverb**: ya es un servicio propio en `compose.prod.yaml` (`restart: always`, corre como usuario `sail`), no hace falta `exec -d` a mano como en desarrollo.
9. **Correo real**: contratar un proveedor SMTP transaccional y poner sus credenciales en `.env` (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`).
10. **Monitorización de errores** (opcional pero recomendado): crear un proyecto en [sentry.io](https://sentry.io) y poner su DSN en `SENTRY_LARAVEL_DSN`. Sin esto, la app funciona igual — solo no se reportan las excepciones a ningún sitio.
11. **Backups automáticos**: añadir a cron `./scripts/backup-db.sh` (guarda `.sql.gz` en `./backups/`, purga los de más de 14 días) y, además, copiar esos backups fuera del propio servidor (S3, Backblaze, rsync a otra máquina...) — el script no lo hace todavía porque el destino depende del proveedor elegido.
12. **Prueba de humo completa**: registrar una cuenta real de prueba, aprobarla, crear una empresa, publicar una oferta, hacer un pedido, comprobar que el correo llega de verdad y que el chat funciona por WebSocket (`wss://`).

Levantar todo: `docker compose -f compose.prod.yaml up -d`.

---

## 7. Variables de entorno de producción

Ya está todo recogido en `.env.production.example` (copiarlo a `.env` en el servidor y rellenar los valores reales). Lo que cambia respecto a `.env.example` (desarrollo):

| Variable | Desarrollo | Producción |
|---|---|---|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | **`false`** (con `true` en producción, cualquier error muestra trazas de código y rutas del servidor a quien lo provoque) |
| `APP_URL` | `http://localhost` | `https://tudominio.com` |
| `APP_KEY` | generada en local | genera una **nueva** con `php artisan key:generate` en el servidor — no reutilices la de desarrollo |
| `DB_HOST` / `DB_PASSWORD` | contenedor local, contraseña de ejemplo | host real, contraseña fuerte propia del entorno |
| `SESSION_SECURE_COOKIE` | (no está, por defecto sigue `APP_URL`) | añade `SESSION_SECURE_COOKIE=true` para forzar cookies solo por HTTPS |
| `MAIL_MAILER` / `MAIL_HOST` / `MAIL_USERNAME` / `MAIL_PASSWORD` | `smtp` + Mailpit | credenciales reales del proveedor transaccional elegido |
| `BROADCAST_CONNECTION` | `reverb` | `reverb` (igual — Reverb es gratuito y ya está integrado, no hace falta Pusher) |
| `REVERB_HOST` / `VITE_REVERB_HOST` | `localhost` | tu dominio real (para que el frontend sepa a qué WebSocket conectarse) |
| `REVERB_SCHEME` / `VITE_REVERB_SCHEME` | `http` | `https` (para que el navegador use `wss://` en vez de `ws://`) |
| `REVERB_PORT` | `8080` | `8080` (igual — es el puerto **interno**, entre `caddy` y el servicio `reverb`, nunca se expone al exterior) |
| `VITE_REVERB_PORT` | `8080` | **`443`** — el navegador conecta al puerto público de Caddy, no al 8080 interno; si aquí se deja `8080`, el chat/pedidos en tiempo real dejan de funcionar en producción (ver el aviso en `.env.production.example`) |
| `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` | vacías (Reverb las genera al instalar) | genera unas propias de producción, no reutilices las de local |
| `TRUSTED_PROXIES` | (no existe, no hace falta) | `*` — todo el tráfico ya pasa por `caddy` dentro del mismo `compose.prod.yaml`, sin esto Laravel no detecta HTTPS y rompe cookies seguras/redirects |
| `APP_DOMAIN` | (no existe, no hace falta) | tu dominio real — lo usa `docker/caddy/Caddyfile` para pedir el certificado HTTPS automáticamente |
| `SENTRY_LARAVEL_DSN` | vacío (no hace nada) | el DSN del proyecto en sentry.io, si se activa monitorización de errores |

---

## 8. Mantenimiento y emergencias

### 8.1 Docker Desktop se corrompe (solo en la máquina Windows de 12GB RAM)

Si `docker compose up` falla con `read-only file system` o el daemon deja de responder:

```bash
docker compose down
# cerrar Docker Desktop
wsl --unregister docker-desktop
# reabrir Docker Desktop y reintentar
```

Ya hay un límite de memoria en `C:\Users\<usuario>\.wslconfig` (`memory=6GB`) puesto para mitigar esto. Más detalle en la sección "Notas de entorno" del `README.md`.

### 8.2 Permisos de `storage/`/`bootstrap/cache`

Si algo se ejecutó con `docker compose exec` (entra como `root` por defecto) y creó un archivo ahí, las peticiones reales (que corren como `sail`) fallarán con `Permission denied`:

```bash
docker compose exec laravel.test bash -c "chown -R sail:sail storage bootstrap/cache && chmod -R 775 storage bootstrap/cache"
```

### 8.3 Vite no recoge cambios en `npm run dev`

Ya está configurado con `usePolling: true` en `vite.config.ts` porque Docker Desktop en Windows no siempre reenvía eventos de archivo del volumen. Si aun así deja de recoger cambios:

```bash
docker compose exec laravel.test bash -c "pkill -f 'node.*vite'"
docker compose exec -d laravel.test npm run dev
```

### 8.4 Modo mantenimiento

Para sacar la app de circulación brevemente sin pararla (útil antes de una migración delicada en producción):

```bash
docker compose exec laravel.test php artisan down --secret="un-token-para-poder-entrar-igualmente"
# ... hacer el mantenimiento ...
docker compose exec laravel.test php artisan up
```

Visitando `https://tudominio.com/un-token-para-poder-entrar-igualmente` se puede seguir accediendo mientras el resto de visitantes ven la página de mantenimiento.
