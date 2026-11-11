# Registre d'ús d'IA

Registro de cada uso relevante de IA en el proyecto. Herramienta usada en todo el proyecto: **Claude (Anthropic), chat web**. Los enlaces a commit/PR se añaden justo después de cada push.

---

**1 · 10/11/2026 — Denys**
- Objetivo: definir el plan de trabajo y fijar PHP 8.2 para compatibilidad entre equipos.
- Prompt (resumen): «Tenemos Laravel 12 con Herd y XAMPP 8.2; cómo fijamos la plataforma y en qué paquetes dividimos el desarrollo».
- Resultado: `composer config platform.php 8.2.0`, plan de ramas por funcionalidad.
- Commit/PR: _pendiente_

**2 · 10/11/2026 — Denys**
- Objetivo: modelo de datos completo (7 migraciones + 6 modelos Eloquent).
- Prompt: «Genera migraciones y modelos para users con roles, sports, clubs, courts, bookings con partidos abiertos, booking_players y user_sport_levels, con comentarios explicando cada decisión».
- Resultado: migraciones con índices y claves foráneas; relaciones y helpers en los modelos; seeders de deportes y usuarios demo.
- Commit/PR: _pendiente_

**3 · 11/11/2026 — Joy**
- Objetivo: base visual del proyecto (layout, CSS propio, iconos SVG).
- Prompt: «Plantilla Blade con navegación por rol y hoja CSS con la paleta F7F7F5/1F2933/F59E0B/0E9F8E/DC2626, tipografía Nunito, animaciones con prefers-reduced-motion».
- Resultado: `layouts/app.blade.php`, `app.css` (~300 líneas), SVG provisionales del logo y 6 deportes.
- Commit/PR: _pendiente_

**4 · 11/11/2026 — Denys**
- Objetivo: autenticación propia y middleware de roles.
- Prompt: «Login/registro con sesiones sin starter kit, y un middleware role:manager/admin registrado en bootstrap/app.php de Laravel 12».
- Resultado: `AuthController` con regeneración de sesión, `EnsureRole`, rutas completas.
- Commit/PR: _pendiente_

**5 · 12/11/2026 — Joy**
- Objetivo: mapa de clubes con Leaflet y endpoint JSON.
- Prompt: «Mapa Leaflet con teselas OSM, marcadores desde /api/clubs con filtro por deporte y popups con enlace a la ficha».
- Resultado: `MapController`, `map/index.blade.php`, módulo de mapa en `app.js` con marcador propio.
- Commit/PR: _pendiente_

**6 · 12/11/2026 — Joy**
- Objetivo: importación de clubes reales desde OpenStreetMap.
- Prompt: «Comando artisan clubs:import con Overpass API (bbox Sabadell-Terrassa-Barcelona), upsert por osm_id y direcciones con Nominatim respetando 1 petición/segundo».
- Resultado: `ImportClubs.php` idempotente con User-Agent educativo.
- Commit/PR: _pendiente_

**7 · 13/11/2026 — Joy**
- Objetivo: ficha de club con disponibilidad y tiempo.
- Prompt: «Ficha con selector de día, horas ocupadas deshabilitadas en el select, aviso de pago simulado y widget Open-Meteo solo si hay pista exterior».
- Resultado: `ClubController` + vista + módulo de tiempo en JS.
- Commit/PR: _pendiente_

**8 · 14/11/2026 — Denys**
- Objetivo: reservas sin solapamientos.
- Prompt: «store() de reservas con validación de horario de pista, comprobación de solapamiento dentro de transacción con lockForUpdate y cancelación que libera el slot».
- Resultado: `BookingController` con la regla start<end' AND end>start'; vista «Mis reservas».
- Commit/PR: _pendiente_

**9 · 17/11/2026 — Denys**
- Objetivo: partidos abiertos completos.
- Prompt: «Partidos abiertos sobre bookings: plazas según players_per_match del deporte, rango de nivel opcional, unirse/salir, y si sale el organizador se cancela el partido».
- Resultado: `MatchController` con búsqueda y filtro «solo mi nivel» calculado en PHP.
- Commit/PR: _pendiente_

**10 · 18/11/2026 — Joy**
- Objetivo: perfil del jugador con niveles por deporte.
- Prompt: «Formulario de perfil con select 1–5 por deporte; nivel vacío = borrar el registro (upsert/delete)».
- Resultado: `ProfileController` + vista con filas de nivel.
- Commit/PR: _pendiente_

**11 · 19/11/2026 — Joy**
- Objetivo: panel del gestor de club.
- Prompt: «CRUD de pistas limitado a los clubes del gestor (403 si no es suyo), precio en euros convertido a céntimos, y listado de reservas de sus clubes».
- Resultado: `ManagerCourtController`, `ManagerBookingController` y sus vistas.
- Commit/PR: _pendiente_

**12 · 20/11/2026 — Denys**
- Objetivo: panel de administración.
- Prompt: «Admin de clubes (asignar gestor), deportes (plazas por partido) y usuarios (cambiar rol), con protecciones: no auto-eliminarse ni quitarse admin».
- Resultado: tres controladores Admin + vistas con subnavegación.
- Commit/PR: _pendiente_

**13 · 24/11/2026 — Denys y Joy**
- Objetivo: pruebas automáticas de las reglas críticas.
- Prompt: «Feature tests: solapamiento de reservas (incluida re-reserva tras cancelar) y accesos por rol con 403/redirect».
- Resultado: `BookingOverlapTest` (Denys) y `RoleAccessTest` (Joy), verdes en local.
- Commit/PR: _pendiente_

**14 · 26/11/2026 — Denys y Joy**
- Objetivo: documentación 02–06 y revisión del README.
- Prompt: «Redacta requisitos, diseño, arquitectura, plan de despliegue en Laravel Cloud y plan de pruebas a partir del código real del proyecto».
- Resultado: `docs/02-requisits.md` … `docs/06-proves.md`, `docs/assets-pendientes.md`.
- Commit/PR: _pendiente_

**15 · 01/12/2026 — Denys**
- Objetivo: despliegue en producción.
- Prompt: «Pasos para desplegar en Laravel Cloud con PostgreSQL: build de Vite, migrate --force --seed, clubs:import y variables de entorno seguras».
- Resultado: app publicada en la URL de `docs/05-desplegament.md`; CHANGELOG v1.0.
- Commit/PR: _pendiente_
