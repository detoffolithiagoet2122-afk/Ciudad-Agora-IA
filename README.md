# Tablón — el corcho digital de tu ciudad

**Tablón** es una aplicación web pensada para resolver un problema muy concreto: mucha gente joven tiene hobbies e intereses (ciclismo, rol, fotografía, running, ajedrez, música, huerta urbana, cine, etc.) pero no tiene un espacio simple —ni físico ni digital— para encontrarse con otras personas que compartan esos intereses, armar un grupo y organizar encuentros reales.

La idea del proyecto es ser una especie de "corcho de anuncios" de barrio, pero digital: te sumás a grupos por tema y zona, publicás en el muro del grupo, proponés encuentros presenciales y confirmás tu asistencia, y podés agregar como amigos a la gente que vas conociendo.

> **Problemática que aborda el proyecto:** la falta de espacios de encuentro para jóvenes con intereses comunes. Muchas personas comparten hobbies o actividades, pero no cuentan con lugares físicos ni herramientas digitales que les permitan conocerse, organizar encuentros y participar de actividades de manera sencilla.

---

## Índice

1. [Qué hace la aplicación](#qué-hace-la-aplicación)
2. [Stack técnico](#stack-técnico)
3. [Estructura del proyecto](#estructura-del-proyecto)
4. [Instalación local con XAMPP](#instalación-local-con-xampp)
5. [Usuario de demo / invitade](#usuario-de-demo--invitade)
6. [Cómo funciona la API](#cómo-funciona-la-api)
7. [Modelo de datos](#modelo-de-datos)
8. [Problemas comunes](#problemas-comunes)

---

## Qué hace la aplicación

Tablón tiene una landing pública y, una vez que iniciás sesión (o entrás como invitade), una app con 5 secciones:

- **Inicio** — resumen / punto de entrada de la cuenta.
- **Explorar** — buscar y descubrir grupos por categoría y zona.
- **Mis grupos** — los grupos a los que ya te uniste.
- **Amigos** — tu lista de amigos, para agregar o quitar gente.
- **Crear grupo** — armar un grupo nuevo (nombre, categoría, zona, descripción), incluso con una categoría personalizada si no existe todavía.

Dentro de cada grupo hay 3 pestañas:

- **Encuentros** — encuentros presenciales propuestos por miembros del grupo (título, fecha/hora en texto libre y lugar), donde cualquier miembro puede marcar "voy" / "no voy".
- **Muro** — un muro de posts tipo timeline donde los miembros publican mensajes.
- **Miembros** — quiénes forman parte del grupo.

Funcionalidades de cuenta:

- Registro con nombre, usuario (`@handle`) y contraseña, eligiendo categorías de interés.
- Login con usuario y contraseña (hasheada con `password_hash` de PHP).
- **Acceso como invitade (demo)**: entra automáticamente con un usuario de prueba ya cargado en la base, sin necesidad de crear una cuenta, ideal para mostrar la app rápido.
- Logout.

---

## Stack técnico

- **Frontend:** un único archivo `index.html` (HTML + CSS + JavaScript vanilla, sin frameworks ni build). Usa Google Fonts (Anton, Space Grotesk, Space Mono) y hace todas las peticiones a la API vía `fetch`.
- **Backend:** PHP puro (sin framework), con un solo endpoint (`api/index.php`) que resuelve distintas acciones según el parámetro `action` que llega por POST en JSON.
- **Base de datos:** MySQL / MariaDB (pensada para el servidor que trae XAMPP).
- **Sesión:** manejo de sesión nativo de PHP (`$_SESSION`) con cookie `httponly`.

No hay dependencias externas que instalar (ni Composer ni npm): con PHP + MySQL alcanza.

---

## Estructura del proyecto

```
Agora-IA/
├── index.html          # Todo el frontend (HTML + CSS + JS)
└── api/
    ├── index.php        # Endpoint único de la API (acciones: bootstrap, login, register, etc.)
    ├── db.php           # Conexión PDO a MySQL + helpers (slugify, timeAgo)
    └── config.php       # Datos de conexión a la base (host, usuario, contraseña, nombre de la BD)
```

Y en la raíz de este chat/entrega también está:

```
tablon.sql              # Dump completo de la base de datos "tablon" (estructura + datos de ejemplo)
```

Ese `tablon.sql` es el que hay que importar en phpMyAdmin (ver instalación abajo). Te recomiendo guardarlo dentro de la carpeta `api/` de tu proyecto local, por ejemplo como `api/schema.sql`, tal como lo referencia el comentario de `config.php`.

---

## Instalación local con XAMPP

### 1. Requisitos

- [XAMPP](https://www.apachefriends.org/) instalado (incluye Apache, PHP y MySQL/MariaDB).
- PHP 8.x (el proyecto usa `declare(strict_types=1)` y funciones modernas, así que evitá versiones muy viejas de PHP).

### 2. Copiar el proyecto a `htdocs`

Copiá la carpeta `Agora-IA` (con `index.html` y `api/`) dentro de la carpeta `htdocs` de tu instalación de XAMPP, por ejemplo:

- Windows: `C:\xampp\htdocs\Agora-IA`
- Linux: `/opt/lampp/htdocs/Agora-IA`
- macOS: `/Applications/XAMPP/htdocs/Agora-IA`

### 3. Levantar Apache y MySQL

Abrí el **Panel de Control de XAMPP** y arrancá los módulos **Apache** y **MySQL**.

### 4. Crear la base de datos

1. Entrá a phpMyAdmin: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Creá una base de datos nueva llamada **`tablon`** (con collation `utf8mb4_general_ci`).
3. Seleccioná esa base, andá a la pestaña **Importar** y subí el archivo `tablon.sql`.
4. Confirmá que se crearon las tablas: `categories`, `users`, `groups`, `group_members`, `meetups`, `meetup_going`, `posts`, `friends`, `user_categories`.

### 5. Revisar la configuración de conexión

El archivo `api/config.php` ya viene configurado para XAMPP por defecto (usuario `root`, sin contraseña):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tablon');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Si tu instalación de MySQL tiene otro usuario/contraseña, cambiálo ahí.

### 6. Abrir la app

En el navegador, andá a:

```
http://localhost/Agora-IA/index.html
```

Para chequear rápido que el backend y la base están bien conectados, podés visitar:

```
http://localhost/Agora-IA/api/index.php?action=ping
```

Debería devolver algo como `{"ok":true,"php":"8.x.x","db":"conectado"}`.

---

## Usuario de demo / invitade

La base ya viene con datos de ejemplo (usuarios, grupos, encuentros y posts) para poder mostrar la app sin tener que cargar nada a mano. Desde la pantalla de login hay un botón **"Entrar como invitade (demo)"** que te loguea automáticamente con el usuario `@biaferreyra` (sin necesidad de contraseña).

Si querés loguearte con las cuentas reales del dump, todas comparten la misma contraseña de prueba porque tienen el mismo hash (excepto el usuario 9, que tiene un hash distinto). Como el hash no se puede "revertir", lo más práctico para probar login manual es crear una cuenta nueva desde "Crear cuenta" o usar el acceso de invitade.

---

## Cómo funciona la API

Todo el backend es **un solo endpoint** (`api/index.php`) al que el frontend le pega por `POST` mandando un JSON con una clave `action`. Según esa acción, el switch de PHP ejecuta una operación distinta contra la base y devuelve JSON.

Acciones disponibles:

| Acción | Qué hace |
|---|---|
| `ping` | Chequeo rápido de que el PHP y la base están funcionando. |
| `bootstrap` | Trae todo el estado inicial de la app: categorías, usuarios, grupos (con miembros, encuentros y posts) y el usuario logueado actual. |
| `register` | Crea una cuenta nueva (nombre, handle, contraseña, categorías de interés). |
| `login` | Inicia sesión con handle y contraseña. |
| `guest_login` | Inicia sesión automáticamente con el usuario de demo. |
| `logout` | Cierra la sesión. |
| `create_group` | Crea un grupo nuevo (y de paso une al creador como miembro). Permite categoría personalizada. |
| `join_group` / `leave_group` | Unirse o salir de un grupo. |
| `create_meetup` | Crea un encuentro dentro de un grupo (solo miembros). |
| `toggle_going` | Marca/desmarca "voy" a un encuentro (solo miembros del grupo). |
| `create_post` | Publica en el muro de un grupo (solo miembros). |
| `add_friend` / `remove_friend` | Agregar o quitar un amigo. |

La autenticación se maneja con sesiones de PHP: mientras haya una cookie de sesión válida, `currentUserId()` sabe quién sos, y las acciones que lo requieren llaman a `requireLogin()`.

---

## Modelo de datos

La base `tablon` tiene estas tablas principales:

- **`users`** — cuentas de usuario (nombre, handle único, hash de contraseña, color de avatar).
- **`categories`** — catálogo de temas/hobbies (ciclismo, rol, fotografía, running, ajedrez, etc.), con posibilidad de agregar categorías nuevas desde "Crear grupo".
- **`user_categories`** — intereses de cada usuario (relación N a N entre usuarios y categorías).
- **`groups`** — grupos creados por los usuarios, cada uno con una categoría y una zona (barrio).
- **`group_members`** — quién pertenece a qué grupo.
- **`meetups`** — encuentros presenciales propuestos dentro de un grupo.
- **`meetup_going`** — quién confirmó asistencia a cada encuentro.
- **`posts`** — publicaciones del muro de cada grupo.
- **`friends`** — relación de amistad entre usuarios (unidireccional: `user_id` agregó a `friend_id`).

Todas las relaciones tienen sus `FOREIGN KEY` correspondientes con borrado en cascada donde corresponde (por ejemplo, si se borra un grupo, se borran sus miembros, encuentros y posts).

---

## Problemas comunes

- **"Error interno del servidor" al abrir la app:** casi siempre es que la base `tablon` no existe o no se importó `tablon.sql`. Revisá `api/config.php` y phpMyAdmin.
- **No conecta a la base:** confirmá que el servicio MySQL de XAMPP esté corriendo y que usuario/contraseña en `config.php` coincidan con tu instalación.
- **Ver el detalle del error real:** con `APP_DEBUG = true` en `config.php`, la API devuelve el mensaje de error de PHP en la respuesta JSON en vez de un mensaje genérico. Antes de subir esto a un hosting real, pasalo a `false`.
- **Botón de invitade dice que no hay datos de demo:** significa que no se importó `tablon.sql` (falta el usuario `@biaferreyra`).
