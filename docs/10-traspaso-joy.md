# 10 · Traspaso a Joy

Documento de continuidad para Joy Liliana Castro Valencia. Denys seguirá en ruso;
cuando Joy escriba como «Joy:», se continúa con ella en español. Código y documentos
de su parte: comentarios y explicaciones en español.

## Estado y acuerdos

- Repositorio compartido: https://github.com/adminpam/reserva-pistes.
- Laravel 12, PHP fijado a 8.2.0 en Composer; Blade, CSS propio y JavaScript.
- Interfaz preparada en paquetes 01, 01b, 02 y 03. No usar una copia anterior de Claude
  para sustituir las vistas nuevas. Login y registro estaban aprobados por Denys y
  sus plantillas no se han cambiado en 03.
- El paquete 03 cubre perfil, club/horarios, reservas, partidos, gestor, administrador,
  errores, confirmaciones y mapa móvil. Denys da el frontend por aceptado de forma
  preliminar tras probarlo en su equipo. La revisión a todas las anchuras y con
  datos completos sigue pendiente; las pruebas de renderizado no la sustituyen.
- La aplicación existente está en https://reserva-pistes.onrender.com/. El código nuevo
  de esta sesión todavía no se ha confirmado como publicado en Render.
- Denys quiere terminar el despliegue y comprobar Render con él. No se da ese trabajo
  por terminado ni se publican cambios pendientes sin seguir el flujo acordado.

## Git y sincronización

Estado comunicado por Denys en el ASUS:

- `82806d1`, `feat/marca-carga`: estado previo al rediseño de portada.
- `b505a4a`, `feat/portada-mapa`: portada y mapa adaptable.
- Rama actual: `feat/frontend-completo`; paquete 03 instalado y sus pruebas PHP
  aprobadas. El commit de 03 y su publicación todavía no se han comunicado.
- Ajuste 03b: microanimaciones y documentación de cierre; su instalación en el
  ASUS se comprueba aparte. No modifica controladores, base ni flujo de pago.

**Primer paso antes de que Joy programe:** Denys revisa las diferencias, guarda
03/03b y publica su rama. Compartir con Joy el nombre de rama y el hash real.
Publicar una rama no equivale a desplegarla. Antes de fusionar a `main`, Joy
revisa el PR y ambos deben poder explicar el código. Si Render despliega `main`
automáticamente, el merge se coordina con Denys para observar también ese despliegue.

Cuando las ramas se hayan publicado y fusionado, en el Lenovo de Joy:

```powershell
git status --short
git fetch origin
git switch main
git pull --ff-only origin main
composer install
npm ci
npm run build
php artisan view:clear
```

Si `git status` muestra trabajo propio, conservarlo primero en su rama y commit;
no sobrescribirlo ni usar un reset para hacer pull. Si el frontend aún está en una
rama remota sin fusionar, obtener de Denys su nombre real antes de cambiar de rama.
Revisar los cambios locales del `.env`; el archivo no se comparte ni se sube.

## Backend: siguiente fase con Joy

La interfaz utiliza los controladores actuales: no son botones simulados sin ruta.
Sin embargo, antes de considerar el proyecto terminado, revisar al menos:

1. **Datos**: cargar deportes; importar clubes de OpenStreetMap; revisar la relación
   entre clubes y pistas de demostración. El local de Denys mostraba 0 clubes y 0
   deportes. El instalador del frontend no introduce datos para ocultarlo.
2. **Permisos**: acceso por rol y propiedad en cada escritura; validar que el usuario
   asignado como gestor tenga realmente rol manager; proteger borrados y conservar
   historial donde corresponda.
3. **Concurrencia**: la comprobación de huecos y el aforo deben seguir siendo válidos
   con dos peticiones simultáneas. Revisar el bloqueo de una pista al reservar y del
   partido al unirse. La desactivación de un botón no sustituye una transacción.
4. **Validación**: comprobar IDs de deporte en los niveles, rangos mínimo/máximo,
   fechas y horas, filtros recibidos, y repetición de acciones.
5. **Rendimiento**: cargar las relaciones necesarias (`user`, `players`, clubes,
   deportes) para evitar consultas repetidas en las listas.
6. **Experiencia**: verificar reservas, cancelaciones, entrada/salida de partidos,
   errores y formularios con datos reales de prueba; revisar también accesos directos.
7. **Administración**: el diseño está preparado, pero falta la revisión funcional
   integral de los flujos con Joy. No añadir acciones aparentes que no tengan una ruta.

No reescribir la interfaz aprobada para solucionar un problema de datos o permisos.
Mantener los componentes, tamaños táctiles y composiciones de móvil.

## Pago de prueba solicitado por Denys — pendiente con Joy

Denys ha pedido una pantalla donde se introduzcan **datos ficticios de pago** y se
obtenga una confirmación simulada. Debe conservar el estilo verde/ámbar, adaptarse
a teléfono y dejar visible «Modo de prueba · no se realiza ningún cobro».

Implementar primero una simulación interna sin contratar servicios, sin tarjeta
de crédito para registrarse y sin claves de un proveedor externo:

- Resumen de club, pista, fecha, hora e importe calculado en el servidor.
- Campos claramente de prueba: titular ficticio, número de prueba admitido por la
  simulación, caducidad y código de prueba. Rechazar números fuera del conjunto de
  prueba para que no se introduzcan tarjetas reales.
- Escenarios de aprobación, rechazo y cancelación con datos de prueba documentados.
- No guardar número completo ni código en base de datos, sesión, logs o registro IA.
- La aprobación debe registrarse en servidor con un identificador de demostración;
  no basta con cambiar el texto mediante JavaScript.
- Concretar estados de reserva/pago, repetición segura de la petición, caducidad
  del hueco pendiente y liberación del horario si se rechaza o cancela.
- Confirmación y recibo de demostración. El importe recibido del navegador no debe
  decidir el precio final.
- Añadir pruebas de éxito, fallo, repetición y conflicto de horario.

**No está implementado en 03.** Actualmente `BookingController` confirma la reserva
de demostración directamente y muestra «pago simulado». La futura pantalla de pago
requiere adaptar ese flujo; no se debe presentar como una pasarela ya integrada.

## Pruebas y documentación

Resultados comunicados por Denys en el ASUS:

- Paquete 01: build y limpieza de vistas correctos; `BrandReadyTest`: 1 test, 5 aserciones.
- Paquete 02: build y limpieza de vistas correctos; `MapPageTest`: 2 tests, 15 aserciones.
- Paquete 03: `FrontendScreensTest` aprobado: **6 tests, 58 aserciones**, 1,31 s.
  Denys acepta preliminarmente la interfaz; publicación y revisión en todos los
  tamaños con datos completos pendientes.

`FrontendScreensTest` prepara su propia SQLite en memoria. Incluye datos ficticios
de jugador, gestor y administrador, así como listas llenas/vacías, formularios,
errores y paginación. Revisar también los tests anteriores antes de una ejecución
global: el `phpunit.xml` antiguo no fijaba una base de pruebas separada.

## Entrega AEA6: requisitos que no se deben perder

- Laravel como backend; GitHub con ramas, commits reales y PR revisado por el otro
  miembro. Ningún cambio en `main` que ninguno de los dos pueda explicar.
- Los documentos deben llamarse exactamente `01-proposta.md`, `02-requisits.md`,
  `03-disseny.md`, `04-installacio.md`, `05-manual-usuari.md`, `06-proves.md` y
  `registre-ia.md` dentro de `docs/`, más README y CHANGELOG en la raíz.
- 04 y 05 se añaden en 03b. Los antiguos `04-arquitectura.md` y
  `05-desplegament.md` quedan como material adicional; no sustituyen los obligatorios.
- README: autores, tecnologías, URL, instalación, capturas y herramientas de IA
  con sus usos reales. Manual con capturas de cada rol. No se exigen capturas del
  proceso, pero sí capturas del producto para README/manual.
- Registro IA: mínimo 15 usos significativos reales con fecha, autor, herramienta,
  objetivo, prompt, resultado y enlace a commit/PR. Un plan sin ejecutar no cuenta.
  Completar los enlaces pendientes después de publicar; no atribuir a Joy cambios
  que todavía no ha revisado ni afirmar un merge inexistente.
- `06-proves.md`: añadir el recorrido completo de demostración y los resultados
  observados, incluidos conflictos de reserva y aforo. No confundir renderizar una
  vista con probar todas las reglas del negocio.
- CHANGELOG: conservar v0.1 y publicar v1.0 con fecha y commit reales cuando la app
  esté operativa. La antigua v1.0 de diciembre era un plan, no una versión publicada.
- No compartir con IA datos personales reales ni claves. No subir `.env` o una
  base con usuarios reales al repositorio. Servicios gratuitos sin tarjeta.

Propuestas acotadas de mejora: transiciones breves, estados de envío y errores
claros, feedback después de la confirmación del servidor y una demostración coherente
para los tres roles. No ampliar con chat, rankings o notificaciones antes de cerrar
reservas, aforo y pago de prueba. Sonidos apagados por defecto.

Fuentes de trabajo: Laravel 12, GitHub y Render, siempre su documentación oficial
antes de introducir una herramienta u opción nueva.

## Cierre con Denys

Después de la revisión funcional con Joy, volver con Denys para publicar el código
en GitHub, revisar los PR, comprobar qué rama despliega el servicio Render y observar
el despliegue de ese commit. Confirmar base PostgreSQL persistente, migraciones,
assets compilados, HTTPS y `APP_DEBUG=false`. La entrada estática opcional del cargador
debe configurarse y probarse por separado; no está creada por instalar estos archivos.


Antes de conectar la base definitiva, comprobar la modalidad y su fecha de caducidad:
el plan Free de Render Postgres expira a los 30 días. El SQLite dentro del contenedor
Free no conserva sus cambios al reiniciar o redesplegar. No elegir una solución que
pida tarjeta; si la base actual no cubre el periodo de la práctica, acordar una base
gratuita persistente y documentar la elección con Denys antes del despliegue final.

Fuente revisada el 18/11/2026: https://render.com/docs/free.
La defensa está prevista para la semana del 07/12; no dar por válida la base sin
comprobar que estará disponible entonces.
