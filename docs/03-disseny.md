# 03 · Diseño

## Modelo de datos

| Tabla | Campos principales | Notas |
|---|---|---|
| users | name, email, password, **role** | role: player / manager / admin |
| sports | name, slug, **players_per_match**, icon | plazas de un partido abierto |
| clubs | name, address, city, **lat, lng**, osm_id, manager_id | osm_id evita duplicados al importar |
| courts | club_id, sport_id, name, **price_per_hour** (céntimos), open_hour, close_hour, is_outdoor | |
| bookings | court_id, user_id, **starts_at, ends_at**, status, **is_open**, min_level, max_level, price_total | un slot = una hora |
| booking_players | booking_id, user_id (únicos juntos) | participantes de partidos abiertos |
| user_sport_levels | user_id, sport_id, **level** (1–5) | nivel por deporte |

**Regla anti-solapamiento:** una reserva nueva se rechaza si en la misma pista existe otra confirmada con `starts_at < nueva.ends_at` y `ends_at > nueva.starts_at`. Se comprueba dentro de una transacción con `lockForUpdate` para evitar carreras.

## Identidad visual

- Paleta: fondo `#F7F7F5`, tinta `#1F2933`, ámbar `#F59E0B` (acciones), turquesa `#0E9F8E` (estados positivos), rojo `#DC2626` (errores).
- Tipografía: **Nunito** (Google Fonts), redondeada y deportiva.
- Logo e iconos de deportes: SVG propios (ver `docs/assets-pendientes.md`).
- Animaciones moderadas: entrada de tarjetas y realce al pasar el ratón, desactivadas con `prefers-reduced-motion`.
- Dos sonidos cortos (éxito/error) generados con WebAudio, **desactivados por defecto**, con botón en la cabecera.

## Pantallas
1. Mapa (home) con filtro por deporte y hero.
2. Ficha de club: tiempo (si hay pista exterior), selector de día, tarjeta por pista con horas ocupadas deshabilitadas.
3. Partidos abiertos (búsqueda) / Mis partidos / Mis reservas.
4. Perfil con niveles.
5. Panel de gestor (pistas + reservas) y panel de admin (clubes, deportes, usuarios).
