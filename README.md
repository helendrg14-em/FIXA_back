# FIXA — Panel web y API (Laravel 12 + Filament 5)

Sistema de gestión de incidencias urbanas. Equipo DevCore, Univalle Santa Cruz, 2-2026.

## Requisitos

- PHP 8.4 con `pdo_pgsql`, `pgsql`, `intl`, `mbstring`, `zip`, `gd`, `fileinfo`, `openssl`, `curl`
- Composer 2
- Node.js 20+
- Proyecto en Supabase (PostgreSQL + PostGIS)

## Puesta en marcha

```bash
composer install
cp .env.example .env
php artisan key:generate
```

En `.env`, completa `DB_HOST`, `DB_USERNAME` y `DB_PASSWORD` con los datos de Supabase
(Project Settings > Database > Session pooler) y los `ADMIN_*` del primer administrador.

```bash
php artisan migrate
php artisan db:seed     # administrador, 3 unidades y 3 categorías
php artisan serve
```

Portal: http://localhost:8000
Panel: http://localhost:8000/admin

Base local para desarrollar (opcional, requiere Docker Desktop). En `.env` usa
`DB_HOST=127.0.0.1`, `DB_PORT=5433`, `DB_SSLMODE=disable` y un `DB_DATABASE`, `DB_USERNAME`
y `DB_PASSWORD` solo para esta base. Guarda antes los datos de Supabase en `.env.supabase`,
que Git ignora.

```bash
docker compose up -d --wait
php artisan migrate
php artisan db:seed
```

Pruebas (usan SQLite en memoria, no tocan Supabase):

```bash
php artisan test
```

## Cuentas de funcionarios

El administrador crea la cuenta y el sistema envía un enlace de un solo uso, válido por 48 horas, para que la persona cree su contraseña. Con `MAIL_MAILER=log` el enlace queda en `storage/logs/laravel.log`, que nunca se sube al repositorio.

## Reglas de seguridad del equipo

- **Nunca** subir `.env` ni contraseñas al repositorio. Solo `.env.example`, sin datos reales.
- `main` está protegida: todo cambio entra por pull request revisado por otro compañero.
- Ejecutar `composer audit` antes de cada merge (RNF06).
- Sin registro público: las cuentas las crea solo el administrador.
- Usuarios y categorías se **desactivan**, nunca se borran (las policies bloquean el borrado).
- `eventos_historial` es inmutable: un trigger rechaza UPDATE, DELETE y TRUNCATE (RNF08).
- Toda tabla nueva debe activar RLS en su migración:
  `DB::statement('ALTER TABLE public.mi_tabla ENABLE ROW LEVEL SECURITY');`

## Base de datos y API en C#

La app Flutter y el panel usan la API en ASP.NET ([Fixa_API](https://github.com/helendrg14-em/Fixa_API)),
que trabaja sobre esta misma base. **Laravel es el único dueño del esquema**: la API de C# lee y
escribe datos, pero nunca crea ni cambia tablas.

1. Toda tabla o columna nueva empieza aquí, como migración de Laravel con
   `ENABLE ROW LEVEL SECURITY`, aunque solo la use la API de C#.
2. Después se actualiza a mano el modelo de Entity Framework en la API para que coincida.
   En C# no se usan `dotnet ef migrations` ni `EnsureCreated`.
3. Se regenera `Fixa.Api.Tests/Datos/schema.sql` en el repositorio de la API, para que sus
   pruebas corran contra el esquema nuevo (el comando está en su README).
4. Al desplegar, primero `php artisan migrate` contra Supabase y después la API de C#.

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
3. Usa `/up` como Health Check Path.
4. Las migraciones se ejecutan desde una PC apuntando a Supabase. El contenedor nunca las corre al arrancar.

Correo: se usa la API de Mailgun porque el plan gratis de Render bloquea SMTP. Con el dominio sandbox solo llega a 5 destinatarios autorizados.
