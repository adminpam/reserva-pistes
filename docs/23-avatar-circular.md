# 23 · Avatar circular y autoguardado · Denys

Paquete 15-Denys · 26/11/2026 · base main 046a20c, merge PR #28.
Rama: feat/avatar-circular. Instalación: C:\Dev\ProjIA\reserva-pistes.

## Base acreditada y reparto

Joy pasó 184 pruebas PHP y 1491 aserciones, en 15,82 s, incluidos los once casos
nuevos del contrato JSON del avatar. Commit f0f9eb4, revisión de Denys y merge
PR #28, main 046a20c confirmado. Esos resultados no acreditan el paquete 15.

El backend de 14 se conserva. Este bloque conecta el círculo, la selección de
archivo y la actualización del perfil/cabecera. Joy revisa este PR antes del merge.
El desarrollo continúa; la presentación y el cierre final siguen pendientes de
las instrucciones de Denys.

## Interacción

La foto o las iniciales forman un botón circular con un plus pequeño. Pulsarlo
abre inmediatamente el selector nativo del dispositivo. Elegir un JPG/PNG/WebP
prepara la miniatura y la envía automáticamente: no hay selector visible debajo,
Guardar foto o Quitar foto. Pulsar otra vez permite sustituirla. Cancelar el diálogo
no modifica la foto ni interrumpe una subida que ya estaba en marcha.

El círculo sigue disponible mientras prepara/guarda, para elegir otra imagen. Un
indicador y texto breve explican preparación, guardado, éxito o error. La vista
previa local no significa que el servidor haya confirmado. La cabecera se actualiza
con una respuesta válida, sin recargar ni enviar los formularios de nombre/niveles
u otras contraseñas. Los avatares de otros usuarios no se modifican.

Los errores muestran el último estado confirmado y permiten elegir otra foto.
Un fallo de red puede ocurrir después de que PHP haya guardado: el mensaje indica
que no se pudo confirmar, sin prometer que el servidor no cambió. Si falla la
lectura de la nueva imagen se muestran iniciales; un error antiguo no borra un
avatar más reciente.

## Contrato y orden de las peticiones

Se usa POST /profile/avatar con FormData, Accept JSON, CSRF y credenciales del
mismo origen. No se fija Content-Type a mano. Se envían solo token, archivo
preparado y expected_avatar_version. La versión inicial procede de la vista y se
actualiza con el resultado del servidor, también ante un conflicto.

Los envíos van en serie. Si cambia la selección mientras se prepara, se descarta
la preparación anterior. Si una petición ya salió, se espera su resultado y se
envía después únicamente la selección pendiente más reciente. Las respuestas
anteriores pueden actualizar el estado confirmado del servidor, pero no sustituyen
la vista previa ni el mensaje de una selección posterior.

El 409 actualiza los metadatos confirmados y permite un único reintento de la
selección vigente. No se reintentan fotos ya sustituidas ni se crea un bucle. Si el
conflicto continúa se informa. El guardado usa siempre la precondición de versión
de 14, incluida la referencia vacía cuando no había foto. Los formularios antiguos
que no envían esa precondición mantienen el contrato de compatibilidad de 14.

Cada petición tiene límite de veinte segundos; si vence se aborta fetch y se
informa. Abortar el navegador no garantiza cancelar PHP; la versión protege frente
a escrituras atrasadas. La preparación tiene límite general de 35 s, con límites
de quince segundos en la lectura y en la codificación de la miniatura. El caso de
un codificador que no devuelve callback libera su recurso por tiempo de espera.

Al abandonar la página sin bfcache se cancelan tareas, listeners y vistas previas.
Volver desde bfcache mantiene el estado de la página; no se ofrece sincronización
instantánea con cambios de foto en otras pestañas. En la siguiente subida la
precondición detecta diferencias con el servidor. No se simula una prueba de
concurrencia real en PostgreSQL mediante estos tests de cliente.

## Validación, caché y accesibilidad

Se conserva el preparador de miniatura centrada JPEG de 384 px, límite previo
10 MB y validación del servidor. SVG y formatos distintos se rechazan antes de
decodificar. Un selector del teléfono que entregue HEIC debe usar JPG/PNG/WebP;
no se promete conversión de formatos no admitidos. Las URLs blob se revocan al
sustituir, completar, fallar o desmontar la vista previa.

La respuesta solo se aplica si contiene url/version/iniciales coherentes, origen
local, ruta de avatar del propio usuario y query v que coincide con la versión.
Las iniciales y los mensajes se insertan como texto, sin HTML. No se guardan fotos
o tokens en localStorage. Los recursos JS/CSS propios y el import del preparador
llevan huellas actualizadas. El lector de bytes y sus cabeceras no se modifican.

El botón tiene nombre accesible y foco visible, el estado usa aria-live y la tarea
marca aria-busy. El spinner respeta movimiento reducido. El archivo permanece
oculto visualmente; el selector se abre dentro del gesto del usuario, sin await
previo. Sin JavaScript se explica que hace falta activarlo para el autoguardado;
los formularios de nombre/niveles y contraseña conservan el funcionamiento habitual.

## Comprobaciones de preparación

83 pruebas Node correctas: 62 anteriores, veinte del autoguardado y una adicional
del codificador. Cubren selección última, cola, cancelación, conflictos limitados,
errores, sesión, tiempos de espera, URL, transporte multipart/CSRF, actualización
de perfil/cabecera, liberación de recursos y aislamiento de otros usuarios.
Los tests de DOM son simulaciones, no comprobaciones visuales de un navegador.

Tres casos PHP nuevos verifican controles del círculo, versión/enlaces de perfil
y cabecera, y separación de datos sensibles de los demás formularios. Se adapta
ProfileRulesTest al nuevo contrato sin Guardar foto. Total declarado: 187 casos
PHP. PHP/Composer no están disponibles en la preparación; la ejecución queda
pendiente en ASUS. El aspecto en escritorio/teléfono también debe comprobarse.

El instalador se revisa con CRLF, backup, reinstalación, rechazo de cambios locales
y paquete incompleto. git diff --check se comprueba con el parche preparado,
incluidos los archivos nuevos y el final de archivo. Eso no ejecuta Laravel.

## Instalación y aceptación

Instalar en feat/avatar-circular desde 046a20c limpio. Ejecutar config:clear,
view:clear, php artisan test y las pruebas Node. No hay migraciones, dependencias
nuevas o cambios en .env. No ejecutar fresh/seeders completos en una base usada.

Con Denys:
1. Abrir perfil, pulsar el círculo/plus y elegir una foto: se guarda sin botón.
2. Comprobar nueva foto en círculo/cabecera y persistencia después de recargar.
3. Elegir otra foto, cancelar el selector, elegir dos con rapidez: debe terminar
   guardada la última elegida y no reaparecer una preparación anterior.
4. Probar un archivo no admitido o demasiado grande: mensaje y foto conservada.
   Con conexión interrumpida, no dejar el círculo esperando indefinidamente.
5. Probar nombre/niveles y contraseñas por separado. Subir la foto no debe enviar
   esos datos ni borrar lo que se estaba escribiendo en otros formularios.
6. Revisar escritorio y teléfono, incluidos apertura del selector, foco, plus,
   indicador, textos y ausencia de los controles anteriores.

Registrar resultados reales, commit y PR; revisión de Joy, merge y comprobación
publicada por HTTPS. Continúan pendientes validaciones uniformes, sonido móvil,
carga tras reposo, funciones nuevas aceptadas e intro independiente del cargador.

## Registro IA

- Autor/petición: Denys, «pequeño circuito con plus ... lo guarda automaticamente».
- Fecha/herramienta: 26/11/2026, ChatGPT (OpenAI).
- Resultado: círculo con plus, cola de subida y proyección de metadatos de 14.
- Validación: 83 casos Node correctos; PHP y revisión visual pendientes en ASUS.
- Commit/PR del paquete 15: pendientes.

## Validación previa a publicación — Denys — 26/11/2026
- PHP: 187 pruebas y 1515 aserciones correctas, en 11,15 s.
- JavaScript: 83 pruebas correctas.
- Comprobación visual y recorrido manual pendientes en la versión publicada.
