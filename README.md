# FIXA — Panel web y portal (Laravel 12 + Filament 5)

Sistema de gestión de incidencias urbanas. Equipo DevCore, Univalle Santa Cruz, 2-2026.

## Cómo encajan los tres proyectos

| Repositorio | Qué es | Puerto en tu PC |
|---|---|---|
| **FIXA_back** (este) | Panel de funcionarios y portal. Dueño de la base de datos (migraciones) | 8000 |
| [Fixa_API](https://github.com/helendrg14-em/Fixa_API) | API en ASP.NET con las reglas de negocio | 5112 |
| [FIXA_app](https://github.com/helendrg14-em/FIXA_app) | App Android del vecino | — |

Para desarrollar, **cada uno usa su propia base en Docker**, no la de Supabase: es más rápida
y no arriesga los datos reales. Orden para levantar todo: **1)** base y Laravel (este README),
**2)** la API, **3)** la app.

## 1. Qué instalar (una sola vez, en Windows)

| Programa | Para qué | Cómo comprobarlo |
|---|---|---|
| [Git](https://git-scm.com) | Clonar y subir cambios | `git --version` |
| PHP **8.4.27 o superior** | Correr Laravel | `php -v` |
| [Composer 2](https://getcomposer.org) | Dependencias de PHP | `composer --version` |
| [Docker Desktop](https://www.docker.com/products/docker-desktop) | La base de datos local (PostgreSQL + PostGIS) | `docker --version` |

PHP en Windows: `winget install PHP.PHP.NTS.8.4`. Después, en el `php.ini` (lo muestra
`php --ini`), quita el `;` de estas líneas: `extension=curl`, `fileinfo`, `gd`, `intl`,
`mbstring`, `openssl`, `pdo_pgsql`, `pgsql`, `pdo_sqlite`, `sqlite3` y `zip`.
Comprueba con `php -m` que aparecen.

No hace falta Node.js: el panel no usa Vite.

## 2. Poner en marcha

```bash
git clone https://github.com/helendrg14-em/FIXA_back.git
cd FIXA_back
git checkout develop
composer install
cp .env.example .env
php artisan key:generate
```

En tu `.env`, cambia la base a la local de Docker y pon **tus propios** datos de administrador:

```
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=fixa
DB_USERNAME=fixa
DB_PASSWORD=una_contraseña_solo_para_tu_pc
DB_SSLMODE=disable

ADMIN_NOMBRES=Tu nombre
ADMIN_APELLIDOS=Tu apellido
ADMIN_EMAIL=tu_correo@gmail.com
ADMIN_PASSWORD=una_contraseña_de_12_o_más
```

Con Docker Desktop abierto:

```bash
docker compose up -d --wait
php artisan migrate
php artisan db:seed     # tu administrador, unidades, categorías, cuadrillas y distritos
php artisan serve
```

- Portal: http://localhost:8000
- Panel: http://localhost:8000/admin, con el correo y la contraseña de `ADMIN_*`.

Para crear supervisores o técnicos, usa el panel. Con `MAIL_MAILER=log` no se envía ningún
correo: el enlace para crear la contraseña queda en `storage/logs/laravel.log`.

## 3. Conectar el panel con la API de tu PC

Las acciones sobre los casos (pasar a revisión, prioridad, programar, nuevo reporte) van por la
API. Para usarlas en local, levanta la API (ver su README) y agrega a tu `.env`:

```
FIXA_API_URL=http://localhost:5112
FIXA_API_JWT_CLAVE=la_misma_clave_que_Jwt:Clave_de_tu_API
```

Las dos claves **deben ser idénticas** y tener al menos 32 caracteres: el panel firma con ella
el token de cada funcionario y la API lo verifica. Sin la API, el resto del panel funciona igual.

## Problemas comunes

| Síntoma | Causa | Solución |
|---|---|---|
| `could not find driver` | Falta `pdo_pgsql` en `php.ini` | Activa la extensión y revisa `php -m` |
| `Connection refused` al migrar | Docker no está encendido | Abre Docker Desktop y repite `docker compose up -d --wait` |
| El panel va muy lento | Tu `.env` apunta a Supabase | Usa la base de Docker |
| "No se pudo completar la acción" en un caso | La API no está levantada o la clave no coincide | Revisa el paso 3; el detalle está en `storage/logs/laravel.log` |
| `docker compose` pide `DB_DATABASE` | Falta en el `.env` | Completa los datos `DB_*` del paso 2 |

## Pruebas

Usan SQLite en memoria; no necesitan Docker ni tocan ninguna base:

```bash
php artisan test
```

En GitHub corren solas, junto con `composer audit`, en cada push y pull request a `main` y `develop` (`.github/workflows/ci.yml`, RNF05).

**Si el panel va lento en tu PC**, casi siempre es la distancia a Supabase: cada consulta cruza el continente. Usa la base local de Docker para desarrollar. Si necesitas Supabase, pon `DB_PERSISTENT=true` en tu `.env` para no abrir una conexión nueva en cada petición.

## Cuentas de funcionarios

El administrador crea la cuenta y el sistema envía un enlace de un solo uso, válido por 48 horas, para que la persona cree su contraseña. Con `MAIL_MAILER=log` el enlace queda en `storage/logs/laravel.log`, que nunca se sube al repositorio.

## Reglas de seguridad del equipo

- **Nunca** subir `.env` ni contraseñas al repositorio. Solo `.env.example`, sin datos reales.
- `main` está protegida: todo cambio entra por pull request revisado por otro compañero.
- `composer audit` corre en cada pull request (RNF06); el merge espera a que pase.
- Sin registro público: las cuentas las crea solo el administrador.
- Usuarios y categorías se **desactivan**, nunca se borran (las policies bloquean el borrado).
- `eventos_historial` es inmutable: un trigger rechaza UPDATE, DELETE y TRUNCATE (RNF08).
- Toda tabla nueva debe activar RLS en su migración:
  `DB::statement('ALTER TABLE public.mi_tabla ENABLE ROW LEVEL SECURITY');`

## Base de datos y API en C#

La app Flutter y el panel usan la API en ASP.NET ([Fixa_API](https://github.com/helendrg14-em/Fixa_API)),
que trabaja sobre esta misma base. **Laravel crea las tablas del sistema** (usuarios, casos,
reportes, notificaciones…). La API de C# solo crea sus tablas propias (`plantillas_notificacion`)
con migraciones de Entity Framework Core, que aplica sola al iniciar.

1. Toda tabla o columna nueva del sistema empieza aquí, como migración de Laravel con
   `ENABLE ROW LEVEL SECURITY`.
2. Después se actualiza a mano el modelo de Entity Framework en la API para que coincida.
3. Se regenera `Fixa.Api.Tests/Datos/schema.sql` en el repositorio de la API, para que sus
   pruebas corran contra el esquema nuevo (el comando está en su README).
4. Al desplegar, primero `php artisan migrate` contra Supabase y después la API de C#.

El perfil de cada funcionario está en la columna **`rol`** de `usuarios` (`administrador`,
`supervisor`, `tecnico`, `operador`).

**Cómo se reparten el trabajo el panel y la API:** el panel *lee* directo de la base (bandeja,
detalle, revisiones), pero todo lo que *cambia* un caso o crea un reporte va por la API
(`app/Api/ClienteFixaApi.php`). Así la máquina de estados, el historial, los plazos y los avisos
al vecino viven en un solo lugar. El panel firma un token de 15 minutos para cada funcionario
(`app/Api/TokenPanel.php`) con la misma clave que la API (`FIXA_API_JWT_CLAVE` = `Jwt__Clave`),
y la API vuelve a comprobar en cada pedido que la cuenta siga activa.

| Pantalla del panel | Quién la ve | Qué hace por la API |
|---|---|---|
| Casos | Supervisor (su unidad) y administrador (todas) | Revisión, prioridad, programar, aprobar y cerrar, rechazar el trabajo, corregir categoría, duplicado, derivar, rechazar |
| Nuevo reporte | Administrador | Registro por teléfono o en ventanilla, con foto o con el motivo de por qué no hay |
| Revisiones | Supervisor (su unidad) y administrador (todas) | Reabrir el caso o explicar al vecino por qué no |

## Qué ya está hecho

| Medida | Dónde |
|---|---|
| Conexión con SSL y PostGIS en el esquema `extensions` | `config/database.php`, migración `enable_postgis` |
| Las 17 tablas del DER, con claves foráneas que impiden borrar | `database/migrations/2026_10_29_*` |
| RLS en todas las tablas (bloquea la API REST pública de Supabase) | migración `enable_row_level_security` |
| Sesión de 15 min, cifrada, cookie `HttpOnly` y `SameSite=lax` | `config/session.php`, `.env` |
| Contraseñas con bcrypt (12 rondas) | cast `hashed` en `User` |
| Solo entran al panel cuentas activas de administrador, supervisor y técnico | `User::canAccessPanel()` |
| Login con correo, sin "Recordarme", 5 intentos por minuto | `app/Filament/Pages/Auth/Login.php` |
| Permisos en el servidor para usuarios y categorías | `app/Policies` |
| Cabeceras de seguridad (nosniff, anti-iframe, HSTS con HTTPS) | `app/Http/Middleware/SecurityHeaders.php` |
| Primer administrador creado desde el `.env` | `database/seeders/AdminSeeder.php` |

El perfil **operador** (R07) existe en la base de datos pero todavía no puede entrar al panel.
Para habilitarlo, edita `TipoUsuario::puedeEntrarAlPanel()`.

## Despliegue en Render

La imagen se construye con el `Dockerfile` y corre panel y portal en un solo contenedor.

1. Crea un **Web Service** con runtime **Docker**, región **Ohio** (la misma de Supabase).
2. Carga estas variables en Render, nunca en el repositorio:
   - `APP_KEY`: una clave propia, generada con `php artisan key:generate --show`
   - `APP_URL`: `https://tu-app.onrender.com`
   - `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT=5432`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE=require`
   - `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAIL_FROM_ADDRESS`
   - `FIXA_API_URL` (la dirección de la API en Render) y `FIXA_API_JWT_CLAVE` (el mismo valor que `Jwt__Clave` de la API)
3. Usa `/up` como Health Check Path.
4. Las migraciones se ejecutan desde una PC apuntando a Supabase. El contenedor nunca las corre al arrancar.

Correo: se usa la API de Mailgun porque el plan gratis de Render bloquea SMTP. Con el dominio sandbox solo llega a 5 destinatarios autorizados.
