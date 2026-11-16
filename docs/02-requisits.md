# 02 · Requisitos

> Proyecto **ReservaPistes** — M0617, AEA6 Vibe Coding. Autores: Denys Peresunko y Joy Liliana Castro Valencia.

## Requisitos funcionales

**Visitante**
- RF1. Ver el mapa de clubes (Leaflet + OpenStreetMap) con filtro por deporte.
- RF2. Ver la ficha de un club: pistas, deportes, precios y horario.
- RF3. Registrarse y entrar con correo y contraseña.

**Jugador (usuario registrado)**
- RF4. Reservar un slot de una hora en una pista; el sistema rechaza slots que se solapan con otra reserva confirmada.
- RF5. El pago es **simulado**: se guarda el precio pero no se cobra nada (hay un aviso visible en la interfaz).
- RF6. Crear un **partido abierto** al reservar: el número de plazas depende del deporte y se puede fijar un rango de nivel (1–5).
- RF7. Buscar partidos abiertos (por deporte, club y «solo mi nivel») y apuntarse o salir.
- RF8. Ver «Mis reservas» y «Mis partidos» y cancelar los futuros (si el organizador sale, el partido se cancela para todos).
- RF9. Editar el perfil: nombre y nivel 1–5 por cada deporte.

**Gestor de club**
- RF10. CRUD de las pistas de **sus** clubes: deporte, precio/hora, horario, interior/exterior.
- RF11. Ver las reservas de sus clubes.

**Administrador**
- RF12. Gestionar clubes: buscar, asignar gestor, eliminar.
- RF13. Gestionar deportes: crear, cambiar jugadores por partido, eliminar (si no tiene pistas).
- RF14. Gestionar usuarios: cambiar rol, eliminar.
- RF15. Importar clubes reales de OpenStreetMap con `php artisan clubs:import`.

## Requisitos no funcionales
- RNF1. Backend Laravel 12, PHP compatible 8.2 (fijado con `platform.php` para que funcione en los dos equipos).
- RNF2. SQLite en local y PostgreSQL en producción, sin cambiar código (Eloquent).
- RNF3. Sin claves de API: Overpass, Nominatim y Open-Meteo son servicios abiertos.
- RNF4. Sin contraseñas ni claves en el repositorio (`.env` ignorado por git).
- RNF5. Accesibilidad básica: foco visible, `prefers-reduced-motion`, textos con contraste.
- RNF6. Datos personales reales mínimos: los usuarios de prueba son ficticios.

## Fuera de alcance (posibles mejoras)
Valoración de compañeros, notificaciones por correo, estadísticas del club, pagos reales.
