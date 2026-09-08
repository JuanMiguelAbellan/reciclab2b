# Manual de uso — ReciclaB2B

Guía de uso de la plataforma privada B2B que conecta a generadores de cartón y plástico reciclable con compradores, distribuidores, almacenes e industrias. Cubre todas las funcionalidades disponibles, organizadas por lo que puede hacer cada tipo de usuario.

## Índice

1. [Roles de usuario](#1-roles-de-usuario)
2. [Registro y acceso a la plataforma](#2-registro-y-acceso-a-la-plataforma)
3. [Alta y aprobación de tu empresa](#3-alta-y-aprobación-de-tu-empresa)
4. [Panel principal (Dashboard)](#4-panel-principal-dashboard)
5. [Gestión de tu empresa](#5-gestión-de-tu-empresa)
6. [Publicar y gestionar ofertas](#6-publicar-y-gestionar-ofertas)
7. [El mercado: buscar y ver ofertas](#7-el-mercado-buscar-y-ver-ofertas)
8. [Mensajería con otras empresas](#8-mensajería-con-otras-empresas)
9. [Pedidos](#9-pedidos)
10. [Notificaciones por email](#10-notificaciones-por-email)
11. [Panel de administración (solo Admin / Superadmin)](#11-panel-de-administración-solo-admin--superadmin)
12. [Referencia rápida de estados](#12-referencia-rápida-de-estados)
13. [Preguntas frecuentes](#13-preguntas-frecuentes)

---

## 1. Roles de usuario

| Rol | Quién lo tiene | Qué puede hacer |
|---|---|---|
| **Superadministrador** | Equipo de la plataforma | Todo lo del Administrador, además de asignar/cambiar roles de otros usuarios y eliminar empresas. |
| **Administrador** | Equipo de la plataforma | Aprobar/rechazar/bloquear cuentas y empresas desde el panel `/admin`. No puede tocar roles de otros usuarios. |
| **Productor** / **Comprador** | Empresas usuarias | Estos roles son informativos — **en la práctica, cualquier miembro de una empresa aprobada puede tanto publicar ofertas como comprar**, no hay restricción funcional entre "productor" y "comprador" en la interfaz. |

No hace falta pedir cuenta al superadministrador de forma manual: cualquiera se registra desde `/register` y queda pendiente de aprobación.

---

## 2. Registro y acceso a la plataforma

### 2.1 Crear una cuenta

En `/register`, rellena:

- **Nombre** y **Apellidos**
- **Correo electrónico**
- **Contraseña** (y su confirmación)

Al enviarlo, la cuenta se crea con estado **Pendiente**. No se puede acceder a nada de la plataforma hasta que un administrador la apruebe desde el panel `/admin`.

### 2.2 Verificación de email

Tras registrarte, Laravel envía un correo de verificación. Hace falta confirmar el email además de tener la cuenta aprobada por un administrador para poder usar la aplicación con normalidad.

### 2.3 Iniciar sesión / cerrar sesión

En `/login`, con el email y la contraseña. El enlace "Cerrar sesión" está siempre visible en la barra de navegación superior una vez dentro.

### 2.4 Recuperar contraseña

Desde `/login` → "¿Has olvidado tu contraseña?" (`/forgot-password`). Se envía un enlace de restablecimiento al correo.

### 2.5 Qué ves mientras tu cuenta está pendiente/rechazada/bloqueada

Si intentas entrar a cualquier página privada, `/cuenta/estado` te muestra el estado actual de tu cuenta y un mensaje explicativo:

- **Pendiente**: "Tu cuenta está pendiente de aprobación. Te avisaremos por correo en cuanto un administrador la revise."
- **Rechazada**: "Tu solicitud de acceso ha sido rechazada. Contacta con nosotros si crees que es un error."
- **Bloqueada** / **Inactiva**: contacta con el administrador.

---

## 3. Alta y aprobación de tu empresa

Una vez tu cuenta está **aprobada**, si todavía no perteneces a ninguna empresa, la plataforma te lleva automáticamente a `/empresa/crear`.

### 3.1 Crear tu empresa

Formulario con:

- **Nombre comercial**, **Razón social**, **CIF/NIF** (debe ser único en toda la plataforma)
- **Tipo de empresa**: Productor, Distribuidor, Almacén, Industria, Intermediario, Mixta u Otro (es solo descriptivo — no cambia lo que puedes hacer en la plataforma)
- **Dirección**, **Código postal**, **Municipio**, **Provincia**, **Comunidad autónoma**
- **Teléfono**, **Correo de la empresa**, **Sitio web**, **Persona de contacto**

Al crearla, quedas como **responsable principal** de esa empresa, y la empresa queda con estado **Pendiente** de aprobación (por un administrador, en `/admin`).

**Un usuario solo puede pertenecer a una empresa a la vez.** Si ya perteneces a una, no puedes crear ni unirte a otra sin que alguien te quite antes de la que tienes.

### 3.2 Mientras tu empresa está pendiente

No podrás acceder al resto de la plataforma (mercado, ofertas, mensajes, pedidos) hasta que la empresa esté **Aprobada**. `/cuenta/estado` mostrará el estado de la empresa en su lugar.

### 3.3 Unirte a una empresa ya existente

Hay dos formas, y las inicia siempre el **responsable principal** de esa empresa desde `/empresa` (ver [sección 5.2](#52-añadir-miembros)):

- **Añadir por email** (si ya tienes cuenta aprobada y no perteneces a ninguna empresa): te añaden directamente.
- **Invitación por email** (si todavía no tienes cuenta): recibes un correo con un enlace para registrarte; al aceptarlo, tu cuenta se crea (pendiente de aprobación, como cualquier registro) y queda vinculada a esa empresa automáticamente, sin pasar por "crear empresa".

---

## 4. Panel principal (Dashboard)

Es lo primero que ves tras entrar en `/dashboard`, con:

- **Ofertas activas**: ofertas publicadas o pausadas de tu empresa (enlaza a "Mis ofertas").
- **Pedidos por responder**: pedidos pendientes en los que actúas como vendedor (enlaza a "Pedidos").
- **Mensajes sin leer** (enlaza a "Mensajes").
- Un listado con los **5 pedidos más recientes que requieren tu respuesta**, con acceso directo a cada uno.
- Accesos rápidos a Mercado, Publicar oferta, Mi empresa y Mensajes.

Si tu cuenta es de administrador/superadministrador (sin empresa asociada), en su lugar verás un aviso con enlace directo al panel `/admin`.

---

## 5. Gestión de tu empresa

Accesible desde "Mi empresa" en la barra de navegación, o en `/empresa`.

### 5.1 Editar los datos de la empresa

Solo el **responsable principal** puede editar (nombre comercial, razón social, dirección, contacto, etc.). El resto de miembros ven esta página en modo solo lectura.

### 5.2 Añadir miembros

Dos formularios distintos en la misma página, ambos solo visibles para el responsable principal:

- **"Añadir miembro con cuenta existente"**: introduce el email de alguien que ya tiene cuenta aprobada y que no pertenece a otra empresa. Se añade al instante.
- **"Invitar por email"**: para alguien sin cuenta todavía. Se le envía un correo con un enlace de invitación válido durante **7 días**. Las invitaciones pendientes aparecen listadas y se pueden **cancelar** en cualquier momento antes de que se acepten.

### 5.3 Quitar miembros

El responsable principal puede quitar a cualquier miembro salvo a sí mismo (no se puede dejar la empresa sin responsable desde aquí).

---

## 6. Publicar y gestionar ofertas

Sección "Mis ofertas" (`/ofertas`) — accesible para cualquier miembro de una empresa aprobada.

### 6.1 Crear una oferta

Formulario ("Publicar oferta") con:

- **Material**: Cartón o Plástico
- **Cantidad** (toneladas) y **Precio** (€/tonelada)
- **Año de generación**
- **Humedad (%)** e **Impurezas (%)** — opcionales
- **Provincia** y **Municipio**
- **Descripción** libre
- **Ubicación en el mapa**: clic para marcar la ubicación exacta de la partida (opcional). Esta ubicación **exacta** solo la ve tu empresa y el equipo de administración — en el mercado público se muestra una ubicación **aproximada**, desplazada automáticamente respecto a la real, para no revelar la localización exacta de tus instalaciones. Si no marcas ninguna ubicación, la oferta simplemente no aparece en el mapa (pero sí en los listados).

La oferta se crea en estado **Borrador** y no es visible para nadie fuera de tu empresa hasta que la publiques.

### 6.2 Ciclo de vida de una oferta

```
Borrador → Publicada ⇄ Pausada → Cerrada
```

- **Borrador**: editable y eliminable, no visible en el mercado.
- **Publicada**: visible y contactable en el mercado (`/mercado`). Se puede pausar o cerrar.
- **Pausada**: se retira temporalmente del mercado sin perder los datos. Se puede volver a publicar o cerrar.
- **Cerrada**: es **definitivo** — no se puede reabrir ni editar. Úsalo cuando ya no queda producto disponible.

Acciones disponibles en el listado "Mis ofertas" según el estado: **Editar**, **Publicar**, **Pausar**, **Cerrar**, **Eliminar** (solo en borrador).

**Aviso:** si cierras una oferta que tiene pedidos pendientes de respuesta, esos pedidos se rechazan automáticamente (con un mensaje explicándolo en la conversación correspondiente).

### 6.3 Quién puede gestionar las ofertas

Cualquier miembro de tu empresa (no hace falta ser el responsable principal) puede crear y gestionar las ofertas de la empresa, siempre que la empresa esté **Aprobada**.

---

## 7. El mercado: buscar y ver ofertas

En `/mercado`, disponible para cualquier usuario con cuenta y empresa aprobadas (verás también las ofertas de otras empresas, incluidas las publicadas por productores, útil para conocer precios de mercado).

### 7.1 Filtros

- Básicos, siempre visibles: **Material** y **Provincia**.
- "Más filtros" (desplegable): **Precio mínimo/máximo** (€/t) y **Cantidad mínima** (t).

### 7.2 Resultados

Cada tarjeta de oferta muestra material, empresa, precio, cantidad disponible, generación y ubicación, y enlaza a la ficha completa. También hay un **mapa** con todas las ofertas filtradas que tienen ubicación pública.

### 7.3 Ficha de una oferta

En `/mercado/{id}`: todos los detalles (cantidad, precio, humedad, impurezas, descripción, mapa con ubicación aproximada) y los datos de contacto de la empresa vendedora (teléfono, email, persona de contacto), además de los botones **Contactar** y, si hay cantidad disponible, **Hacer pedido**.

Una oferta que no está publicada (o cuya empresa vendedora ya no está aprobada) no es accesible por esta vía aunque tengas el enlace — verás un 404.

---

## 8. Mensajería con otras empresas

### 8.1 Iniciar una conversación

Desde la ficha de una oferta, botón **Contactar**. Se abre (o se reutiliza, si ya existía) una conversación entre tu empresa y la vendedora sobre esa oferta concreta. Cualquier miembro de cualquiera de las dos empresas puede participar en ella.

### 8.2 Chatear

En `/mensajes/{id}`, el chat funciona **en tiempo real** (sin recargar la página) mientras la otra persona esté conectada; si no, el mensaje queda guardado igualmente y lo verá al entrar.

### 8.3 Pedir dentro de la conversación

Si la oferta sigue disponible, hay un formulario directo para **proponer un pedido** desde el propio chat, sin tener que volver a la ficha de la oferta.

### 8.4 Mensajes no leídos

El menú "Mensajes" de la barra de navegación muestra un contador con el número de conversaciones no leídas, que se actualiza al instante en cuanto llega un mensaje nuevo (no hace falta recargar la página). En el listado de conversaciones, las no leídas aparecen destacadas con un punto.

---

## 9. Pedidos

### 9.1 Hacer un pedido

Se puede pedir de dos formas — ambas acaban en la misma conversación:

- Desde la ficha de la oferta en el mercado (`/mercado/{id}`), indicando cuántas toneladas quieres.
- Desde dentro de una conversación ya abierta sobre esa oferta.

Solo puedes indicar hasta la **cantidad disponible** en ese momento (cantidad total de la oferta menos lo ya aceptado/completado de otros pedidos). El precio se **congela** al precio de la oferta en el momento de pedir.

### 9.2 Ciclo de vida de un pedido

```
Pendiente → Aceptado → Completado
         ↘ Rechazado      ↘ Cancelado
         (por el vendedor, si está pendiente)
```

- **Pendiente**: esperando respuesta del vendedor. El comprador puede **cancelarlo** en cualquier momento mientras siga pendiente.
- **Aceptado**: el vendedor lo ha aceptado. Cualquiera de las dos partes puede **cancelarlo** desde aquí si hace falta. El vendedor puede marcarlo como **Completado** cuando la operación se ha cerrado de verdad (entrega, pago, etc.).
- **Rechazado** / **Cancelado** / **Completado**: estados finales, ya no admiten más acciones.

Solo el **vendedor** puede aceptar, rechazar o completar un pedido. El **comprador** solo puede cancelarlo (mientras esté pendiente o aceptado).

### 9.3 Qué pasa con el inventario

Cada vez que se acepta un pedido, se descuenta de la cantidad disponible de la oferta. Si dos compradores piden más de lo que hay, el primero cuya pedido se acepte se queda con la cantidad; el segundo pedido fallará al intentar aceptarlo si ya no queda suficiente (verás un aviso claro, nunca una venta duplicada). Si al aceptar un pedido se agota toda la cantidad, la oferta se **cierra automáticamente** y cualquier otro pedido pendiente sobre ella se rechaza también, avisando por qué en la conversación correspondiente.

**Importante**: si más tarde se cancela un pedido aceptado que había agotado (y cerrado) la oferta, **la oferta no se reabre sola** — habría que crear una oferta nueva si sigue quedando producto.

### 9.4 Dónde consultarlos

En `/pedidos` ves todos tus pedidos, tanto los que has hecho como comprador como los que has recibido como vendedor, con su estado. Cada pedido enlaza a su ficha (`/pedidos/{id}`) con el detalle completo, quién lo creó, quién respondió o canceló, y los botones de acción que correspondan según tu rol y el estado actual.

Cada cambio de estado (aceptar, rechazar, completar, cancelar) publica automáticamente un mensaje en la conversación asociada al pedido, así que la otra parte se entera al instante por el mismo canal de mensajería, sin necesidad de revisar `/pedidos` constantemente.

---

## 10. Notificaciones por email

La plataforma envía correo automáticamente en estas situaciones:

- **Invitación a una empresa** (sección [3.3](#33-unirte-a-una-empresa-ya-existente)): enlace de registro válido 7 días.
- **Cambio de estado de tu cuenta** (aprobada, rechazada o bloqueada por un administrador).
- **Cambio de estado de tu empresa** (aprobada, rechazada o bloqueada) — recibe el correo el **responsable principal** de la empresa.

En el entorno de desarrollo, estos correos no salen a internet: se capturan en **Mailpit** (<http://localhost:8025>), donde se pueden revisar como si fuera una bandeja de entrada.

---

## 11. Panel de administración (solo Admin / Superadmin)

En `/admin` (Filament), solo accesible para usuarios con rol **Administrador** o **Superadministrador** y cuenta aprobada.

### 11.1 Usuarios

Listado con nombre, apellidos, email, roles y estado. Filtros por estado y por rol. Acciones disponibles (piden confirmación):

- **Aprobar** / **Rechazar** (solo si está Pendiente)
- **Bloquear** (solo si está Aprobado)
- **Editar**: datos del usuario. El campo de **roles** solo es editable si quien lo edita ya es **Superadministrador** — un Administrador no puede auto-promocionarse ni promocionar a nadie a Superadministrador.

### 11.2 Empresas

Listado con nombre comercial, CIF/NIF, tipo, provincia y estado. Filtros por estado y por tipo. Mismas acciones que en Usuarios: **Aprobar**, **Rechazar**, **Bloquear**, **Editar**.

Al aprobar/rechazar/bloquear, se envía automáticamente un correo de notificación al responsable principal de la empresa (o al propio usuario, en el caso de cuentas).

**Nota**: bloquear una empresa retira al instante todas sus ofertas publicadas del mercado (dejan de ser visibles, contactables y pedibles) sin necesidad de ningún paso adicional; si se desbloquea, vuelven a estarlo automáticamente.

---

## 12. Referencia rápida de estados

**Estado de una cuenta de usuario / de una empresa** (mismo conjunto para ambas):

| Estado | Significado |
|---|---|
| Pendiente | Recién creada, esperando revisión de un administrador |
| Aprobado/a | Acceso completo a la plataforma |
| Rechazado/a | Solicitud denegada |
| Bloqueado/a | Acceso retirado tras haber estado aprobado |
| Inactivo/a | Desactivado (uso administrativo) |

**Estado de una oferta:**

| Estado | Significado |
|---|---|
| Borrador | Solo visible para tu empresa, editable, eliminable |
| Publicada | Visible y contactable en el mercado |
| Pausada | Retirada temporalmente, se puede reactivar |
| Cerrada | Definitivo, ya no se puede reabrir ni editar |

**Estado de un pedido:**

| Estado | Significado |
|---|---|
| Pendiente | Esperando respuesta del vendedor |
| Aceptado | El vendedor lo ha aceptado, en curso |
| Rechazado | El vendedor lo ha rechazado (o se rechazó automáticamente al cerrarse la oferta) |
| Completado | Operación finalizada |
| Cancelado | Cancelado por comprador o vendedor |

---

## 13. Preguntas frecuentes

**Me acabo de registrar y no puedo entrar a nada.**
Tu cuenta está pendiente de aprobación por un administrador (y también hace falta verificar tu email). Revisa `/cuenta/estado` para ver el motivo exacto.

**¿Por qué no veo el botón de "Hacer pedido" en una oferta?**
Solo aparece si la oferta sigue publicada, la empresa vendedora sigue aprobada, y queda cantidad disponible. Tampoco puedes pedir sobre tu propia oferta.

**¿Puedo pertenecer a dos empresas a la vez?**
No. Si necesitas cambiar de empresa, el responsable principal de la empresa actual tiene que quitarte primero (o pide a un administrador que revise tu caso).

**No me llegan los correos de invitación/notificación.**
En desarrollo local, los correos no salen a tu bandeja real: se capturan en Mailpit (<http://localhost:8025>). En producción, revisa la carpeta de spam o contacta con el administrador.

**Cerré una oferta por error y tenía pedidos pendientes.**
Los pedidos pendientes se rechazan automáticamente al cerrar la oferta y no se puede deshacer — habría que crear una oferta nueva y que el comprador vuelva a pedir.
