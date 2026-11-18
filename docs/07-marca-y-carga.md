# 07 · Marca y pantalla de carga

Paquete preparado el 17/11/2026 con asistencia de ChatGPT y ajustado tras la
revisión visual de Denys. En su ASUS se completaron la compilación Vite, la
limpieza de vistas y `BrandReadyTest` (1 test, 5 aserciones). La publicación de la
entrada estática y la revisión visual del ajuste 01b siguen pendientes.

## Qué cambia

- El símbolo de la cabecera y los paneles de acceso usan el PNG original aportado
  por el equipo. El archivo se conserva sin modificar; CSS muestra la zona del símbolo.
- La carga muestra una pista ámbar en perspectiva, pelota luminosa con bote continuo,
  nombre de marca y barra de espera. La pista se ha trazado en SVG según la referencia:
  no es una copia píxel a píxel de la textura de la imagen generada.
- Se elimina la antigua animación de la letra R y su cierre fijo a los 2,4 segundos.
- La barra es indeterminada: expresa espera, no un porcentaje de arranque inventado.
- Con `prefers-reduced-motion` no hay bote ni desplazamiento de la barra.
- El resto de las páginas mantiene el diseño del código recibido.

## Cómo funciona

`partials/intro.blade.php` contiene la escena común. `public/brand/loading.css`
define el aspecto y `loading.js` coordina la espera. Los archivos del cargador se
sirven directamente para que no dependan de la descarga del JavaScript de Vite.

Dentro de Laravel, se espera al documento y a las fuentes. La pantalla aparece
una vez por sesión del navegador, con un límite de 12 segundos para recursos
externos lentos. Un respaldo independiente de 15 segundos impide bloquear la
página si falla el archivo JavaScript del cargador. Sin JavaScript no se oculta
el contenido.

Para revisar la animación sin que se cierre, abrir `/brand/preview.html` en la
aplicación local. Esta página es una vista previa permanente, no una espera de servidor.

## Espera de Render

Una página servida por el mismo servicio dormido no puede mostrarse antes de que
ese servicio responda. Render muestra su propia espera al acceder directamente
a https://reserva-pistes.onrender.com/ cuando el servicio está dormido.

El paquete incluye una entrada independiente para un **Static Site** gratuito de
Render. No cambia la autenticación ni mueve Laravel a otro servicio. Al abrir esa
entrada se ve inmediatamente el cargador y se solicita `/brand/ready` al Laravel
existente. El controlador devuelve una imagen SVG de 2 × 2 cuando la petición
llega a Laravel. No inicia sesiones, no crea cookies y no envía datos de usuarios.
La respuesta no se almacena en caché. Una página HTML intermedia de Render no
se puede decodificar como la imagen esperada.

Solo se comprueba el arranque HTTP de Laravel: no se certifica el estado de todas
las tablas ni de los servicios externos. No se usa `fetch` con `no-cors`, que
impediría distinguir una respuesta válida de una página intermedia.

Si todavía no responde, se vuelve a comprobar tras 3 segundos, con un máximo de
15 segundos por intento y 2 minutos por visita. Después aparecen «Reintentar» y
«Abrir la aplicación». No hay peticiones periódicas para mantener Render despierto.
Al estar disponible, se abre la aplicación con una señal temporal que evita
mostrar dos veces el cargador. No se pasan credenciales entre dominios.

### Publicar la entrada estática (después de publicar este paquete en Laravel)

1. En Render, crear un **Static Site** conectado al mismo repositorio y a `main`.
2. Dejar el directorio raíz del servicio en la raíz del repositorio.
3. Build Command: `node scripts/build-loading-site.mjs`.
4. Publish Directory: `loading-site/dist`.
5. Variable de entorno: `SKIP_INSTALL_DEPS=true` (el generador solo usa Node).
6. Compartir la URL que asigne Render a la entrada para ver el cargador al despertar
   Laravel. La URL antigua seguirá funcionando, con la espera propia de Render.

La entrada todavía no se ha creado ni publicado. No se inventa una URL para ella.
El destino se configura en `loading-site/config.json`; no se acepta un destino
desde los parámetros de la URL del visitante.

## Archivos principales y explicación para la defensa

| Archivo | Responsabilidad |
| --- | --- |
| `partials/brand-mark.blade.php` | Mostrar el símbolo del PNG original mediante una ventana CSS. |
| `partials/intro.blade.php` | Estructura del cargador y geometría SVG de la pista. |
| `public/brand/loading.css` | Colores, proporciones, adaptación móvil y movimiento reducido. |
| `public/brand/loading.js` | Esperar al documento o comprobar la respuesta de Laravel. |
| `BrandReadyController.php` | Devolver una imagen pequeña y no cacheable como señal de arranque. |
| `scripts/build-loading-site.mjs` | Construir una entrada estática con los mismos archivos y la misma escena. |

## Validación

Comprobaciones realizadas al preparar el paquete:

- Sintaxis de los archivos JavaScript y generación del sitio estático.
- Lógica del cargador mediante dobles de DOM y temporizadores: espera del documento,
  fallo de imagen, dimensiones incorrectas, respuesta válida, espera agotada,
  reintento, almacenamiento no disponible y movimiento reducido.
- Instalador sobre una copia: instalación, segunda ejecución sin cambios y rechazo
  de archivos editados desde el ZIP de origen. Se verifica la copia de seguridad.
- El PNG incluido coincide byte a byte con el original facilitado.

Estas comprobaciones no sustituyen la revisión visual en navegador ni una ejecución
real de PHP. Este entorno de preparación no dispone de PHP ni Composer; no se
ha ejecutado PHPUnit, Laravel ni la compilación Vite del proyecto completo.

En el ASUS de Denys, desde la carpeta del proyecto, después de instalar:

```powershell
npm run build
php artisan view:clear
php artisan test --filter=BrandReadyTest
node --test tests/Frontend/loading.test.mjs
node scripts/build-loading-site.mjs
```

Revisar `/brand/preview.html` en escritorio, tablet y móvil; probar también
movimiento reducido. En la aplicación, comprobar acceso, mapa y un formulario.
Una vez publicado el paquete de Laravel y la entrada estática, comprobar la
entrada con Laravel dormido y con Laravel activo. Anotar los resultados reales
en `docs/06-proves.md` y enlazar el PR en `docs/registre-ia.md`.

## Fuentes oficiales consultadas

- https://render.com/docs/free
- https://render.com/docs/static-sites
- https://laravel.com/framework/docs/12.x/middleware
- https://laravel.com/framework/docs/12.x/deployment
- https://learn.microsoft.com/powershell/module/microsoft.powershell.archive/expand-archive

## Documentación anterior

`docs/05-desplegament.md`, la entrada 15 del registro y el bloque v1.0 del changelog
todavía incluyen el plan anterior de Laravel Cloud. La aplicación observada está
en Render. Antes de la entrega, contrastar la configuración real de la base de
datos y actualizar esos apartados sin dar por completadas tareas pendientes.

## Ajuste 01b · 17/11/2026

El nombre visible del cargador es «Reserva Pistes», con separación explícita
entre palabras. El segmento entra desde fuera del borde izquierdo, recorre la
barra y sale completamente por el derecho antes de reiniciar. No vuelve hacia
atrás. Su desplazamiento va de −100% a 312,5% de su propia anchura, que es el 32%
de la barra; por eso ambos extremos quedan totalmente fuera del área visible.

La geometría de la pista y la animación de la pelota aprobadas se conservan.
En teléfono cambia el orden y la estructura: nombre en la zona superior, pista
central y barra en la zona inferior. En horizontal, nombre y barra comparten la
primera fila y la pista ocupa la segunda. Tablet tiene reglas propias de ancho
y separación. Las demás páginas aún están pendientes de su rediseño; estos
cambios no significan que todo el sitio ya tenga una composición móvil nueva.

Revisión manual del ajuste: escritorio, tablet, teléfono vertical y horizontal,
con atención a la salida completa del segmento, el espacio del nombre y los
mensajes de error. Volver a generar `loading-site/dist` antes de publicar.
