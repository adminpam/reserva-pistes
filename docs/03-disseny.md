# 03 · Diseño

## Modelo de datos

| Tabla | Campos principales | Notas |
|---|---|---|
| users | name, email, password, **role**, avatar_version | role: player / manager / admin |
| sports | name, slug, **players_per_match**, icon | plazas de un partido abierto |
| clubs | name, address, city, **lat, lng**, osm_id, manager_id | osm_id evita duplicados al importar |
| courts | club_id, sport_id, name, **price_per_hour** (céntimos), open_hour, close_hour, is_outdoor | |
| bookings | court_id, user_id, **starts_at, ends_at**, status, **is_open**, min_level, max_level, price_total | un slot = una hora |
| booking_players | booking_id, user_id (únicos juntos) | participantes de partidos abiertos |
| user_sport_levels | user_id, sport_id, **level** (1–5) | nivel por deporte |
| user_avatars | **user_id** (PK/FK), mime_type, image_base64 | foto pequeña; relación users 1 → 0..1 foto |

**Regla anti-solapamiento:** una reserva nueva se rechaza si en la misma pista existe otra confirmada con `starts_at < nueva.ends_at` y `ends_at > nueva.starts_at`. En 07 se comprueba dentro de una transacción después de bloquear la fila de Court con `lockForUpdate`; así existe una fila común incluso en una pista aún vacía. Las altas/bajas/cancelaciones de partidos bloquean la fila de Booking antes de comprobar estado y aforo. La prueba con peticiones paralelas en PostgreSQL sigue pendiente.

## Identidad visual

- Paleta: fondo `#F7F7F5`, tinta `#1F2933`, ámbar `#F59E0B` (acciones), turquesa `#0E9F8E` (estados positivos), rojo `#DC2626` (errores).
- Tipografía actual: **Manrope y Sora**, archivos locales, sin depender de Google Fonts.
- Logo e iconos de deportes: SVG propios (ver `docs/assets-pendientes.md`).
- Animaciones moderadas: entrada de tarjetas y realce al pasar el ratón, desactivadas con `prefers-reduced-motion`.
- Dos sonidos cortos (éxito/error) generados con WebAudio, **desactivados por defecto**, con botón en la cabecera.

## Pantallas
1. Mapa (home) con filtro por deporte y hero.
2. Ficha de club: tiempo (si hay pista exterior), selector de día, tarjeta por pista con horas ocupadas deshabilitadas.
3. Partidos abiertos (búsqueda) / Mis partidos / Mis reservas.
4. Perfil con niveles.
5. Panel de gestor (pistas + reservas) y panel de admin (clubes, deportes, usuarios).


## Lectura de disponibilidad · paquete 04

`GET /clubs/{club}/availability?date=YYYY-MM-DD` es una lectura pública de huecos.
Un club inexistente responde 404; una fecha ausente o inválida, 422 en JSON.
Responde sin caché compartida y no publica los usuarios de las reservas.

```json
{
  "date": "2026-11-19",
  "checked_at": "2026-11-18T12:30:00+01:00",
  "courts": [{"id": 1, "hours": [{"hour": 10, "available": true}]}]
}
```

El ejemplo es ilustrativo. `ClubAvailability` concentra el cálculo utilizado
también por Blade; `availability.js` actualiza los inputs de hora al volver a la
pestaña. Consultar un hueco no lo bloquea ni lo reserva: escribir la reserva
sigue siendo responsabilidad de `BookingController` y sus reglas transaccionales.


## Integridad administrativa · 08

Se conservan esquema y rutas. Los controladores rechazan los borrados que perderían
reservas/participaciones: club con pistas, pista con reservas o usuario con historial.
Deportes con pistas o niveles tampoco se borran. Las cascadas SQL siguen existiendo;
estas reglas protegen las rutas HTTP y no sustituyen una política de mantenimiento.

La fila del gestor coordina asignación, rol y operaciones de pistas; la del club
coordina creación de pistas y eliminación de clubes vacíos. Las reservas toman
usuario, deporte y pista; las altas a partidos, usuario y reserva. Se vuelve a leer
la propiedad después de los bloqueos. Los cambios de rol/baja de usuarios ordenan
primero las filas admin y verifican de nuevo al actor. La comprobación de carreras
reales con PostgreSQL sigue pendiente.

No hay copia histórica del aforo, club o deporte en bookings: por eso esos cambios
se restringen una vez usados. price_total sí es una copia de la tarifa; cambiar el
precio de la pista no modifica una reserva existente. Nombre de pista e indicador
interior/exterior siguen siendo datos vivos, no una fotografía histórica inmutable.
Ver [15-administracion-y-gestor.md](15-administracion-y-gestor.md).


## Perfil, fotos y acceso · 09

Nombre y niveles se guardan en una transacción: usuario primero, deportes ordenados
después. Las claves de levels se validan antes de modificar datos. Se comparten las
filas bloqueadas con los cambios administrativos y las reservas del usuario.

user_avatars separa la imagen de los datos consultados en listas. El base64 se
oculta al serializar el modelo; GET /users/{user}/avatar sirve bytes a usuarios
autenticados, con tipo validado, nosniff y caché privada. avatar_version identifica
la URL y cambia al sustituir la foto. El almacenamiento es la misma DB y depende
de que esta sea persistente en Render; no se basa en archivos del contenedor.

| Método | Ruta | Requisito |
| --- | --- | --- |
| PATCH | /profile | auth + auth.session; name y levels[ID] |
| POST | /profile/avatar | auth + auth.session; multipart con avatar |
| DELETE | /profile/avatar | auth + auth.session; borra únicamente la foto propia |
| GET | /users/{user}/avatar | auth + auth.session; bytes privados o 404 |
| PATCH | /profile/password | auth + auth.session; contraseña actual y nueva confirmada |

Se usan RateLimiter y Hash de Laravel. auth.session protege las rutas privadas;
logoutOtherDevices y la rotación de remember_token invalidan accesos anteriores
después del cambio de contraseña. No hay API externa ni nuevas dependencias.
Guía y límites: [16-perfil-cuentas-y-foto.md](16-perfil-cuentas-y-foto.md).
