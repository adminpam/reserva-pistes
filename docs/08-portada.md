# 08 · Portada y mapa

Paquete 02, preparado el 17/11/2026 con asistencia de ChatGPT. Se aplica encima
de los paquetes 01 y 01b. Revisión visual del equipo y pruebas PHP pendientes.

## Diseño

En escritorio la portada sigue la referencia aportada por Denys: cabecera verde
oscura, símbolo original del equipo, título grande, ilustración de pista y mapa
ancho con panel de deportes. Se conserva la atribución visible de OpenStreetMap.
La referencia se usa como guía visual: el mapa muestra datos reales de la base,
no una imagen fija ni el número de clubes escrito a mano.

| Dispositivo | Estructura |
| --- | --- |
| Escritorio, más de 1100 px | Panel de deportes a la izquierda y mapa a la derecha; ficha emergente sobre un marcador. |
| Tablet, 721–1100 px | Banda de filtros con tres columnas de deportes y mapa debajo; menú compacto en la cabecera. |
| Teléfono, hasta 720 px | Título breve «¿Dónde jugamos?», deportes en desplazamiento horizontal, mapa como área principal, ficha del club debajo y navegación inferior. |

Los botones táctiles y controles del mapa mantienen al menos 44 px; los enlaces
principales del menú móvil tienen 48 px. La ficha móvil se lleva a la vista al
seleccionar un club. No se comprime el panel lateral de escritorio para meterlo
en el teléfono. Los iconos de deporte son SVG propios.

El símbolo de marca procede de `public/brand/logo-original.png`, sin regenerar.
La ilustración de pista de la portada es decorativa y no sustituye al logotipo.
La animación de carga aprobada no se modifica en este paquete.

## Código y explicación para la defensa

| Archivo | Responsabilidad |
| --- | --- |
| `resources/views/layouts/app.blade.php` | Cabecera común, menú y orden de carga de estilos. |
| `partials/navigation-links.blade.php` | Una lista de destinos para escritorio y menú de teléfono. Las rutas privadas mantienen la autenticación. |
| `resources/views/map/index.blade.php` | Estructura semántica de la portada y filtros procedentes de la base de datos. |
| `public/ui/shell.css` | Identidad, cabecera, menú y navegación inferior. |
| `public/ui/home.css` | Composición de escritorio, tablet y teléfono. |
| `public/ui/home.js` | Petición de clubes, selección, marcadores, ficha y cambios de tamaño. |
| `public/ui/map-data.js` | Validación del contrato JSON y construcción segura de las fichas con nodos DOM. |
| `MapController.php` | Deportes ordenados y clubes filtrados con direcciones, deportes y miniatura ilustrativa. |

El mapa antiguo se retira de `resources/js/app.js` para inicializar Leaflet una
sola vez. Ese archivo conserva sonido, tiempo y formularios. El icono de sonido
usa `toggleAttribute` porque los elementos SVG no ofrecen la misma propiedad
`hidden` que un HTMLElement. CSS oculta explícitamente los elementos con ese atributo.

La petición anterior se cancela al cambiar de deporte. Un número de secuencia
impide que una respuesta antigua sustituya el resultado del último filtro. Si
una petición supera 15 segundos, se ofrece reintentar. `ResizeObserver` comunica
los cambios de tamaño a Leaflet mediante `invalidateSize`.

No se interpolan nombres o direcciones dentro de `innerHTML`: se usa
`textContent`. Los enlaces e imágenes del API solo se aceptan en el mismo origen.
La miniatura identifica su carácter ilustrativo; no se presenta como una foto
verificada del club.

## Base de datos local vacía

En el último pantallazo local de Denys solo aparecía «Todos» y el contador era
0. Eso es compatible con una base sin deportes y sin clubes; no demuestra un
problema de CSS. Este paquete no introduce datos ficticios para ocultarlo ni
ejecuta migraciones o seeders al instalar.

Con una base vacía se muestra «Todavía no hay deportes disponibles» y un estado
vacío sobre el mapa. Un error de red o de servidor tiene un mensaje diferente y
un botón de reintento. Cuando el equipo confirme la base correcta, podrá cargar
los deportes y ejecutar el importador ya existente de OpenStreetMap. El
importador crea clubes, no pistas; `CourtSeeder` crea una oferta de demostración
para clubes que todavía no tienen pistas. Revisar ese flujo por separado del diseño.

## Validación realizada y pendiente

- Sintaxis JavaScript comprobada.
- Cinco pruebas Node aprobadas para coordenadas, enlaces, imágenes, estados
  vacíos, singular/plural y tratamiento como texto de contenido con etiquetas HTML.
- Instalador verificado sobre una copia del código recibido con 01 y 01b:
  comprobación de versiones, copia de seguridad y segunda ejecución sin cambios.
- No se ha podido observar el stand local desde el navegador de revisión:
  el navegador no alcanza el servidor del entorno de código. No se declara
  confirmada la coincidencia visual en navegador ni la adaptación en dispositivos.
- Este entorno no dispone de PHP ni Composer. Las dos pruebas de `MapPageTest`
  están preparadas para ejecutarse en el ASUS con SQLite en memoria. No eliminan
  las tablas ni los datos de la base de trabajo.

Después de instalar, desde el proyecto en el ASUS de Denys:

```powershell
npm run build
php artisan view:clear
php artisan test --filter=MapPageTest
node --test tests/Frontend/map-data.test.mjs
```

Abrir http://127.0.0.1:8000/ y recargar con Ctrl+F5. Verificar los anchos 320,
390, 768, 1024, 1440 y 1920 px; teléfono en vertical y horizontal; navegación
con teclado; un único icono de sonido; selección de deporte; ficha del marcador;
base vacía y pérdida de red. La comprobación final debe hacerse en Laravel,
con los datos del equipo y también después del despliegue.

## Git y revisión

Antes de instalar el paquete en una nueva rama, comprobar el estado de los
cambios de marca y carga. No se deben perder ni mezclar sin revisar esos cambios.
La nueva funcionalidad corresponde a la parte de Joy; Joy debe revisar y poder
explicar el código de la portada. El commit y el PR se crean cuando el equipo
haya comprobado el resultado. Los enlaces del registro no se inventan.

## Fuentes oficiales

- Leaflet 1.9.4: https://leafletjs.com/reference-1.9.4.html
- Uso de teselas OSM: https://operations.osmfoundation.org/policies/tiles/

No se añaden claves, servicios de pago ni dependencias de npm. Las teselas se
consultan desde el navegador con su caché normal y se conserva la atribución;
no hay descargas masivas ni precarga de mapas.
