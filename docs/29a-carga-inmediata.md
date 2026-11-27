# Ajuste 20a — mostrar la carga antes de esperar al servidor

Responsable: Denys. Fecha: 27/11/2026. Rama: fix/carga-inmediata.
Base: main con el paquete 20 integrado. Sin cambios de plan o variables de Render.

## Resultado observado y corrección

Denys confirma que la entrada y el retorno funcionan tras el reposo. Detecta una
espera de 4–5 segundos antes de ver la escena al actualizar o navegar. El paquete
20 esperaba el probe de hasta cuatro segundos antes de mostrar la entrada.

Este ajuste cambia el orden: una navegación GET protegida recibe primero una
escena pública local. La comprobación del servidor ocurre con la pelota y la
pista ya visibles. No se promete un tiempo absoluto de pintura del navegador;
se elimina la espera de red previa que imponía el código anterior.

La escena aparecerá también en las navegaciones GET habituales con el servidor
activo. No se espera a detectar el sueño para dibujarla. La aplicación real se
abre cuando /brand/ready devuelve la imagen válida, sin duración de vídeo fija.

## Escena autónoma

scripts/build-navigation-shell.mjs construye public/brand/navigation-shell.html
con el SVG, CSS, JavaScript y fuente ya existentes. La escena no depende de
descargar CSS, scripts o fuente del servidor que está durmiendo. No renderiza
Laravel ni lee .env, sesiones, usuarios, reservas o datos de formularios.

El builder verifica que el fragmento sea HTML estático, normaliza los finales de
línea y calcula SHA-256. Actualiza la misma huella dentro del worker. Docker ejecuta
el builder en su etapa Node y copia juntos la escena y el worker a la imagen PHP.
La construcción estática de Render conserva su comando actual y comparte el
loading.js actualizado.

## Caché permitido y protección

A diferencia del paquete 20, existe un caché explícito, pero solo para este único
HTML público, idéntico para todas las cuentas. No se almacena ninguna página del
perfil, clubes, administración, pagos, formularios ni respuestas de API.

La instalación descarga únicamente /brand/navigation-shell.html, sin cookies,
con recarga de caché y sin seguir redirecciones. Comprueba tipo y huella antes
de guardar. Un error de Render o HTML de otra página no coincide y no se guarda.
Se eliminan las cabeceras originales, incluidas posibles cookies. La escena se
entrega con no-store y una política de recursos limitada a su contenido público.

Al activar una nueva versión solo se retiran cachés con el prefijo propio
rp-public-loader-. No se tocan otros cachés del origen. Si no hay escena disponible,
se usa la entrada estática como respaldo, sin esperar el probe de cuatro segundos.

## Continuación sin bucles

El HTML autónomo usa la URL real del documento para conservar ruta, filtros y
ancla. Tras ready añade rp_ready=1, indicador que la aplicación ya utilizaba.
El worker deja pasar esa lectura una sola vez y el layout limpia el indicador
visible. La respuesta real conserva su autorización, estado HTTP y cookies.
Los errores propios de Laravel se muestran en vez de volver a iniciar la escena.

El enlace manual Abrir la aplicación también evita otra escena. Abrir directamente
el archivo HTML público lleva a la portada en lugar de redirigirse a sí mismo.
La entrada estática con #next conserva su funcionamiento y omite una segunda escena.

El módulo registra la versión ?loader=20a. Mientras siga controlando el worker
anterior, los enlaces usan la entrada estática de respaldo. La versión nueva debe
instalar su escena antes de activarse. Tras publicar hay que abrir la aplicación
una vez y dejar que termine esa actualización.

POST, PATCH y DELETE siguen fuera del worker y no se repiten. El cambio está
limitado a navegaciones GET; no sustituye las respuestas de operaciones de escritura.

## Preparación y límites

- Batería JavaScript disponible: 190 pruebas correctas, sobre la base anterior
  sin el paquete 19 aún no confirmado. El total varía si Joy lo incorpora.
- 41 casos de gateway, loading y builder correctos.
- Se comprueban entrega sin red previa, continuación única, retorno de ruta y
  ancla, exclusión de HTML privado, huella, invalidación, limpieza selectiva,
  actualización desde el worker anterior y reproducibilidad Windows/Linux.
- Se comprueba el builder con los archivos disponibles en la etapa Node de Docker.
- La construcción de la entrada estática sigue pasando.
- Instalación, copia de seguridad, CRLF, reinstalación, rechazo de versiones
  inesperadas y restauración ante fallo de copia comprobados.
- git diff --cached --check sin errores en una copia de verificación.
- No se ha ejecutado PHP ni una construcción Docker completa en la preparación.
- Pendiente: despliegue real y medición visual de aparición en ordenador/teléfono.

Sigue siendo necesario usar la entrada principal en una primera visita al Web
Service dormido sin worker. Si el navegador prohíbe o borra la instalación, no se
promete esa protección. El caché contiene la escena, no un modo offline del proyecto:
para consultar datos y comprobar ready continúa siendo necesaria la conexión.

## Comprobación local

```powershell
node scripts/build-navigation-shell.mjs
if ($LASTEXITCODE -eq 0) { node scripts/build-loading-site.mjs }
```

```powershell
$pruebas20a = @(Get-ChildItem -LiteralPath 'tests/Frontend' -Filter '*.test.mjs' -File | Select-Object -ExpandProperty FullName)
node --test $pruebas20a
```

La escena generada y su huella forman parte del commit. El resultado de
loading-site/dist sigue siendo derivado y no se añade al repositorio.

## Publicación y revisión

1. Revisar y hacer merge. Esperar Live en los dos servicios existentes. El build
   de Docker debe mostrar Escena pública preparada antes de compilar Vite.
2. Abrir la entrada principal y esperar al sitio para actualizar el worker. En
   Application → Service Workers debe aparecer la URL con loader=20a.
3. En Application → Cache Storage solo el caché de este cargador debe contener una
   escena pública; no debe incluir páginas de cuenta ni respuestas de formularios.
4. Actualizar o navegar: la escena debe verse antes de esperar al servidor. Tras
   ready se abre la página solicitada, con su fecha, filtros y ancla.
5. Repetir después de la inactividad normal, sin otras pestañas consultando el sitio.
   Comprobar ordenador y teléfono. La escena no debe esperar los cuatro segundos
   de la versión anterior para aparecer.
6. Probar entrar/salir, volver atrás y una reserva con pago simulado. Los envíos
   no deben duplicarse. La navegación desde la entrada estática no debe repetir
   dos escenas consecutivas.

## Reversión

Revertir el ajuste conserva los datos del proyecto. Para volver completamente al
worker anterior puede ser necesario retirarlo en Application → Service Workers,
solo para rp-gateway-sw.js, y volver a abrir el sitio. No borrar otros datos de
la cuenta para realizar esta operación. El caché público del cargador se puede
retirar por su prefijo sin tocar otros cachés.

Referencia del patrón de contenido público local:
https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API/Using_Service_Workers

## Validación local — Denys — 27/11/2026
- Base confirmada: main 0f39b15.
- Escena pública generada: 37318 bytes.
- Entrada estática generada correctamente.
- JavaScript: 190 pruebas correctas, 0 fallos.
- git diff --check sin errores.
- Pendientes: build de Render y comprobación visual de carga inmediata.
