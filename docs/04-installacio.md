# 04 · Instalación local

Guía para una copia de desarrollo en Windows PowerShell 5.1. No son instrucciones
para ejecutar sobre la base de producción. El proyecto existente no se vuelve a
crear con el instalador de Laravel.

## Requisitos

- PHP 8.2 o superior y las extensiones requeridas por Composer; para SQLite,
  `pdo_sqlite` y `sqlite3`. Denys utiliza Herd y Joy el PHP de XAMPP.
- Composer, Git y Node.js/npm compatibles con el lock del repositorio.
- El mismo código publicado por el equipo y sus archivos `composer.lock` y
  `package-lock.json`. Composer fija `platform.php` a `8.2.0`.

En el equipo donde se instala:

```powershell
php --version
php -m
composer --version
node --version
git --version
```

Si Composer informa de una extensión ausente, habilitarla en el `php.ini` usado
por ese PHP de consola; no ocultar el problema con opciones para ignorar requisitos.

## Obtener el código

Para una copia nueva, desde la carpeta de proyectos del equipo:

```powershell
git clone https://github.com/adminpam/reserva-pistes.git
Set-Location reserva-pistes
composer install
composer check-platform-reqs
npm ci
```

Si Joy ya tiene el repositorio, seguir primero la sincronización de
[`10-traspaso-joy.md`](10-traspaso-joy.md); no clonar encima de su trabajo.
La versión nueva de frontend debe haberse publicado antes de poder descargarla.

## Configuración de una copia nueva

Crear `.env` solo si no existe. Conservar cualquier configuración local anterior.

```powershell
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
    php artisan key:generate
}
```

En el `.env` local, comprobar `APP_URL=http://127.0.0.1:8000` y
`DB_CONNECTION=sqlite`. Para usar la ruta predeterminada, dejar comentadas las
asignaciones `DB_DATABASE` y `DB_URL`. No copiar conexiones de Render al entorno
de pruebas. Nunca compartir el contenido de `.env` en el chat o en Git.

Crear el archivo SQLite solo si falta y migrar:

```powershell
if (-not (Test-Path -LiteralPath 'database/database.sqlite')) {
    New-Item -ItemType File -Path 'database/database.sqlite' | Out-Null
}
php artisan config:clear
php artisan migrate
```

Para llenar **una base local nueva de demostración**, ejecutar:

```powershell
php artisan db:seed
```

El seeder carga deportes, cuatro usuarios ficticios y `Club Demo Sabadell` con
pistas. Los usuarios son `admin@reservapistes.test`, `gestor@reservapistes.test`,
`marta@reservapistes.test` y `pau@reservapistes.test`. Su contraseña local procede
de `SEED_PASSWORD` o del valor de demostración definido en `UserSeeder`.
No usar ese valor de demostración en producción. El seeder de usuarios actual
actualiza los registros existentes: no ejecutarlo automáticamente en cada arranque.

La importación de clubes reales se prepara con Joy tras comprobar la base:

```powershell
php artisan clubs:import
```

Importar un club no verifica sus pistas, precios o deportes. `CourtSeeder` crea
pistas ilustrativas para clubes sin pistas; esos datos deben estar identificados
como demostración y revisarse antes de presentarlos como oferta de un club real.

## Arrancar la aplicación

En una terminal dentro del proyecto:

```powershell
php artisan serve
```

En una segunda terminal, en la misma carpeta:

```powershell
npm run dev
```

Abrir http://127.0.0.1:8000. Para compilar los assets sin el servidor Vite:

```powershell
npm run build
php artisan view:clear
```

Si Vite estaba ejecutándose, detenerlo al comprobar el build de producción.
Actualizar el navegador con Ctrl+F5 tras cambiar CSS/JS de `public/ui`.

## Verificación

En la terminal del proyecto:

```powershell
php artisan --version
composer config platform.php
php artisan test --filter=MapPageTest
php artisan test --filter=FrontendScreensTest
node --test tests/Frontend/map-data.test.mjs tests/Frontend/screens.test.mjs
```

Los dos tests PHP indicados preparan SQLite en memoria. Antes de ejecutar toda
la suite, aislar también la base de los tests anteriores: algunos utilizan
`RefreshDatabase` y el `phpunit.xml` heredado no fija una base separada.

Resultados realmente observados: [`06-proves.md`](06-proves.md).
Faltan la ejecución completa de esta guía en el Lenovo y la revisión del backend.

## Fuentes oficiales

- https://laravel.com/docs/12.x/installation
- https://laravel.com/docs/12.x/seeding
- https://getcomposer.org/doc/03-cli.md#install-i
- https://docs.npmjs.com/cli/v11/commands/npm-ci
- https://docs.github.com/en/get-started/using-git/getting-changes-from-a-remote-repository
