# ReciclaB2B

Plataforma privada B2B para conectar generadores de cartón y plástico reciclable con compradores, distribuidores, almacenes locales e industrias.

## Stack tecnológico

- Laravel 13 (PHP 8.5, vía imagen de Sail)
- React + TypeScript + Inertia.js (se instalará en la Fase 1)
- Tailwind CSS 4
- MySQL 8.4
- Laravel Reverb (WebSockets propios, sin servicios externos) + Laravel Echo para mensajería en tiempo real
- Laravel Sail (Docker) como entorno de desarrollo local

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)

No hace falta instalar PHP, Composer, Node ni MySQL en el sistema: todo corre dentro de contenedores.

### Nota para Windows sin WSL2

El script de conveniencia `./vendor/bin/sail` solo funciona en macOS, Linux o WSL2 (comprueba el sistema operativo con `uname`). Si trabajas desde Git Bash / PowerShell en Windows sin una distribución WSL2 instalada, usa `docker compose` directamente como se indica más abajo.

## Puesta en marcha

```bash
# Construir y levantar los contenedores (app Laravel + MySQL)
WWWUSER=$(id -u) WWWGROUP=$(id -g) docker compose up -d --build

# Ejecutar las migraciones
docker compose exec laravel.test php artisan migrate

# Instalar dependencias de frontend y compilar assets
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build
```

La aplicación queda disponible en <http://localhost>.

Para desarrollo con recarga en caliente de Vite:

```bash
docker compose exec laravel.test npm run dev
```

Y para que funcione la mensajería en tiempo real, el servidor de WebSockets (Reverb) también tiene que estar corriendo:

```bash
docker compose exec -d -u sail laravel.test php artisan reverb:start --host=0.0.0.0 --port=8080
```

Sin esto, `/mensajes` sigue funcionando (los mensajes se guardan igual), pero no llegan en tiempo real hasta recargar la página.

### Comandos habituales

| Tarea | Comando |
|---|---|
| Ejecutar Artisan | `docker compose exec laravel.test php artisan ...` |
| Ejecutar Composer | `docker compose exec laravel.test composer ...` |
| Ejecutar tests | `docker compose exec laravel.test php artisan test` |
| Arrancar el servidor de WebSockets (Reverb) | `docker compose exec -d -u sail laravel.test php artisan reverb:start --host=0.0.0.0 --port=8080` |
| Ver los correos enviados en desarrollo (Mailpit) | abrir <http://localhost:8025> |
| Detener contenedores | `docker compose down` |
| Ver logs | `docker compose logs -f laravel.test` |

### Cuentas de prueba (tras `php artisan migrate:fresh --seed`)

Contraseña para todas: `password`.

| Email | Rol | Empresa |
|---|---|---|
| `superadmin@reciclab2b.test` | Superadmin | — |
| `admin@reciclab2b.test` | Admin | — |
| `productor@reciclab2b.test` | Productor | Reciclajes del Sur (aprobada) |
| `comprador@reciclab2b.test` | Comprador | Distribuciones Levante (aprobada) |
| `test@example.com` | sin rol | sin empresa (útil para probar el alta de empresa) |

Aprobar/rechazar/bloquear usuarios y empresas se hace desde el panel de administración de Filament, no por `tinker`: entra en <http://localhost/admin> con `superadmin@reciclab2b.test` o `admin@reciclab2b.test`.

**Importante:** el panel usa acciones dedicadas (Aprobar/Rechazar/Bloquear) que asignan `status`, `approved_at` y `approved_by` directamente y hacen `save()`, no `update([...])` — esos campos no son mass-assignable a propósito (protección contra que un usuario se autoaprobara vía formulario), así que un `update()`/formulario genérico los ignoraría en silencio.

### Más documentación

- [`docs/comandos.md`](docs/comandos.md) — chuleta rápida: arrancar la app y comandos habituales del día a día.
- [`docs/operaciones.md`](docs/operaciones.md) — manual de operaciones: logs, backups, primer superadmin, checklist de despliegue a producción.
- [`docs/manual-usuario.md`](docs/manual-usuario.md) — manual para usuarios finales de la plataforma.

## Estado del proyecto

- **Fase 0** ✅ — Laravel 13 + Sail (MySQL) funcionando en local.
- **Fase 1** ✅ — React + TypeScript + Inertia + Tailwind instalados y verificados (navegación sin recarga completa).
- **Fase 2** ✅ — Autenticación completa con Laravel Fortify (registro, login, logout, recuperación de contraseña, verificación de email) + estado de usuario (`pending/approved/rejected/blocked/inactive`) con middleware de aprobación.
- **Fase 3** ✅ — Empresas, roles/permisos y panel de administración:
  - ✅ Modelo `Company` + relación `company_user` (varios usuarios por empresa, uno como responsable principal).
  - ✅ Roles y permisos con Spatie Laravel Permission (`superadmin`, `admin`, `producer`, `buyer`).
  - ✅ `CompanyPolicy` / `UserPolicy` (comprueban pertenencia real, no solo el rol).
  - ✅ Flujo de alta de empresa: todo usuario aprobado sin empresa pasa por `/empresa/crear`; la empresa creada queda `pending` hasta aprobación.
  - ✅ **Panel de administración con Filament** (`/admin`) — solo accesible para `superadmin`/`admin` aprobados (`User::canAccessPanel`). `UserResource` y `CompanyResource` con acciones Aprobar/Rechazar/Bloquear; el `UserResource` también permite asignar roles. No incluye creación de usuarios desde el panel (el alta es por registro público) ni edición de los flags `is_current_client`/`is_current_supplier`/`is_managed_by_admin`/`internal_notes` de `Company` (no son mass-assignable; pendiente si se necesitan en el futuro).
- **Fase 4** ✅ — Publicación de ofertas (`/ofertas`):
  - ✅ Modelo `Offer` (material `carton`/`plastico`, cantidad, precio, año de generación, humedad/impurezas opcionales, ubicación, descripción) ligado a la empresa (`company_id`) y a quien la creó (`created_by`).
  - ✅ Ciclo de vida completo: `Borrador → Publicada ⇄ Pausada → Cerrada` (cerrada es definitivo), implementado con Actions dedicadas (`PublishOfferAction`, `PauseOfferAction`, `CloseOfferAction`) que validan la transición de origen y lanzan `RuntimeException` si no es válida.
  - ✅ Cualquier miembro (no solo el responsable principal) de una empresa **aprobada** puede crear/gestionar las ofertas de su empresa (`OfferPolicy`); solo se puede borrar en borrador; una oferta cerrada ya no se puede editar.
  - ✅ Páginas React (`pages/offers/`: Index, Create, Edit) — el listado ("Mis ofertas", enlazado desde `AppLayout`) solo muestra las ofertas de la empresa del usuario.
- **Fase 5** ✅ — Mercado: listado/búsqueda de ofertas para compradores (`/mercado`):
  - ✅ `MarketplaceController` + `Offer::published()` (scope de atributo `#[Scope]`) — cualquier usuario aprobado con empresa aprobada ve las ofertas **publicadas** de todas las empresas (no solo la propia).
  - ✅ Filtros: básicos (material, provincia) siempre visibles + "Más filtros" (precio mín/máx, cantidad mínima) en un desplegable anidado dentro del panel de filtros. Paginado (12 por página).
  - ✅ Detalle de oferta (`/mercado/{offer}`) muestra los datos de contacto de la empresa (teléfono/email/persona de contacto). Una oferta no publicada (borrador/pausada/cerrada) da 404 en esta ruta, aunque su dueño la vea en "Mis ofertas".
- **Fase 6** ✅ — Mensajería en tiempo real (`/mensajes`):
  - ✅ Laravel Reverb (WebSockets propios) + Laravel Echo. Una conversación por oferta y empresa compradora (`Conversation`, único por `offer_id`+`buyer_company_id`; se reutiliza si el comprador pulsa "Contactar" otra vez en la misma oferta).
  - ✅ Cualquier miembro de la empresa compradora o de la vendedora puede participar (`ConversationPolicy`); una empresa no puede abrir una conversación consigo misma sobre su propia oferta.
  - ✅ Evento `MessageSent` (`ShouldBroadcastNow`, sin cola) en un canal privado `conversation.{id}`, autorizado en `routes/channels.php`. El botón "Contactar" de `/mercado/{offer}` ya es funcional (antes estaba deshabilitado a propósito).
  - ✅ El hilo de conversación (`pages/conversations/Show.tsx`) no depende de la respuesta HTTP del envío para actualizar los mensajes — todo llega por el WebSocket (incluidos los propios), evitando duplicados.
- **Fase 7** ✅ — Perfil de empresa y gestión de miembros (`/empresa`):
  - ✅ El responsable principal puede editar los datos de su empresa (`CompanyController@edit/update`, reutiliza `CompanyPolicy::update`); el resto de miembros la ven en modo solo lectura.
  - ✅ Añadir miembros por email: solo funciona si esa persona ya tiene una cuenta aprobada y no pertenece a ninguna otra empresa (sin invitaciones por correo todavía — no hay SMTP configurado, es el mismo criterio pragmático que "Contactar" en el mercado). Quitar miembros, salvo al responsable principal (`RemoveCompanyMemberAction` lo bloquea explícitamente).
  - ✅ De paso, `Create.tsx`/`Edit.tsx` comparten `CompanyFormFields` y ahora exponen comunidad autónoma, web y persona de contacto (antes se guardaban en BD pero no había forma de rellenarlos desde el formulario de alta).
- **Fase 8** ✅ — Mensajes no leídos:
  - ✅ `conversation_reads` guarda, por usuario y conversación, el **id** del último mensaje visto (no un timestamp — dos eventos casi simultáneos con precisión de reloj insuficiente podían empatar y dejar algo mal marcado como leído; con id es determinista). Se actualiza al abrir el hilo (`ConversationController@show`).
  - ✅ Contador compartido vía Inertia (`auth.user.unread_conversations_count`, en `HandleInertiaRequests`) — badge en "Mensajes" del `AppLayout` y punto rojo por conversación en el listado.
  - ✅ El badge de "Mensajes" del `AppLayout` se actualiza **en tiempo real**, no solo al navegar: `MessageSent` transmite también en el canal privado personal de cada destinatario (`App.Models.User.{id}`, ya lo deja preparado la instalación de Reverb por defecto), y el layout, al recibirlo, refresca solo la prop `auth` (`router.reload({ only: ['auth'] })`) — no repite el cálculo en el cliente, pide el número ya calculado al servidor. `echo` se importa de forma dinámica en `AppLayout` (igual que Leaflet) para no meter esa dependencia en el bundle de cada página, solo en las que la usan de verdad.
  - ⬜ Sigue habiendo una ventana pequeña: si ya tienes el hilo abierto y te llega un mensaje por Echo, no se marca como "leído" hasta que sales y vuelves a entrar (el marcado como leído ocurre en la petición GET de `ConversationController@show`, no al recibir el mensaje en vivo).
- **Fase 9** ✅ — Mapa y privacidad de ubicación en ofertas (pieza del spec original que faltaba tras el cambio `Production` → `Offer`):
  - ✅ `Offer` distingue coordenadas **exactas** (`exact_latitude`/`exact_longitude`, privadas, las introduce el propietario) de **públicas** (`public_latitude`/`public_longitude`, con un desplazamiento determinista — `App\Support\ApproximateLocation` — nunca aleatorio en cada carga, solo cambia si cambia la ubicación exacta).
  - ✅ Las públicas se calculan siempre en el backend (`Offer::refreshPublicLocation()`, llamado desde las Actions al crear/editar); no son mass-assignable, mismo patrón que `status`/`approved_by`.
  - ✅ `OfferPolicy::viewExactLocation` — solo la empresa propietaria y `admin`/`superadmin` ven la ubicación exacta. El `MarketplaceController` (público) solo serializa las públicas; comprobado con tests que la clave `exact_latitude`/`exact_longitude` **no existe** en esa respuesta (no solo oculta en la UI).
  - ✅ Mapa con Leaflet + React-Leaflet + OpenStreetMap: selector de ubicación (clic en el mapa) en el formulario de oferta, y mapa de resultados en `/mercado` (lista y ficha). Leaflet se carga en un chunk aparte, solo en las páginas que lo usan.
  - ⬜ La ubicación exacta es opcional al crear una oferta; si no se indica, esa oferta simplemente no aparece en el mapa (sigue apareciendo en el listado).
- **Fase 10** ✅ — Invitaciones por email reales:
  - ✅ **Mailpit** añadido a `compose.yaml` (captura de correo para desarrollo, sin necesitar credenciales SMTP reales) — verlos en <http://localhost:8025>. En producción hay que configurar un proveedor transaccional real en `.env` (SendGrid/Mailgun/SES/etc.), eso es una decisión de despliegue, no de código.
  - ✅ El responsable principal invita por email desde `/empresa`; se crea un `CompanyInvitation` y se envía un correo con un enlace **firmado** (`URL::temporarySignedRoute`, caduca en 7 días).
  - ✅ Solo funciona para emails que **no** tienen cuenta todavía (si ya existe una cuenta con ese email, el formulario avisa de usar "añadir miembro" en su lugar, que ya existía). Al aceptar, se crea la cuenta (sigue empezando `pending`, necesita aprobación de admin como cualquier registro) y queda vinculada a la empresa automáticamente — se salta el paso de "crear empresa", no el de aprobación de la cuenta.
  - ✅ El formulario de aceptación hace POST a la **misma URL firmada** (no a una ruta aparte) porque el middleware `signed` valida la URL completa, incluida la query string — si se postea a otra ruta sin firma, se rechaza.
  - ✅ Verificado de extremo a extremo con un envío real a través de Mailpit (asunto, contenido y enlace firmado correctos), no solo con `Notification::fake()`.
- **Fase 11** ✅ — Pedidos (`/pedidos`):
  - ✅ `Order` (cantidad, precio congelado en el momento del pedido, estado) ligado a la oferta, la empresa compradora y opcionalmente a una `Conversation` — se puede pedir desde la ficha de la oferta (`/mercado/{offer}/pedidos`, crea/reutiliza la conversación) o desde dentro del chat (`/mensajes/{conversation}/pedidos`), ambos acaban en el mismo sitio.
  - ✅ Ciclo de vida: `Pendiente → Aceptado/Rechazado`, y desde Aceptado → `Completado` o `Cancelado`. Solo el vendedor acepta/rechaza/completa; el comprador puede cancelar mientras está pendiente, cualquiera de las dos partes una vez aceptado.
  - ✅ **Con control de inventario**: `Offer::availableQuantity()` = cantidad listada − pedidos aceptados/completados. Se recomprueba disponibilidad tanto al pedir como al aceptar (por si dos compradores piden más de lo que hay: el primero en ser aceptado se queda la cantidad, el segundo falla al intentar aceptarlo si ya no queda suficiente). Si aceptar un pedido agota la cantidad, la oferta se cierra sola (`CloseOfferAction`).
  - ⬜ **Limitación conocida y deliberada**: como cerrar una oferta es definitivo (regla ya establecida en la Fase 4), si se cancela después un pedido aceptado que había agotado la oferta, **la oferta se queda cerrada** — no se reabre automáticamente. Si hace falta reabrir, habría que añadir esa funcionalidad explícitamente.
  - ✅ Cada cambio de estado (aceptar/rechazar/completar/cancelar) publica un mensaje automático en la conversación del pedido, atribuido a quien hace la acción — así la otra parte se entera en tiempo real (y por el contador de no leídos) sin tener que mirar `/pedidos`, reutilizando la mensajería ya existente en vez de montar un sistema de notificaciones aparte.
  - ✅ El badge de "Mensajes" es tiempo real (canal privado personal `App.Models.User.{id}`, ya lo deja preparado Reverb por defecto); `echo` se importa dinámicamente en `AppLayout` para no meter laravel-echo/pusher-js en el bundle de páginas que no lo usan.
- **Fase 12** ✅ — Dashboard real (`DashboardController`, antes un placeholder desde la Fase 2 — "el contenido se irá completando en las próximas fases"):
  - ✅ Resumen de la empresa: ofertas activas (publicadas + pausadas), pedidos pendientes de respuesta (como vendedor) y mensajes sin leer, cada tile enlaza al listado correspondiente.
  - ✅ Lista de "pedidos que requieren tu respuesta" (hasta 5, los más recientes) con enlace directo a cada uno.
  - ✅ El personal (`superadmin`/`admin`, que no tiene empresa) ve una versión mínima con enlace al panel de Filament, en vez de fallar al no encontrar `primaryCompany()`.
- **Fase 13** ✅ — Cierre de un hueco de autorización real: ni `ConversationPolicy::startFor` ni `OrderPolicy::placeFor` comprobaban que la oferta siguiera publicada ni que la empresa vendedora siguiera aprobada — solo lo ocultaba el botón en la interfaz, no la ruta. Con una petición directa se podía contactar/pedir sobre una oferta en borrador/pausada/cerrada, o de una empresa bloqueada por un admin.
  - ✅ `Offer::isTradeable()` centraliza la comprobación (publicada + empresa aprobada), usada en ambas Policies. `Offer::visibleInMarket()` (scope nuevo, separado de `published()`) la aplica también al listado y a la ficha del mercado.
  - ✅ Si un admin bloquea una empresa desde Filament, sus ofertas ya publicadas dejan de ser visibles/contactables/pedibles automáticamente — no hace falta ningún paso adicional ni backfill, se comprueba en el momento, así que si se desbloquea la empresa vuelve a funcionar solo.
  - ✅ 7 tests nuevos que fallan sin el fix (comprobado).
- **Fase 14** ✅ — Otro hueco de consistencia cerrado: `AcceptOrderAction`/`OrderPolicy::respond` tampoco comprobaban el estado de la oferta, así que un pedido pendiente sin responder podía quedar "colgado" si el vendedor cerraba la oferta manualmente — y en teoría aceptarse después contra una oferta ya cerrada.
  - ✅ `CloseOfferAction` ahora rechaza automáticamente cualquier pedido pendiente de esa oferta al cerrarla (reutiliza `RejectOrderAction`, con un mensaje distinto — "la oferta se ha cerrado" — para que quede claro por qué). Como pasa por `RejectOrderAction`, también publica el aviso en la conversación del pedido, igual que un rechazo manual.
  - ✅ Efecto secundario correcto gratis: si aceptar un pedido agota la cantidad y cierra la oferta sola, cualquier **otro** pedido pendiente sobre esa misma oferta se rechaza automáticamente también (antes se habría quedado colgado igual).
  - ✅ `CloseOfferAction::handle()` pasó a pedir `User $user` (quién cierra, para atribuir los rechazos) — cambia su firma; si lo usas en otro sitio, revisa las llamadas.
- **Fase 15** ✅ — Notificación por email al aprobar/rechazar/bloquear una cuenta o empresa. Antes, esas acciones desde Filament solo mostraban un aviso interno al propio admin (el toast) — la persona afectada no se enteraba de nada salvo que volviera a entrar a la plataforma.
  - ✅ `UserStatusChangedNotification` y `CompanyStatusChangedNotification` (mismo patrón que `CompanyInvitationNotification` de la Fase 10: usan Mailpit en desarrollo, un proveedor SMTP real en producción). Para empresas, se notifica al `primaryMember()` (nuevo helper en `Company`, el reverso de `User::primaryCompany()`) — una `Company` no tiene email propio ni es `Notifiable`.
  - ✅ Si una empresa pendiente todavía no tiene responsable principal asignado, `notifyPrimaryMember()` no hace nada (no falla) — comprobado con un test dedicado.
  - ✅ Verificado con `Notification::fake()` en los tests **y** con un envío real a través de Mailpit (asunto, destinatario y contenido correctos), mismo criterio que en la Fase 10.
- **Fase 16** ✅ — Auditoría de huecos (aplazada explícitamente hasta cerrar toda la lógica; primer pase). Dos hallazgos reales, no huecos hipotéticos:
  - ✅ **Bug funcional generalizado**: `User::primaryCompany()` solo devuelve algo si el usuario es el responsable principal (`is_primary=true`), pero 8 sitios distintos lo usaban como si significara "la empresa del usuario" en general — un miembro no-principal de una empresa aprobada no podía crear ofertas, no le aparecían en "Mis ofertas", no podía pedir productos ni contactar vendedores, y su dashboard salía vacío, pese a que ya estaba documentado que "cualquier miembro" debía poder hacerlo. Nunca se detectó porque todos los tests y los datos de prueba usan siempre al miembro principal. Nuevo método `User::company()` (cualquier miembro) separado de `primaryCompany()` (solo el responsable); 5 tests nuevos con un miembro no-primario que habrían fallado sin el fix.
  - ✅ **Escalada de privilegios en Filament**: cualquier `admin` podía auto-promocionarse (o promocionar a cualquiera) a `superadmin` desde `/admin/users/{id}/editar`, el único selector de roles no tenía restricción — y `CompanyPolicy::delete` es la única distinción real que existe entre `admin` y `superadmin` en toda la app, así que era puenteable trivialmente. El campo de roles ahora está `disabled()` para quien no sea ya `superadmin` (los campos deshabilitados no se guardan, así que no hay riesgo de despromocionar a alguien sin querer al guardar el formulario). 1 test nuevo que habría fallado sin el fix.
  - ✅ 149/149 tests pasando, `tsc --noEmit` y Pint limpios tras los dos arreglos.
- **Fase 16 (continuación)** ✅ — Segundo pase de la misma auditoría. Tres hallazgos más:
  - ✅ **Un usuario podía pertenecer a dos empresas a la vez**: `/empresa/crear` solo comprobaba que el usuario estuviera aprobado, no que ya perteneciera a una empresa — a diferencia del flujo de "añadir miembro", que sí lo valida. Un usuario con empresa podía crear una segunda y quedar en ambas, rompiendo el supuesto de "una empresa por usuario" en el que se basa `User::company()`, `AddCompanyMemberRequest`, etc. `CompanyPolicy::create` ahora exige también `$user->companies()->doesntExist()`. 1 test nuevo.
  - ✅ **Error 500 en `/cuenta/estado`**: esa ruta solo exige `auth`, no pasa por el middleware que garantiza que el usuario tenga empresa — cualquier `admin`/`superadmin` (que por diseño no tiene empresa) o un usuario recién aprobado que aún no ha creado la suya, al visitarla directamente, hacía crashear el controlador (`->status` sobre `null`). Ahora redirige a `/dashboard`, que ya sabe mandar a cada uno a su sitio. 1 test nuevo.
  - ✅ **Trampa de mass-assignment ya trigger sin activar** (documentada, no explotada): `CreateNewUser` (registro) y `CreateCompanyAction` creaban el registro con `::create([...'status' => ...])`, pero `status` no es mass-assignable en ninguno de los dos modelos — se ignoraba en silencio. No fallaba porque la columna tiene `default('pending')` en la migración y coincide con el valor pretendido, pero es la misma trampa ya documentada como gotcha del proyecto y quedaba inconsistente con el resto del código (que ya usa `new Modelo() + asignación directa + save()`, incluido `AcceptCompanyInvitationAction` para el mismo `User`). Alineado con el patrón del resto del proyecto.
  - ✅ 151/151 tests pasando, `tsc --noEmit` y Pint limpios tras los tres arreglos.
- **Fase 17** ✅ — Preparación para producción (infraestructura y código, sin desplegar todavía — quedan dos decisiones pendientes: dónde alojar y qué proveedor de correo transaccional usar; ver `docs/operaciones.md` secciones 5 y 6):
  - ✅ `TrustProxies` (`bootstrap/app.php`) + `URL::forceScheme('https')` en producción (`AppServiceProvider`) — necesario en cuanto haya un proxy inverso delante, si no Laravel no detecta HTTPS y rompe cookies seguras/redirects.
  - ✅ Páginas de error Inertia propias en español (403/404/419/500/503, `resources/js/pages/errors/Error.tsx`) en vez de la página HTML genérica de Laravel cuando `APP_DEBUG=false`.
  - ✅ Sentry (`sentry/sentry-laravel`) integrado pero inactivo sin `SENTRY_LARAVEL_DSN` — no requiere ninguna cuenta todavía.
  - ✅ `compose.prod.yaml` + `docker/caddy/Caddyfile`: overlay de producción sobre el mismo patrón de Sail — Caddy delante con HTTPS automático (Let's Encrypt), MySQL sin exponer al exterior, Reverb como servicio propio (`restart: always`, corre como `sail` no como `root`).
  - ✅ `.env.production.example` con todas las variables de producción documentadas, incluida la trampa de `VITE_REVERB_PORT` (tiene que ser `443`, el puerto público de Caddy, no el `8080` interno de Reverb — si no, el chat/pedidos en tiempo real dejan de funcionar en producción).
  - ✅ CI en GitHub Actions (`.github/workflows/ci.yml`): tests + Pint + `tsc --noEmit` + build del frontend en cada push/PR a `main`.
  - ✅ Script de backup (`scripts/backup-db.sh`), listo para cron — guarda `.sql.gz` local y purga los de más de 14 días; la subida a almacenamiento externo queda pendiente del proveedor elegido.
  - ✅ De paso, primer `composer install`/`npm install` reales en esta máquina Windows (nunca se habían hecho en esta ruta) — reveló que `.env.example` no traía `WWWUSER`/`WWWGROUP` (hacen falta para que `compose.yaml` pueda construir la imagen; en Linux `sail` los rellena solo, en Windows/Git Bash no), ya añadidos con un valor por defecto.
  - ✅ 151/151 tests pasando (tras `npm run build`, si no fallan 24 por no encontrar el manifest de Vite), `tsc --noEmit` y Pint limpios.

### Notas de entorno (para no repetir problemas ya resueltos)

- **Docker Desktop en esta máquina (12GB RAM) se ha corrompido más de una vez bajo carga** (builds pesados). Si `docker compose up` falla con `read-only file system` o el daemon deja de responder: probar primero `docker compose down` → cerrar Docker Desktop → `wsl --unregister docker-desktop` → reabrir Docker Desktop → reintentar. **Ojo:** en la Fase 17 ese comando `wsl --unregister` no funcionó ejecutado desde Git Bash/este entorno (devolvía la ayuda genérica de `wsl.exe` en vez de ejecutarse) ni tampoco desde PowerShell en ese momento — lo único que realmente recuperó el motor de Docker fue **reiniciar la máquina por completo** y volver a abrir Docker Desktop. Si el arreglo de `wsl --unregister` no responde, no insistas mucho: reiniciar Windows es más lento pero fiable. Hay un límite de memoria puesto en `C:\Users\<usuario>\.wslconfig` (`memory=6GB`) para mitigarlo.
- **`storage/logs` y `bootstrap/cache` deben pertenecer al usuario `sail`, no a `root`.** Si algún comando se ejecuta con `docker compose exec` (que por defecto entra como `root`) y crea un archivo nuevo ahí, las peticiones web reales (que corren como `sail`) fallarán con `Permission denied`. Arreglo: `docker compose exec laravel.test bash -c "chown -R sail:sail storage bootstrap/cache && chmod -R 775 storage bootstrap/cache"`.
- **El watcher de archivos de Vite necesita polling** (ya configurado en `vite.config.ts` con `usePolling: true`) porque Docker Desktop en Windows no siempre reenvía los eventos de cambio de archivo del volumen montado. Si `npm run dev` no recoge cambios nuevos, reinícialo: `docker compose exec laravel.test bash -c "pkill -f 'node.*vite'"` y luego `docker compose exec -d laravel.test npm run dev`.
- El script `./vendor/bin/sail` no funciona en Git Bash (solo detecta macOS/Linux/WSL2) — se usa `docker compose` directamente en todo este proyecto, como se documenta arriba.

**Notas específicas de la máquina Linux (Docker nativo, sin Docker Desktop/WSL):**

- `docker compose exec laravel.test ...` sin `-u sail` entra como **root** y cualquier archivo que cree (p. ej. `php artisan make:filament-resource`) queda con dueño `root`, no editable desde el host. Usa `docker compose exec -u sail laravel.test ...` para comandos que generen archivos, o corrige después con `docker compose exec -u root laravel.test bash -c "chown -R sail:sail /var/www/html"` (el usuario `sail` dentro del contenedor está mapeado al UID del host vía `WWWUSER`/`WWWGROUP` en `.env`).
- Para el primer `composer install` en una máquina nueva sin PHP local con la extensión `iconv` (necesaria por `laravel/fortify` → `bacon/bacon-qr-code`), evita tocar la configuración de PHP del sistema: usa la imagen oficial de Sail `docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install` (basta con que cumpla el `^8.3` de `composer.json`; no hace falta que coincida con la versión 8.5 del runtime de Sail).
- `php artisan reverb:install` falla con `NonInteractiveValidationException` al ejecutarlo por `docker compose exec` (pide confirmar el broadcaster por prompt). Publica igualmente `config/broadcasting.php` y `routes/channels.php`, pero hay que poner `BROADCAST_CONNECTION=reverb` en `.env` a mano después.
- Tras cualquier cambio en `compose.yaml` (p. ej. añadir el puerto de Reverb), `docker compose up -d` recrea el contenedor `laravel.test` — hay que volver a lanzar `npm run dev` y `reverb:start` (ver tabla de comandos arriba), no sobreviven a la recreación.
