# ReservaPistes 🏸

Aplicación web para **reservar pistas deportivas** y **encontrar compañeros de juego** en la zona Sabadell–Terrassa–Barcelona. Proyecto del módulo **M0617 Programación asistida con IA** (AEA6 «Vibe Coding»), IES Sabadell.

**Autores:** Denys Peresunko (@adminpam) y Joy Liliana Castro Valencia (@joyvalencia)

> ⚠️ Las reservas y pagos son **simulados**: es un proyecto educativo, no se cobra nada.

## Qué hace
- Mapa de **clubes reales** (importados de OpenStreetMap) con filtro por deporte.
- Reserva de slots de una hora **sin solapamientos** (comprobación transaccional).
- **Partidos abiertos**: plazas según el deporte (tenis de mesa 2, fútbol sala 10…), rango de nivel 1–5 opcional, apuntarse/salir.
- Perfil con nivel por deporte; «Mis reservas» y «Mis partidos» con cancelación.
- Panel de **gestor de club** (CRUD de pistas, reservas del club) y panel de **admin** (clubes, deportes, usuarios).
- Tiempo actual en pistas exteriores (Open-Meteo). Todo **sin claves de API**.

## Stack
Laravel 12 · Blade + CSS propio · JS vanilla + Leaflet/OpenStreetMap · SQLite (local) / PostgreSQL (producción) · Vite

## Herramientas de IA utilizadas
El proyecto se ha desarrollado con asistencia de **Claude (Anthropic)** — generación de código guiada, revisión y documentación. Cada uso está registrado en [`docs/registre-ia.md`](docs/registre-ia.md) con su prompt, resultado y commit. Todo el código mergeado a `main` lo entendemos y podemos explicarlo.

## Puesta en marcha local
```bash
git clone https://github.com/adminpam/reserva-pistes.git
cd reserva-pistes
composer install
cp .env.example .env
php artisan key:generate
# SQLite: crear el archivo de base de datos si no existe
# (Windows PowerShell): New-Item database/database.sqlite -ItemType File -Force
php artisan migrate --seed
npm install && npm run dev   # en otra terminal
php artisan serve            # → http://127.0.0.1:8000
php artisan clubs:import     # opcional: clubes reales desde OpenStreetMap
```

**Usuarios demo (ficticios, solo local):** `admin@reservapistes.test`, `gestor@reservapistes.test`, `marta@reservapistes.test` — contraseña: la de `SEED_PASSWORD` del `.env` (por defecto `password`).

## Producción
Desplegado en **Laravel Cloud** (PostgreSQL). URL: _pendiente — ver `docs/05-desplegament.md`_.

## Documentación
`docs/01-proposta.md` → propuesta · `02-requisits` · `03-disseny` · `04-arquitectura` · `05-desplegament` · `06-proves` · `registre-ia.md` · [`CHANGELOG.md`](CHANGELOG.md)

## Capturas
_Se añadirán al final del desarrollo (app desplegada)._
