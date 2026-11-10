# Propuesta inicial (versión 2): ReservaPistes

**Autores:** Denys Peresunko y Joy Liliana Castro Valencia

**Módulo:** M0617 Programación asistida con IA (Vibe Coding), IES Sabadell

## Descripción

ReservaPistes es una aplicación web propia para reservar pistas y encontrar jugadores. El objetivo es parecido al de aplicaciones como Playtomic (reservar una pista y completar el partido con otros jugadores), pero nuestro proyecto tiene su propio nombre, su propio diseño y su propio código, y está pensado para otros deportes: tenis de mesa, bádminton, baloncesto 3x3, voleibol... Los jugadores encuentran clubes en un mapa, reservan pistas y abren partidos para que se apunten otros jugadores de su nivel. Los gestores de cada club administran sus pistas y sus reservas.

## Originalidad

- Diseño propio: paleta de colores, tipografías, iconos y pantallas diseñados por nosotros, distintos de los de cualquier aplicación existente.
- No usamos logotipos, imágenes, textos ni código de otras aplicaciones. El mapa usa OpenStreetMap, con su atribución.
- Deportes con sus reglas reales de plazas, y datos reales de instalaciones deportivas procedentes de OpenStreetMap. Los usuarios y las reservas de ejemplo son ficticios.

## Problema que resuelve

Es difícil encontrar una pista libre y gente con quien jugar. Las reservas por teléfono o en papel provocan reservas dobles, y muchos jugadores se quedan sin partido por falta de compañeros. La aplicación junta las dos cosas: reservar la pista y completar el partido con jugadores reales.

## Roles

- **Jugador:** tiene perfil y nivel por deporte, busca clubes en el mapa, reserva pistas, abre partidos o se apunta a los de otros y consulta su historial.
- **Gestor de club:** administra las pistas, los horarios y los precios de su club y ve las reservas de su club.
- **Administrador:** gestiona los clubes, los deportes y los usuarios de la plataforma.

## Funcionalidades mínimas

- Registro e inicio de sesión, con perfil de jugador y nivel (de 1 a 5) por deporte.
- Mapa de clubes con marcadores (Leaflet y OpenStreetMap) y filtro por deporte.
- Ficha de club con sus pistas, horarios y precios.
- Reservar una franja de una hora en una pista, sin solapamientos. El pago se simula, no hay dinero real.
- Partidos abiertos: al reservar, el jugador puede abrir el partido a otros. El número de plazas depende del deporte y se puede indicar un nivel mínimo y máximo.
- Buscar partidos abiertos (por deporte, fecha y nivel), apuntarse y salirse.
- Mis reservas y mis partidos, con cancelación (con límite de tiempo).
- Gestor de club: crear, editar y desactivar pistas, y ver las reservas de su club.
- Administrador: gestionar clubes, deportes y usuarios.

## Funcionalidades opcionales, si nos da tiempo

- Valoración de los compañeros después del partido.
- Historial de los jugadores con los que has jugado.
- Avisos por correo (en local, con el registro de Laravel).
- Estadísticas sencillas para el gestor del club.

## Datos (modelo inicial)

Tablas principales: usuarios, deportes, clubes (con latitud y longitud), pistas, reservas, jugadores de cada partido y nivel de cada usuario por deporte. Un club tiene muchas pistas, una pista tiene muchas reservas y una reserva abierta tiene varios jugadores. Los clubes se importan de datos reales de OpenStreetMap. Los usuarios y las reservas de ejemplo son ficticios: no usamos datos personales reales.

## Tecnologías

- Backend: Laravel.
- Frontend: plantillas Blade con CSS propio (o Tailwind) y JavaScript para el mapa.
- Mapa: Leaflet con OpenStreetMap (gratuito y sin clave de API).
- Datos reales de instalaciones: API Overpass de OpenStreetMap, ejecutada una sola vez con un comando de importación (no en cada visita).
- Búsqueda de direcciones: Nominatim de OpenStreetMap.
- Previsión del tiempo para pistas exteriores: Open-Meteo (sin clave de API).
- Base de datos: MySQL o PostgreSQL en producción, según la plataforma gratuita de despliegue (se decide antes de programar).
- Control de versiones: GitHub, con ramas por funcionalidad y pull requests revisados por la otra persona.
- Herramienta de IA: Claude.

## Datos reales y servicios externos

- **Deportes:** tenis de mesa, bádminton, baloncesto 3x3, voleibol, fútbol sala y tenis, con su número real de jugadores por partido (por ejemplo, individual o dobles en tenis de mesa y bádminton).
- **Ubicaciones:** las instalaciones deportivas reales (nombre, dirección, coordenadas y deportes) se importan de OpenStreetMap para la zona de Sabadell, Terrassa y Barcelona. Los datos son de colaboradores de OpenStreetMap y se citan con su atribución.
- **Aviso en la aplicación:** es un proyecto académico. Las reservas y los precios son simulados y no se envían a los clubes reales.
- **Claves de API:** los servicios elegidos no necesitan clave. Si más adelante usamos alguno que la necesite, la clave irá en el archivo .env y en las variables de entorno de la plataforma, nunca en el repositorio ni en los mensajes a la IA.
- **Usuarios reales:** cualquier persona puede registrarse en la aplicación desplegada (por ejemplo, compañeros durante la presentación). Los datos de ejemplo que cargamos nosotros son ficticios.

## Convenciones de código

- Nombres de clases, funciones y tablas en inglés (convención de Laravel) y textos de la interfaz en español.
- Cada clase y cada función importante lleva un comentario que explica para qué sirve. El código de cada uno lo comentamos en nuestro idioma de trabajo.
- Nada se une a main sin revisión de la otra persona, y los dos tenemos que saber explicar para qué sirve cada parte.

## Plan de trabajo (15 horas)

1. Preparación (1 h): repositorio, colaboradores y proyecto Laravel.
2. Usuarios, perfiles, deportes y datos de ejemplo (1,5 h).
3. Clubes, pistas y gestión del gestor (2 h).
4. Mapa y búsqueda de clubes (1,5 h).
5. Reservas sin solapamientos (2 h).
6. Partidos abiertos (2 h).
7. Pruebas (1 h).
8. Despliegue (1 h).
9. Documentación y presentación (1,5 h), con 1,5 h de margen.

## Reparto

- **Denys:** modelo de datos, usuarios y roles, reservas, partidos abiertos y despliegue.
- **Joy:** clubes y pistas (gestión del gestor de club), mapa, vistas y diseño, perfiles de jugador y manual de usuario.
- **Los dos:** pruebas, documentación y registro de IA.

## Riesgos

- El despliegue gratuito de Laravel: lo decidimos al empezar, con la documentación oficial de la plataforma.
- El tamaño del proyecto: por eso separamos lo mínimo de lo opcional y recortamos primero lo opcional.
- Los solapamientos de reservas y las plazas de los partidos: los cubrimos con pruebas automáticas.
