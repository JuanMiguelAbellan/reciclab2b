# Chuleta de comandos — ReciclaB2B

Referencia rápida para el día a día en local. Para backups, logs a fondo, primer superadmin y despliegue a producción, ver [`operaciones.md`](operaciones.md).

Todo corre en Docker (Sail): no hace falta PHP, Composer, Node ni MySQL instalados en el sistema. `./vendor/bin/sail` **no funciona** en Git Bash/PowerShell en Windows sin WSL2 — se usa `docker compose` directamente en todos los comandos de abajo.

## Arrancar la app (primera vez en una máquina nueva)

```bash
# 1. Copiar el .env de ejemplo y generar la clave de la app
cp .env.example .env

# 2. Construir y levantar los contenedores (app Laravel + MySQL + Mailpit)
docker compose up -d --build

# 3. Generar APP_KEY
docker compose exec laravel.test php artisan key:generate

# 4. Ejecutar las migraciones
docker compose exec laravel.test php artisan migrate

# 5. Instalar dependencias de frontend y compilar assets
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build
```

La app queda en <http://localhost>. En Windows, si `docker compose up` falla al construir la imagen, comprueba que `.env` tiene `WWWUSER=1000` y `WWWGROUP=1000` (en Linux/macOS esto se rellena solo con `WWWUSER=$(id -u) WWWGROUP=$(id -g)` delante del comando).

Si quieres datos de prueba (usuarios, empresas, ofertas) en vez de una base vacía:

```bash
docker compose exec laravel.test php artisan migrate:fresh --seed
```

⚠️ `migrate:fresh` borra todo lo que hubiera en la base de datos — nunca lo ejecutes en producción sin confirmarlo antes.

## Arrancar la app (ya instalada, día a día)

```bash
# Levantar contenedores (si no están ya arriba)
docker compose up -d

# Frontend con recarga en caliente
docker compose exec laravel.test npm run dev

# WebSockets (Reverb) — necesario para mensajería, pedidos y el contador de
# no leídos en tiempo real. Sin esto la app funciona igual, pero hay que
# recargar la página para ver mensajes nuevos.
docker compose exec -d -u sail laravel.test php artisan reverb:start --host=0.0.0.0 --port=8080
```

Para parar todo: `docker compose down` (los datos de MySQL persisten en un volumen; no se pierden).

## Comandos habituales

| Tarea | Comando |
|---|---|
| Artisan | `docker compose exec laravel.test php artisan <comando>` |
| Composer | `docker compose exec laravel.test composer <comando>` |
| npm | `docker compose exec laravel.test npm <comando>` |
| Ejecutar todos los tests | `docker compose exec laravel.test php artisan test` |
| Ejecutar un test concreto | `docker compose exec laravel.test php artisan test --filter=NombreDelTest` |
| Formateo PHP (Pint) | `docker compose exec laravel.test ./vendor/bin/pint` |
| Comprobar tipos TypeScript | `docker compose exec laravel.test npx tsc --noEmit` |
| Build de producción del frontend | `docker compose exec laravel.test npm run build` |
| Consola interactiva (Tinker) | `docker compose exec laravel.test php artisan tinker` |
| Migrar | `docker compose exec laravel.test php artisan migrate` |
| Deshacer la última migración | `docker compose exec laravel.test php artisan migrate:rollback` |
| Ver logs de la app en vivo | `docker compose exec laravel.test tail -f storage/logs/laravel.log` |
| Ver logs de un contenedor | `docker compose logs -f laravel.test` |
| Ver correos enviados en desarrollo | abrir <http://localhost:8025> (Mailpit) |
| Entrar a una shell del contenedor | `docker compose exec laravel.test bash` |
| Parar contenedores | `docker compose down` |
| Ver estado de los contenedores | `docker compose ps` |

## Cuentas de prueba

Solo existen tras `php artisan migrate:fresh --seed`. Contraseña para todas: `password`.

| Email | Rol | Empresa |
|---|---|---|
| `superadmin@reciclab2b.test` | Superadmin | — |
| `admin@reciclab2b.test` | Admin | — |
| `productor@reciclab2b.test` | Productor | Reciclajes del Sur (aprobada) |
| `comprador@reciclab2b.test` | Comprador | Distribuciones Levante (aprobada) |
| `test@example.com` | sin rol | sin empresa (útil para probar el alta de empresa) |

Panel de administración (aprobar/rechazar/bloquear usuarios y empresas): <http://localhost/admin>, con `superadmin@reciclab2b.test` o `admin@reciclab2b.test`.

## Problemas comunes

- **`docker compose up` falla o el daemon deja de responder** (sobre todo en la máquina Windows de 12GB RAM, bajo carga): `docker compose down` → cerrar Docker Desktop → `wsl --unregister docker-desktop` → reabrir Docker Desktop → reintentar. Si eso no arranca el motor, reiniciar Windows por completo suele ser lo que realmente lo arregla (ver [`operaciones.md`, sección 8.1](operaciones.md#81-docker-desktop-se-corrompe-solo-en-la-máquina-windows-de-12gb-ram)).
- **`Permission denied` al escribir en `storage/` o `bootstrap/cache`**: suele pasar tras ejecutar algo con `docker compose exec` (entra como `root` por defecto) que creó un archivo ahí. Arreglo: `docker compose exec laravel.test bash -c "chown -R sail:sail storage bootstrap/cache && chmod -R 775 storage bootstrap/cache"`.
- **Los tests fallan con "Vite manifest not found"**: falta compilar el frontend al menos una vez. Ejecuta `docker compose exec laravel.test npm run build`.
- **`npm run dev` no recoge cambios**: revisar [`operaciones.md`, sección 8.3](operaciones.md#83-vite-no-recoge-cambios-en-npm-run-dev) (polling de Vite en Docker Desktop).
