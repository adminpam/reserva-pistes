# Registre d'ús d'IA

Registro de cada uso relevante de IA en el proyecto. Herramienta usada en todo el proyecto: **Claude (Anthropic), chat web**. Repositorio: https://github.com/adminpam/reserva-pistes

---

**1 · 10/11/2026 — Denys**
- Objetivo: definir el plan de trabajo y fijar PHP 8.2 para compatibilidad entre los dos equipos.
- Prompt (resumen): «Tenemos Laravel 12 con herd-lite y XAMPP 8.2; cómo fijamos la plataforma y en qué paquetes dividimos el desarrollo».
- Resultado: `composer config platform.php 8.2.0` en composer.json y plan de ramas por funcionalidad.
- Commit: [ab0916b](https://github.com/adminpam/reserva-pistes/commit/ab0916b)

**2 · 10/11/2026 — Denys**
- Objetivo: modelo de datos completo (7 migraciones + 6 modelos Eloquent + seeders).
- Prompt: «Genera migraciones y modelos para users con roles, sports, clubs, courts, bookings con partidos abiertos, booking_players y user_sport_levels, con comentarios explicando cada decisión».
- Resultado: migraciones con índices y claves foráneas; relaciones y helpers en los modelos (`Booking::overlaps`, `freeSlots`, `levelFits`); seeders de deportes, usuarios demo y club de ejemplo.
- Commit: [8bf9bbd](https://github.com/adminpam/reserva-pistes/commit/8bf9bbd) · PR #1

**3 · 10/11/2026 — Denys**
- Objetivo: autenticación propia y middleware de roles.
- Prompt: «Login/registro con sesiones sin starter kit, y un middleware role:manager/admin registrado en bootstrap/app.php de Laravel 12».
- Resultado: `AuthController` con regeneración de sesión, `EnsureRole` y las vistas de login y registro.
- Commit: [a5b8b92](https://github.com/adminpam/reserva-pistes/commit/a5b8b92) · PR #2

**4 · 10/11/2026 — Denys**
- Objetivo: reservas de una hora sin solapamientos.
- Prompt: «store() de reservas con validación de horario de pista, comprobación de solapamiento dentro de transacción con lockForUpdate y cancelación que libera el slot».
- Resultado: `BookingController` con la regla `start < other_end AND end > other_start`; vista «Mis reservas» con cancelación.
- Commit: [1a29d6d](https://github.com/adminpam/reserva-pistes/commit/1a29d6d) · PR #3

**5 · 10/11/2026 — Denys**
- Objetivo: partidos abiertos.
- Prompt: «Partidos abiertos sobre bookings: plazas según players_per_match del deporte, rango de nivel opcional, unirse/salir, y si sale el organizador se cancela el partido».
- Resultado: `MatchController` con búsqueda, filtro «solo mi nivel» calculado en PHP y las vistas de búsqueda y «Mis partidos».
- Commit: [d27fe8b](https://github.com/adminpam/reserva-pistes/commit/d27fe8b) · PR #4

**6 · 10/11/2026 — Denys**
- Objetivo: panel de administración.
- Prompt: «Admin de clubes (asignar gestor), deportes (plazas por partido) y usuarios (cambiar rol), con protecciones: no auto-eliminarse ni quitarse el rol de admin».
- Resultado: tres controladores en `Admin/` y sus vistas con subnavegación.
- Commit: [212a057](https://github.com/adminpam/reserva-pistes/commit/212a057) · PR #5

**7 · 10/11/2026 — Denys**
- Objetivo: prueba automática de la regla crítica de reservas.
- Prompt: «Feature test del solapamiento de reservas, incluida la re-reserva después de cancelar».
- Resultado: `BookingOverlapTest` con dos casos, en verde en local.
- Commit: [5bcbc4d](https://github.com/adminpam/reserva-pistes/commit/5bcbc4d) · PR #6

**8 · 10/11/2026 — Denys**
- Objetivo: documentación técnica y de entrega.
- Prompt: «Redacta arquitectura, plan de despliegue y plan de pruebas a partir del código real del proyecto, más README y CHANGELOG».
- Resultado: `04-arquitectura.md`, `05-desplegament.md`, `06-proves.md`, `README.md`, `CHANGELOG.md` y este registro.
- Commit: [3c301c6](https://github.com/adminpam/reserva-pistes/commit/3c301c6) · PR #6

**9 · 14/11/2026 — Joy**
- Objetivo: base visual del proyecto.
- Prompt: «Plantilla Blade con navegación por rol y hoja CSS con la paleta F7F7F5/1F2933/F59E0B/0E9F8E/DC2626, tipografía Nunito, animaciones respetando prefers-reduced-motion».
- Resultado: `layouts/app.blade.php`, `partials/flash.blade.php`, `app.css` (~310 líneas), `app.js` (mapa, tiempo, sonidos) y 12 SVG provisionales.
- Commit: [ff45a1f](https://github.com/adminpam/reserva-pistes/commit/ff45a1f) · PR #7

**10 · 16/11/2026 — Joy**
- Objetivo: mapa de clubes con Leaflet.
- Prompt: «Mapa Leaflet con teselas OSM, marcadores desde /api/clubs con filtro por deporte y popups con enlace a la ficha».
- Resultado: `MapController` (vista + endpoint JSON), vista del mapa y módulo de mapa en `app.js` con marcador propio.
- Commit: [bbafe5f](https://github.com/adminpam/reserva-pistes/commit/bbafe5f) · PR #8

**11 · 16/11/2026 — Joy**
- Objetivo: ficha de club con disponibilidad y tiempo.
- Prompt: «Ficha con selector de día, horas ocupadas deshabilitadas en el select, aviso de pago simulado y widget Open-Meteo solo si hay pista exterior».
- Resultado: `ClubController` con el cálculo de horas ocupadas y la vista de la ficha.
- Commit: [3e0e05e](https://github.com/adminpam/reserva-pistes/commit/3e0e05e) · PR #8

**12 · 16/11/2026 — Joy**
- Objetivo: perfil del jugador con niveles por deporte.
- Prompt: «Formulario de perfil con select 1–5 por deporte; nivel vacío = borrar el registro (upsert/delete)».
- Resultado: `ProfileController` y la vista del perfil.
- Commit: [8c23a6f](https://github.com/adminpam/reserva-pistes/commit/8c23a6f) · PR #9

**13 · 16/11/2026 — Joy**
- Objetivo: panel del gestor de club.
- Prompt: «CRUD de pistas limitado a los clubes del gestor (403 si no es suyo), precio en euros convertido a céntimos, y listado de reservas de sus clubes».
- Resultado: `ManagerCourtController`, `ManagerBookingController` y sus vistas.
- Commit: [5a77443](https://github.com/adminpam/reserva-pistes/commit/5a77443) · PR #9

**14 · 16/11/2026 — Joy**
- Objetivo: importación de clubes reales desde OpenStreetMap, test de roles y documentación de requisitos y diseño.
- Prompt: «Comando artisan clubs:import con Overpass API (bbox Sabadell-Terrassa-Barcelona), upsert por osm_id y direcciones con Nominatim respetando 1 petición/segundo» + «Feature test de accesos por rol» + «Requisitos y diseño a partir del código real».
- Resultado: `ImportClubs.php` idempotente (probado: 10 clubes reales importados), `RoleAccessTest`, `02-requisits.md`, `03-disseny.md` y `assets-pendientes.md`.
- Commits: [2b5bf92](https://github.com/adminpam/reserva-pistes/commit/2b5bf92), [45b6abc](https://github.com/adminpam/reserva-pistes/commit/45b6abc), [7fe0b59](https://github.com/adminpam/reserva-pistes/commit/7fe0b59) · PR #10

**15 · __/12/2026 — Denys**
- Objetivo: despliegue en producción.
- Prompt: «Pasos para desplegar en Laravel Cloud con PostgreSQL: build de Vite, migrate --force --seed, clubs:import y variables de entorno seguras».
- Resultado: _pendiente_ — app publicada en la URL de `docs/05-desplegament.md`; etiqueta v1.0.
- Commit: _pendiente_
