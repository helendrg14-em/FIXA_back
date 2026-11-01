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
php artisan db:seed
php artisan serve
```

Panel: http://localhost:8000/admin

## Reglas de seguridad del equipo

- **Nunca** subir `.env` ni contraseñas al repositorio. Solo `.env.example`, sin datos reales.
- `main` está protegida: todo cambio entra por pull request revisado por otro compañero.
- Ejecutar `composer audit` antes de cada merge (RNF06).
- Sin registro público: las cuentas las crea solo el administrador.
- Usuarios y categorías se **desactivan**, nunca se borran (las policies bloquean el borrado).
- `eventos_historial` es inmutable: un trigger rechaza UPDATE, DELETE y TRUNCATE (RNF08).
- Toda tabla nueva debe activar RLS en su migración:
  `DB::statement('ALTER TABLE public.mi_tabla ENABLE ROW LEVEL SECURITY');`

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
