# Changelog

## [v1.0] — 2026-12-04
Primera versión completa, desplegada en producción.
- Partidos abiertos: plazas por deporte, rango de nivel, unirse/salir, «Mis partidos».
- Perfil del jugador con nivel 1–5 por deporte.
- Panel de gestor: CRUD de pistas y reservas del club.
- Panel de admin: clubes (asignación de gestor), deportes y usuarios.
- Importación de clubes reales desde OpenStreetMap (`clubs:import`) y tiempo con Open-Meteo.
- Pruebas automáticas (solapamiento y roles) y documentación 01–06 completa.
- Despliegue en Laravel Cloud con PostgreSQL.

## [v0.1] — 2026-11-14
Esqueleto funcional del proyecto.
- Laravel 12 con `platform.php = 8.2.0` (compatibilidad Herd/XAMPP).
- Modelo de datos completo (7 tablas) con seeders de deportes y usuarios demo.
- Autenticación con sesiones y middleware de roles (player/manager/admin).
- Base visual: layout, paleta propia, CSS sin frameworks, SVG provisionales.
- Mapa Leaflet con clubes y filtro por deporte; ficha de club con reserva de slots de una hora sin solapamientos (pago simulado).
