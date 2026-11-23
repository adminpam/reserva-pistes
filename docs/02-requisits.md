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
- RF6. Crear un **partido abierto** al reservar: el número de plazas depende del deporte y se puede fijar un rango de nivel (1–5), con ambos límites o uno solo. Si se fija un rango, incluye el nivel del organizador; sin rango no se exige nivel.
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


## Reglas de administración · 08

- Solo se asignan clubes a usuarios con rol manager. Un gestor debe quedar sin
  clubes antes de cambiar a otro rol o eliminarse.
- Un gestor solo administra sus pistas y consulta reservas de sus propios clubes.
- Se conservan todas las reservas y participaciones: no se borra un usuario con
  historial, una pista con reservas ni un club con pistas.
- Una pista con reservas conserva club y deporte; los cambios de horario no dejan
  fuera reservas confirmadas futuras o en curso. El precio guardado no se recalcula.
- El aforo se puede configurar antes del primer partido; después se conserva
  también para los partidos pasados o cancelados. Un deporte con pistas o niveles
  asociados no se elimina.
- Aceptación nueva pendiente de ejecutar con Joy. Detalle y casos:
  [Administración y gestor](15-administracion-y-gestor.md).


## Perfil y cuentas · 09

- El usuario modifica solo su nombre y sus niveles; IDs de deportes desconocidos
  no guardan cambios parciales. No juego elimina únicamente el nivel elegido.
- Puede subir, sustituir y quitar su foto. JPEG/PNG/WebP; servidor hasta 512 KB.
  La foto es visible a usuarios autenticados y se guarda junto con la base de datos.
- Puede cambiar contraseña confirmando la actual y la nueva; la sesión actual
  sigue abierta y las otras requieren acceso de nuevo en su siguiente ruta privada.
- El registro público crea jugadores, conserva contraseñas con hash y valida
  correos duplicados. Se limitan intentos de registro/acceso/cambio de contraseña.
- Criterios preparados en 09; la aceptación PHP, visual y manual sigue pendiente.
