# 11 · Datos locales y disponibilidad al volver a una pestaña

## Punto de partida comprobado por Joy · 18/11/2026

- Lenovo: proyecto `C:\Dev\reserva-pistes`; rama `feat/backend-datos`.
- Actualización de `main` hasta `8a8b39c`, después del PR #16 del frontend.
- Composer instalado desde lock y requisitos correctos. El PHP real de su
  terminal es 8.5.11, aunque la configuración compatible del proyecto sigue en 8.2.0.
- `npm ci` y build correctos con npm 12.2.0/Vite 6.4.6. El postinstall de esbuild
  se bloqueó por la política local, pero el build funcionó. El permiso anterior
  sigue guardado en el stash; no se ha restaurado ni publicado por necesidad.
- SQLite local; todas las migraciones aplicadas. Antes: 6 deportes, 11 clubes,
  4 pistas y 0 reservas. Después de `CourtSeeder`: **29 pistas, 0 clubes sin pistas**.
- Registro/inicio de sesión utilizados por Joy sin error comunicado. La ficha
  muestra pistas y precios; el importe de la reserva aparece en «Mis reservas».
- Cancelar conserva la entrada como cancelada y libera la hora al consultar de
  nuevo. La segunda pestaña mantenía la ficha anterior hasta esa nueva consulta.

Los clubes importados proceden de OSM. Las pistas y sus deportes, horarios y
precios generados son de demostración, no una oferta verificada de esos clubes.
No se sube la SQLite del equipo a Git ni se ejecuta un seeder global sobre producción.

## Cambio del paquete 04

`ClubAvailability` calcula los huecos de una hora de todas las pistas del club con
una consulta de reservas confirmadas del día. Comprueba intersecciones de intervalos,
incluidas las que comenzaron antes de ese día, y descarta horas ya pasadas.
Las canceladas dejan de bloquear, pero permanecen en el historial.

`ClubController` utiliza ese servicio tanto para la ficha Blade inicial como para
`GET /clubs/{club}/availability?date=YYYY-MM-DD`. La respuesta pública contiene
fecha, hora de consulta e IDs/horas/disponibilidad de pistas. No contiene quién
reserva ni sus datos. La fecha se valida y la respuesta se marca `no-store`.

`availability.js` consulta ese endpoint cuando la pestaña vuelve a ser visible,
recupera el foco o se restaura desde la caché de navegación. No recarga toda la
página: conserva niveles, partido abierto y una selección de hora que siga libre.
Si la hora elegida se ocupa, se desmarca y el botón queda deshabilitado hasta
que la persona elija otra. No se cambia silenciosamente su reserva a otra hora.

Si falla la red o cambia la lista de pistas/horarios, se informa y se ofrece volver
a consultar con «Ver disponibilidad». No se afirma que los huecos estén actualizados
si la petición falla. Se agrupan eventos de foco/visibilidad para evitar duplicados;
la espera de red tiene un límite de 10 segundos.

## Explicación para la defensa

La base de datos es la fuente del estado. Cancelar cambia `status` a `cancelled`;
no elimina una fila. La ficha que ya estaba dibujada necesita otra lectura para
reflejarlo. Ahora esa lectura se hace al volver a la pestaña y aplica únicamente
las horas a los formularios existentes. La página inicial y la lectura JSON
comparten el cálculo para no mostrar reglas diferentes.

Esto no sustituye la validación de `BookingController` al enviar una reserva,
ni garantiza por sí solo protección frente a dos reservas simultáneas. Esa
revisión transaccional sigue en la fase de backend, junto con permisos y aforo.
No se añade pago en este paquete. La fotografía de perfil y el pago de prueba
siguen pendientes con Joy; los despliegues se coordinan con Denys.

## Instalación y pruebas

Instalar el ZIP sobre `main` actualizado a `8a8b39c`, desde la rama de Joy.
El instalador admite las diferencias CRLF/LF de Git en Windows, verifica el resto
del contenido antes de copiar y guarda los originales en Downloads.

Después, en el Lenovo, dentro del proyecto y en otra terminal distinta de serve:

```powershell
php artisan route:clear
php artisan view:clear
php artisan test --filter=ClubAvailabilityTest
node --test tests/Frontend/availability.test.mjs tests/Frontend/screens.test.mjs tests/Frontend/map-data.test.mjs
```

`ClubAvailabilityTest` prepara y migra su propia SQLite `:memory:`; no cambia los
datos de Joy. Se preparan siete casos: privacidad/no-cache, cancelación, solapamiento
parcial, cruce de día, aislamiento por club, pasado y peticiones inválidas.
JavaScript: **14 pruebas aprobadas** al preparar el paquete (6 de disponibilidad,
3 de confirmaciones y 5 de datos del mapa), ejecutadas sobre la copia integrada.
Joy ejecutó los 7 tests PHP en el Lenovo: 45 aserciones completadas, con dos
avisos de compatibilidad PHP 8.5 en config/database.php. Se prepara el ajuste
04a; la repetición sin avisos y la observación del comportamiento nuevo están pendientes.

Regresión manual: mantener la ficha en una pestaña y cancelar una reserva futura
desde «Mis reservas» en otra. Volver a la ficha sin pulsar refrescar: la hora debe
quedar libre y debe aparecer «Disponibilidad actualizada». Repetir ocupando una
hora seleccionada en otra sesión: se desmarca, sin seleccionar otra por el usuario.

## Fuentes oficiales

- https://laravel.com/docs/12.x/responses
- https://laravel.com/docs/12.x/validation
- https://developer.mozilla.org/en-US/docs/Web/API/Document/visibilitychange_event
- https://developer.mozilla.org/en-US/docs/Web/API/Window/pageshow_event


## Compatibilidad del equipo · 04a

La deprecación se origina al evaluar las opciones MySQL/MariaDB de la configuración,
incluso si la conexión utilizada es SQLite. PHP 8.5 recomienda la constante del
driver `Pdo\Mysql::ATTR_SSL_CA`. Una comprobación con `defined` permite utilizarla
cuando existe y mantener el proyecto ejecutable en PHP 8.2. El acceso se mantiene
dentro del condicional `extension_loaded('pdo_mysql')`, de modo que tampoco se
requiere instalar MySQL para una configuración local que utiliza solo SQLite.

Fuentes oficiales:

- https://www.php.net/manual/en/migration85.deprecated.php
- https://www.php.net/manual/en/class.pdo-mysql.php
- https://www.php.net/manual/en/function.defined.php

Este ajuste de configuración se revisa junto al paquete 04 en la misma rama.
No se requiere recompilar JavaScript. Repetir la prueba PHP indicada en `06-proves.md`.
