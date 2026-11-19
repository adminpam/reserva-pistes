# Registre d'ús d'IA

Registro de cada uso relevante de IA en el proyecto. Herramientas utilizadas: **Claude (Anthropic), chat web**, y **ChatGPT (OpenAI)**. Se indica la herramienta en las entradas nuevas; las entradas 1–15 pertenecen al registro anterior de Claude. Repositorio: https://github.com/adminpam/reserva-pistes

---

**19/11/2026 — Denys · Adaptación y entrada de Render**
- Herramienta: ChatGPT (OpenAI).
- Objetivo: corregir roturas de interfaz descubiertas al reducir la ventana,
  reorganizar mapa/lista en teléfono y preparar la página de entrada estática.
- Prompt (fragmentos literales): «al reducir las dimenciones de la pantalla en
  unos momentos se rompe la estructura de los elementos» y «al nivel de telefono
  mejora los botones que tapan la mapa y haz que sea accesible esta pantalla que
  nos enseña los clubs y o podemos subir la que la mapa desaparece at all o bajamos
  y solo tenemos la mapa, haz lo logico!».
- Resultado preparado: acciones de pistas y formularios alineados; una fila de
  filtros fuera del mapa; vistas Mapa/Ambos/Lista con arrastre y teclado; lista
  disponible si falla la biblioteca del mapa; margen adaptable de login y precios
  sin cortar el símbolo €. Guía de Static Site con destino fijo a Laravel.
- Validación: revisión Chrome de 54 combinaciones de página/ancho, sin overflow
  horizontal ni botones fuera de sus tarjetas; estados, arrastre y teclado
  comprobados; 6 clubes accesibles sin Leaflet; 13 pruebas Node y constructor
  estático correctos. Son fixtures HTML con datos ficticios, no una ejecución PHP.
  Instalación, feature tests en ASUS, Safari y despliegue del Static Site pendientes.
- Commit/PR: pendiente de publicar por Denys y revisar por Joy.

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

**15 · Plan anterior de despliegue — sin ejecución acreditada (no cuenta como uso realizado)**
- Objetivo: despliegue en producción.
- Prompt: «Pasos para desplegar en Laravel Cloud con PostgreSQL: build de Vite, migrate --force --seed, clubs:import y variables de entorno seguras».
- Resultado: no realizado según este registro. El plan citaba Laravel Cloud, pero el servicio existente es Render. No acredita una publicación ni una etiqueta v1.0.
- Commit: _pendiente_

---

**16 · 17/11/2026 — Denys (revisión de interfaz con Joy pendiente)**
- Herramienta: ChatGPT (OpenAI).
- Objetivo: revisar el código existente y adaptar la marca y la carga a las referencias aportadas por el equipo.
- Prompt (resumen): «La página no me gusta como se ve. Usa el logo original y una pantalla de carga con la misma pista de la referencia y una pelota luminosa que bote. Entrega archivos por paquetes con comandos para instalarlos desde Downloads». Se facilitó el ZIP del código y la URL de Render.
- Resultado: PNG original conservado, pista SVG basada en la referencia, pelota animada, barra indeterminada, carga ligada al documento, entrada estática con comprobación de Laravel y límite de espera, e instalador con comprobación de versiones y copia de seguridad.
- Validación posterior comunicada por Denys: build y limpieza correctos; `BrandReadyTest`: 1 test, 5 aserciones. Pista y pelota aprobadas; el nombre/barra se ajustaron en 01b. Entrada estática sin despliegue confirmado.
- Git: incluido en el commit local `82806d1`; enlace y PR pendientes de publicar y revisar.

---

**17 · 17/11/2026 — Denys**
- Herramienta: ChatGPT (OpenAI).
- Objetivo: ajustar el nombre, el ciclo de espera y la composición de móvil y tablet a partir de la revisión visual de Denys.
- Prompt (resumen): «En móvil no queremos reducir la página de escritorio: queremos una estructura propia. Falta el espacio entre Reserva y Pistes; el segmento de carga debe desaparecer al final y volver a entrar desde el inicio. La pista y la pelota están bien».
- Resultado: nombre con separación visible; segmento que atraviesa la barra en una sola dirección; estructura de teléfono con marca arriba, pista al centro y progreso abajo, variante horizontal y ajustes específicos de tablet. Se conservan la geometría de la pista y el bote aprobado.
- Validación del paquete anterior en el ASUS de Denys: `npm run build` correcto, `php artisan view:clear` correcto y `BrandReadyTest` aprobado (1 test, 5 aserciones), según las salidas compartidas. El ajuste visual de esta entrada queda pendiente de revisar en el navegador del equipo.
- Git: incluido en el commit local `82806d1`; enlace y PR pendientes de publicar y revisar.

---

**18 · 17/11/2026 — Denys (revisión e integración de la parte de Joy pendientes)**
- Herramienta: ChatGPT (OpenAI).
- Objetivo: adaptar la portada a la referencia facilitada, usando el logotipo original y composiciones propias para teléfono y tablet.
- Prompt (resumen): «La página principal se ve mal. Hay que mejorarla como en la referencia y sustituir el dibujo que hace de logo por nuestro logo real». Después se compartió la versión local actual y se aclaró que todavía no estaba publicada.
- Resultado: cabecera original ampliada, navegación completa, título e ilustración grandes, panel de deportes y mapa ancho; controles táctiles, ficha móvil inferior, estados vacíos y errores explícitos. API con miniatura ilustrativa, validación de datos y cancelación de peticiones obsoletas. Corregida la visibilidad de los dos SVG de sonido.
- Validación: 5 pruebas Node de contrato y contenido del mapa aprobadas; sintaxis JS y comprobación del instalador. Pruebas PHP y revisión visual en Laravel pendientes. El navegador del entorno de revisión no pudo alcanzar el stand local; no se declara validación visual en dispositivos.
- Git: commit local `b505a4a` en `feat/portada-mapa`; enlace y PR pendientes de publicar y revisar. Validación posterior: `MapPageTest`, 2 tests y 15 aserciones aprobados en el ASUS.

---

**19 · 17/11/2026 — Denys (continuación posterior con Joy)**
- Herramienta: ChatGPT (OpenAI).
- Objetivo: completar las pantallas restantes de la interfaz según las referencias, sin modificar las plantillas aprobadas de acceso y registro.
- Prompt (resumen): «Quita el scroll del header y déjalo fijo. Haz todas las demás pantallas, también las que no tienen referencia, con nuestro estilo. Seguiré en español con Joy para backend, admin y un pago de prueba; el despliegue en Render lo acabamos conmigo».
- Resultado: pantallas de perfil, club/horarios, reservas, partidos, gestor, administrador y errores; componentes comunes, confirmación y paginación. Cabecera fija y mapa móvil con hoja inferior. Archivo de traspaso a Joy con backend, sincronización GitHub y pago de prueba pendiente.
- Validación comunicada por Denys del paquete 02: build correcto; limpieza de vistas correcta; `MapPageTest`: 2 tests aprobados, 15 aserciones. La portada local se aprobó con la petición de corregir el scroll y fijar la cabecera, incluida en 03.
- Validación del paquete 03: sintaxis JS y pruebas Node al preparar el código. Después Denys ejecutó `FrontendScreensTest` en el ASUS: 6 tests, 58 aserciones, aprobados en 1,31 s. Da la interfaz por terminada preliminarmente; revisión completa por tamaños y despliegue pendientes. Las pantallas de error no dependen de sesión ni de Vite.
- Git: existe el commit local `82806d1` del estado anterior a la portada. Enlace al commit/PR de este paquete: pendiente de la publicación y revisión reales.


---

**20 · 18/11/2026 — Denys**
- Herramienta: ChatGPT (OpenAI).
- Objetivo: preparar la continuidad con Joy, cerrar la documentación pendiente y
  añadir movimiento discreto sin rehacer el frontend aprobado.
- Prompt (fragmentos literales del mensaje de Denys):

  > With joy you finish payment method implementation at the end.
  > in general fill the page, make it working and dynamic in general!
  > host it with me!
  > And if you still can make the page more alive, it could be even better (on level of animations, transitions, make elements more alive and crete some originality where it would be convenient.)

  Denys adjuntó también los requisitos completos de AEA6: nombres obligatorios de
  documentos, capturas, registro IA, versiones y reglas de GitHub.
- Resultado: ajuste 03b de microanimaciones CSS respetando movimiento reducido;
  instalación y manual con los nombres exigidos; estado real de tests y commits;
  handoff de Joy con datos, permisos, concurrencia, pago al final y cierre con
  Denys. Se corrige la antigua v1.0 presentada prematuramente como publicada.
- Validación: usar los resultados PHP compartidos por Denys, sin inventar una
  suite global ni un despliegue. Instalación y observación de 03b en el ASUS
  pendientes hasta que Denys las realice. Capturas finales por rol pendientes.
- Commit/PR: pendiente de crear, publicar y sustituir por el enlace real.

Las entradas con enlaces pendientes se completarán al publicar. La entrada 15 es
un plan sin ejecución y no se cuenta para el mínimo de 15 usos significativos.
