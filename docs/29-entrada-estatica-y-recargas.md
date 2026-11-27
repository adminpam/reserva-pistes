# Paquete 20 — entrada estática y recargas

Responsable: Denys. Fecha: 27/11/2026. Rama: feat/carga-estatica.
Base: main con los ajustes de sonido 18c integrados. El paquete 19 de Joy puede
seguir en revisión; este cambio no modifica sus rutas, filtros ni buscador.

## Decisión y alcance

Denys elige mantener todo gratuito y usar como entrada principal:
https://reserva-pistes-inicio.onrender.com/.

Laravel conserva https://reserva-pistes.onrender.com/. El primer acceso directo a
ese Web Service dormido puede mostrar la pantalla de Render. Un código servido
por Laravel no puede sustituir una respuesta que Render entrega antes de arrancar
Laravel. No se cambia el dominio, el plan ni se crean servicios de pago.

La escena de pelota, pista, colores y barra sigue siendo la existente.

## Observación real previa

El 27/11/2026 se abrió la entrada estática en un navegador: mostró la escena
propia y después abrió Laravel con 41 clubes. Esta observación corresponde a la
entrada existente. No demuestra que la nueva protección ya esté desplegada.

## Funcionamiento añadido

1. La entrada recibe opcionalmente #next con una ruta local, sus filtros y ancla.
   El fragmento no se envía al servidor estático. No permite destinos externos,
   protocol-relative, barras inversas o caracteres de control.
2. Sigue esperando una imagen 2×2 válida de /brand/ready antes de abrir Laravel.
   Una página HTML de Render no cuenta como aplicación preparada. Se conservan
   el plazo de espera, el reintento y el modo de movimiento reducido.
3. Si se vuelve a la entrada desde la caché del navegador, se reinicia la prueba
   que pagehide había cancelado.
4. Una visita al origen real registra rp-gateway-sw.js, cuando el navegador permite
   service workers. No se reemplaza otro worker existente de la aplicación.
5. Las navegaciones GET conocidas bajo ese worker, incluidas recargas, comprueban
   la disponibilidad antes de pedir la página. El probe usa credentials:omit,
   cache:no-store, redirect:error y un máximo de cuatro segundos. Si no recibe el
   SVG esperado, redirige a la entrada con el destino original.
6. Si responde correctamente, se transmite una sola vez la petición original y su
   respuesta. Los errores de Laravel se conservan; no se genera un bucle intentando
   ocultar un error propio de la aplicación.
7. Sin un worker propio activo, los clics normales y búsquedas GET usan la entrada
   como respaldo. Se respetan modificadores, enlaces externos, pestañas nuevas,
   descargas y anclas del documento actual.

No se utiliza CacheStorage, no se almacena HTML de cuentas y no se guardan cuerpos
de formularios. POST, PATCH y DELETE no se interceptan ni reintentan. El guardián
no convierte una reserva o un pago en una operación repetida. Las API y descargas
continúan con su comportamiento existente.

## Límites del alojamiento gratuito

- Compartir siempre la entrada estática para garantizar la escena en el primer
  acceso. Una primera visita directa al Web Service sin worker sigue limitada.
- Navegadores que no admitan el worker disponen del respaldo para enlaces y
  formularios GET; una recarga directa al servidor dormido puede mostrar Render.
- La comprobación previa no es un proxy permanente: una caída entre el probe y
  la lectura posterior sigue siendo posible. Los errores de esa lectura se muestran
  normalmente; una conexión fallida ofrece la entrada estática como continuación.
- No es una aplicación offline. Si el dispositivo no tiene red, tampoco podrá
  descargar la entrada estática.
- No modifica el resultado de envíos POST ni respuestas de API: no se promete
  sustituir todas las posibles pantallas de error del alojamiento.
- No hay cron, keep-alive periódico ni solicitudes para impedir el sueño del servidor.

## Preparación comprobada

- 28 casos Node de carga y navegación correctos: 11 de loading y 17 de gateway.
- Batería JavaScript completa disponible: 177 pruebas correctas, sobre la base sin
  el paquete 19 aún no confirmado. Si se incorpora ese paquete, cambia el total.
- node scripts/build-loading-site.mjs genera la entrada con la escena existente
  y el loading.js actualizado. Sin dependencias nuevas ni reconstrucción de Vite.
- Instalador con CRLF, copia de seguridad, reinstalación, rechazo atómico de cambios
  locales o paquete incompleto y restauración ante fallo de copia comprobados.
- git diff --cached --check sin errores en una copia de verificación.
- PHP no ejecutado en el entorno de preparación; se usa la batería pertinente en
  el equipo de Denys para verificar la nueva etiqueta de script del layout.

Las pruebas Node simulan navegador y red. La instalación del worker y el arranque
real desde reposo en teléfono siguen pendientes hasta desplegar este paquete.

## Archivos y comprobación local

Los cambios se limitan a loading.js, el nuevo módulo de navegación, su política,
el worker, una etiqueta del layout, pruebas y documentación de la entrada.
No se modifica el flujo de pagos, los controladores ni el esquema de la base.

```powershell
php artisan view:clear
if ($LASTEXITCODE -eq 0) {
    php artisan test --filter="BrandReadyTest|FrontendAssetTest|FrontendScreensTest"
}
```

```powershell
node scripts/build-loading-site.mjs
$pruebas20 = @(Get-ChildItem -LiteralPath 'tests/Frontend' -Filter '*.test.mjs' -File | Select-Object -ExpandProperty FullName)
node --test $pruebas20
```

El registro se limita al origen público real. En localhost se comprueba el código
con Node y el renderizado PHP; no se instala un worker que envíe el desarrollo a
producción.

## Publicación y revisión de Denys

1. Tras pruebas y revisión, hacer merge. Esperar a que estén Live tanto el Web
   Service como el Static Site existentes. Ambos deben usar main con este commit.
   No hay que crear un tercer servicio ni cambiar variables de Render.
2. Abrir la entrada principal en una ventana privada. Comprobar la escena y el
   paso al sitio. El contador del mapa debe proceder de la base real.
3. Abrir la entrada con #next=%2Fprofile y, con sesión, comprobar que llega al
   perfil conservando la cuenta. Sin sesión, debe llevar al acceso habitual.
4. En Chrome, Application → Service Workers debe mostrar rp-gateway-sw.js en el
   origen de Laravel. No debe aparecer un caché de páginas privadas.
5. Después de una visita y del tiempo normal de inactividad de Render, abrir
   otra vez o recargar una página del sitio. Con el worker activo debe aparecer la
   entrada propia y volver a la ruta al terminar el arranque.
6. Repetir en teléfono. Si el navegador no permite el worker, comprobar el respaldo
   al pulsar enlaces. Documentar esa diferencia sin afirmar protección universal.
7. Probar una búsqueda GET con varios deportes, un ancla de pista, entrar/salir y
   una reserva con pago simulado. No deben duplicarse envíos ni perderse filtros.
8. El README del repositorio debe destacar el enlace estático como entrada pública,
   conservando el del Web Service como dirección técnica. Actualizar ese bloque
   sobre el archivo actual para no sobrescribir las validaciones de Joy.

## Reversión

Revertir este commit conserva la base de datos y el servicio existentes. Después
hay que retirar el worker instalado: en Application → Service Workers elegir
Unregister solo para rp-gateway-sw.js. Quitar su etiqueta del layout por sí solo
no borra un worker que el navegador ya tenía instalado. Volver a compartir la
entrada estática sigue siendo válido incluso sin la protección de recargas.

## Referencias oficiales revisadas

- https://render.com/docs/free
- https://render.com/docs/static-sites
- https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerContainer/register
- https://developer.mozilla.org/en-US/docs/Web/API/FetchEvent/request

## Validación local — Denys — 27/11/2026
- Base confirmada: main c88c792.
- Entrada estática generada correctamente.
- PHP: 9 pruebas y 66 aserciones correctas.
- JavaScript: 177 pruebas correctas.
- git diff --check sin errores.
- Pendiente: despliegue de ambos servicios y comprobación real tras el reposo.
