# 12 · Adaptación de interfaz y entrada estática

## Incidencias observadas por Denys · 19/11/2026

Las capturas del equipo muestran una acción de invitado fuera de las tarjetas de
pistas al estrechar la ventana; desalineación entre fecha/botón y horarios/acción;
filtros móviles que ocupan dos filas sobre el mapa; y una hoja de clubes que no
puede pasar claramente de mapa completo a lista completa. También se pidió
preparar la entrada de marca durante el arranque de Render.

## Cambio y explicación para la defensa

- La acción de invitado tiene una posición explícita en la cuadrícula. Al pasar
  a dos columnas ocupa una fila completa; no intenta caber en la columna del icono.
- Fecha y botón se alinean por la base. En teléfono son dos filas de ancho completo.
  Horarios y botón Reservar se reorganizan al cambiar el espacio disponible.
- Se limita el ancho mínimo de componentes anidados, nombres y acciones. Las
  acciones de administración se apilan cuando no caben de forma útil.
- Todos los deportes, incluido «Todos», están en un único grupo desplazable
  encima del mapa. La fila no flota encima de sus marcadores o controles.
- La hoja de clubes tiene tres vistas: Mapa, Ambos y Lista. Mapa conserva solo
  una cabecera breve; Lista ocupa toda el área bajo los filtros y oculta/inactiva
  el mapa para teclado y accesibilidad. Ambos divide el área útil.
- La cabecera de la hoja se puede arrastrar. También funcionan los botones y
  las teclas arriba/abajo, Home/End y Escape. Se conserva la posición del mapa.
- La altura utiliza el viewport dinámico y los espacios seguros del dispositivo.
  Los controles mantienen área táctil; las transiciones respetan movimiento reducido.
- Si Leaflet no está disponible, se mantiene la consulta del API y la lista de clubes.
  No se convierte una caída de la biblioteca de mapa en una aplicación sin acceso.
- La suma y el símbolo de euro permanecen juntos en los precios de la lista.
- Se corrige el margen lateral heredado del login en anchos reducidos; sus textos,
  colores y formularios no se rediseñan. Sus inputs móviles usan 16 px.

`explorer-sheet.js` gestiona estados, tamaño, arrastre y foco; `home.js` coordina
la misma fuente JSON con lista y mapa. No se añaden librerías ni se modifica el
contrato de `/api/clubs` o las reglas de reserva que está revisando Joy.

## Entrada estática

El servicio gratuito de Laravel puede mostrar la pantalla de Render antes de
responder. Una página dentro de ese proceso todavía no puede ejecutarse mientras
el proceso duerme. La solución preparada es un Static Site separado, servido por
CDN, que muestra la escena existente y comprueba `/brand/ready` antes de abrir
Laravel. Su URL es el nuevo acceso inicial; la URL directa del Web Service conserva
el comportamiento de Render cuando está dormido.

El constructor existente ya copiaba CSS, JavaScript y fuente. En 05 se añaden
comprobaciones de plantilla/escena, escape del destino, configuración del enlace
sin JavaScript y viewport seguro. La geometría y la animación aprobadas se conservan.
Configuración de Render y comprobación final: `loading-site/README.md`.

## Revisión ejecutada al preparar el paquete

| Revisión | Resultado |
| --- | --- |
| Plantillas visuales de mapa, club con sesión, club invitado, reservas, login y registro | 54 combinaciones de página/ancho comprobadas en Chrome, con datos ficticios |
| Anchos revisados | 320, 390, 600, 720, 721, 834, 1024, 1280 y 1600 px |
| Desbordamiento horizontal y acciones fuera de las tarjetas | 0 incidencias en esos casos |
| Alineación fecha/botón en tablet/escritorio | Coincide la base de ambos controles; en teléfono se apilan |
| Estados Mapa/Lista y teclado | Cambio de estado, contenido oculto y mapa inactivo comprobados |
| Arrastrar cabecera hacia arriba | Cambia a Lista y finaliza el gesto sin dejar un arrastre activo |
| Biblioteca Leaflet ausente | La lista sigue mostrando los 6 clubes ficticios y permite abrir sus detalles |
| Pruebas Node de mapa y carga | 13 pruebas correctas |
| Constructor de entrada estática | Genera HTML y recursos sin directivas Blade pendientes |
| API y backend PHP nuevos | No se añaden en este paquete |

La revisión visual usa HTML representativo de las vistas y sus CSS/JS reales en
un stand independiente; no es una ejecución de Laravel ni sustituye la prueba en
el ASUS. La ausencia de PHP en ese entorno impide declarar una nueva ejecución
de los feature tests. La prueba en Safari/iPhone real sigue pendiente del equipo.

## Instalación y trabajo paralelo

Aplicar en una rama nueva de Denys basada en main. El instalador comprueba las
versiones y los finales de línea de Windows, y crea una copia de seguridad.
No incluye controladores, migraciones, `.env` ni SQLite. Los únicos archivos
compartidos con 04/04a son CHANGELOG.md y docs/registre-ia.md. Las nuevas entradas
de Denys se insertan en zonas distintas de las que modifica Joy para facilitar
la combinación mediante Git. Se ha comprobado la combinación de esas versiones.

No instalar este paquete desde Downloads en el Lenovo encima del trabajo de Joy:
el flujo de integración es commit, PR revisado y merge. Después, Joy actualiza
su rama desde Git y conserva sus cambios de backend.

En el ASUS, tras aplicar los archivos:

```powershell
php artisan view:clear
php artisan test --filter=MapPageTest
node --test tests/Frontend/map-data.test.mjs tests/Frontend/loading.test.mjs
node scripts/build-loading-site.mjs
```

Actualizar el navegador con Ctrl+F5. El Static Site se configura con Denys después
de publicar los archivos; todavía no se ha observado un despliegue de 05.
