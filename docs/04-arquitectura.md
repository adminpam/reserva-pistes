# 04 · Arquitectura

## Stack
- **Laravel 12** (PHP fijado a 8.2 con `composer config platform.php 8.2.0`).
- **Blade + CSS propio** (sin frameworks CSS) + JavaScript vanilla.
- **Leaflet 1.9** con teselas de OpenStreetMap para el mapa (CDN).
- **SQLite** en desarrollo, **PostgreSQL** en producción (Laravel Cloud).
- Servicios externos sin clave: **Overpass API** (clubes), **Nominatim** (direcciones), **Open-Meteo** (tiempo).

## Estructura (lo añadido sobre Laravel base)
```
app/Console/Commands/ImportClubs.php      ← comando clubs:import
app/Http/Controllers/                     ← Auth, Map, Club, Booking, Match, Profile
app/Http/Controllers/Manager/, Admin/     ← paneles por rol
app/Http/Middleware/EnsureRole.php        ← middleware 'role:manager|admin'
app/Models/                               ← User, Sport, Club, Court, Booking, UserSportLevel
database/migrations, database/seeders
resources/views/                          ← Blade por secciones
resources/css/app.css, resources/js/app.js (Vite)
public/img/                               ← SVG propios
```

## Decisiones principales
1. **Autenticación propia con sesiones** (sin starter kit): todo el flujo está en `AuthController` y lo podemos explicar línea a línea.
2. **Roles en una columna** + middleware `EnsureRole`: simple y suficiente para tres roles.
3. **Partido abierto = reserva con `is_open`**: reutiliza toda la lógica de reservas; los participantes van en `booking_players`.
4. **Precios en céntimos (integer)** para evitar errores de coma flotante.
5. **Importación idempotente**: `updateOrCreate` por `osm_id`, se puede relanzar sin duplicar clubes.
6. El filtro «solo mi nivel» se aplica en PHP porque el nivel depende del deporte de cada partido.

## Herramientas de IA
Todo el desarrollo asistido está registrado en `docs/registre-ia.md` (herramienta, prompt, resultado y commit).
