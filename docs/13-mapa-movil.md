# Mapa móvil y actualización de recursos · paquete 06

## Problema comunicado por Denys

El 19/11/2026, después del PR #17, Denys detectó en un iPhone que no aparecía la
ficha del club seleccionado, la hoja inferior se deformaba y las acciones de
invitado seguían desplazándose fuera de las tarjetas.

Había un defecto confirmado en 05: `.rp-mobile-selection` conservaba
`display:none`; actualizar su contenido no hacía visible la ficha. Las pruebas
anteriores de disposición no incluían verificar el enlace de esa ficha.

Las vistas también enlazaban CSS y JS propios con nombres sin versión. La captura
con una flecha SVG enorme es compatible con mezclar HTML nuevo y estilos anteriores;
es una hipótesis de caché, no una lectura del caché privado del iPhone.

## Comportamiento nuevo

- Se conserva una sola fila horizontal de deportes encima del mapa.
- Al abrir la pantalla se ve el mapa y una cabecera inferior breve con el número
  de clubes y «Ver lista».
- Tocar un marcador abre una ficha compacta con imagen, nombre, dirección,
  deportes y el enlace «Ver detalles». Otra selección actualiza esa misma ficha.
- Arrastrar la cabecera hacia arriba abre el listado y oculta/inactiva la zona
  del mapa. Arrastrarla hacia abajo vuelve al mapa.
- También funcionan «Ver lista», «Mapa» y las teclas arriba/abajo, Home/End y Escape.
  Cerrar la ficha devuelve el espacio al mapa. La lista se desplaza con scroll
  nativo; el gesto de la hoja se hace sobre su cabecera, no sobre las tarjetas.
- Se eliminan los tres selectores Mapa/Ambos/Lista y la flecha SVG de la hoja.
- La hoja se desplaza con transformaciones; no cambia las dimensiones de Leaflet.
  Su recorrido termina después de pointerup o pointercancel y respeta movimiento reducido.
- Si no se descarga Leaflet, el listado continúa permitiendo abrir los clubes.
- La acción de invitado usa `.rp-court-guest`, una fila propia de ancho completo.
  En teléfono el botón ocupa todo el ancho útil, dentro de la tarjeta.

## Recursos y archivos

`App\Support\FrontendAsset` añade una versión SHA-256 al URL de los CSS y JS
enlazados desde el layout. Un cambio del contenido genera una URL distinta sin
depender de que el usuario borre manualmente el caché. La versión no es un secreto.
El módulo activo de la portada es `public/ui/club-explorer.js`; utiliza el nuevo
`club-drawer.js` y su import lleva la huella del archivo entregado. Los antiguos
home.js/explorer-sheet.js se conservan por compatibilidad, pero la vista ya no los carga.

Si se modifica club-drawer.js en el futuro, hay que actualizar la versión de su
import en club-explorer.js o migrar estos módulos al empaquetado Vite. No basta con
cambiar el archivo dependiente dejando un import versionado anterior.

No hay cambios en rutas, controladores, migraciones ni reglas de reserva. La
modificación de clubs/show.blade.php es solo la clase del contenedor de invitado;
se ha comprobado una combinación Git limpia con la disponibilidad del paquete 04.
El instalador se detiene si los archivos del ASUS contienen otra versión.

## Verificación al preparar el ZIP

| Comprobación | Resultado observado |
| --- | --- |
| Plantillas representativas, CSS/JS reales, 9 anchos de 320 a 1600 px y 6 páginas | 54 casos sin desbordamiento horizontal ni acciones de pista fuera de su tarjeta |
| Marcador y ficha en 320 y 390 px | Ficha visible y enlace dentro del área útil |
| Enlace de la ficha en 390 px, mediante clic normal | Abre la página del club ficticio elegido |
| Arrastre arriba y abajo de la cabecera | Cambia entre listado y mapa; termina sin arrastre activo |
| Vista de listado | Mapa oculto/inactivo; 6 clubes ficticios con enlaces |
| Leaflet ausente | Lista accesible con 6 clubes ficticios, sin errores JS observados |
| Node: contrato de datos, cargador y confirmación | 16 pruebas correctas |
| PHP: pruebas de versión de recursos y vistas | Pendientes de ejecutar en el ASUS |
| Safari/iPhone físico y nueva versión desplegada | Pendientes de verificar por el equipo |

La revisión del ZIP se realiza en Chrome sobre HTML de prueba con datos ficticios;
no es una ejecución completa de Laravel ni una prueba real de Safari. Se consultó
la página pública de Forus para contrastar la estructura del invitado y sus recursos.
No se ha publicado este paquete por crear el ZIP.

## Aceptación en el ASUS y en el iPhone

1. Instalar en una rama propia basada en main y limpiar las vistas.
2. Ejecutar FrontendAssetTest, MapPageTest y FrontendScreensTest. Los tests de mapa
   y pantallas usan SQLite en memoria; FrontendAssetTest no usa base de datos.
3. Sin iniciar sesión, revisar una pista en anchos de teléfono y tablet. El botón
   de acceso debe permanecer dentro de su tarjeta; seguir el enlace al login.
4. En móvil: tocar varios marcadores, abrir «Ver detalles», volver, desplegar el
   listado, desplazarlo y volver al mapa tanto con botones como con la cabecera.
5. Comprobar también la orientación horizontal y la interfaz con sesión iniciada.
6. Revisar el PR con Joy y, tras el despliegue, repetir en Safari sin depender de
   una limpieza manual del caché. Si falla un caso, registrar el ancho y el estado.

## Entrada de Render ya existente

El Static Site `https://reserva-pistes-inicio.onrender.com/` se publicó desde main
en el commit de merge 9fda7bf (PR #17). Se observó su estado Live y su redirección
al Web Service, que mostró 41 clubes. La prueba de arranque desde reposo aún no
está confirmada. El paquete 06 conserva este acceso y la escena de carga aprobada.

Referencias técnicas: https://www.php.net/manual/en/function.hash-file.php,
https://developer.mozilla.org/en-US/docs/Web/API/Element/setPointerCapture y
https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/touch-action.
