# 24 · Corrección de autoguardado y cámara

Ajuste 15a-Denys · 26/11/2026. Base: main con el paquete 15 integrado/publicado.
El SHA del merge de 15 no se ha aportado todavía; se validan las huellas de los
archivos afectados. Último ancestro confirmado antes de 15: 046a20c.
Rama de corrección: fix/avatar-camara. Revisión: Joy.

## Error observado

Denys informó de que tanto la primera foto como una sustitución fallaban en la
versión publicada. La captura mostraba Illegal invocation. Antes habían pasado
187 pruebas PHP/1515 aserciones y 83 pruebas Node: esos resultados no demostraban
que el guardado real funcionara en un navegador. La aceptación visual/funcional de
15 no se considera completada por haber pasado las pruebas automáticas.

El objeto de temporizadores usaba { set: setTimeout, clear: clearTimeout } y luego
llamaba timers.set/timers.clear. Ese patrón cambia el receptor de los métodos
nativos. Los temporizadores de Node y los relojes simulados de las pruebas previas
permitían ese uso; el navegador exige su contexto global. Por eso fallaba el límite
de tiempo y su limpieza, antes de poder confirmar una subida.

Se sustituyen esas referencias por funciones que llaman explícitamente a
 globalThis.setTimeout y globalThis.clearTimeout. No se elimina el límite ni se
oculta el fallo para fingir un guardado. Las reglas de cola, versión, CSRF y destino
local permanecen iguales. Un error nativo inesperado recibe además un mensaje
comprensible en español, sin exponer Illegal invocation como texto de la interfaz.

Referencia primaria para el contexto de los métodos de temporización:
https://html.spec.whatwg.org/multipage/timers-and-user-prompts.html#timers

## Nueva comprobación de regresión

Se ejecuta el módulo de producción en un realm que exige receptor global para
setTimeout/clearTimeout y rechaza otro objeto con Illegal invocation. Se comprueba
un guardado inicial con los temporizadores predeterminados y el vencimiento de una
sustitución. Otro caso verifica el mensaje legible ante un fallo nativo inesperado.

Control negativo realizado: con el JavaScript publicado de 15, esos tres casos
fallan (20 pasan, 3 fallan en el archivo del avatar). Con 15a pasan los 23 de ese
archivo. La batería completa pasa 86 pruebas Node. Esto simula la comprobación del
receptor; no se presenta como una prueba de subida real en Chrome/Safari o Laravel.

## Ajuste visual solicitado

La referencia de Denys muestra una cámara verde dentro de un botón blanco con
sombra suave, sobre el borde del círculo. Se sustituye el plus amarillo por esa
cámara, conservando el mismo botón accesible y el gesto que abre el selector nativo.
Sin foto, el círculo muestra una silueta gris suave; las iniciales de la cabecera
siguen funcionando. La vista previa se superpone sin que la silueta la tape.

Se mantienen los tamaños adaptados y el área de pulsación del avatar. La cámara
queda dentro del botón, por lo que pulsar la foto o la cámara abre el selector.
Los errores usan un aviso discreto en español. No aparecen Guardar foto o Quitar
foto, ni se reintroduce un selector visible debajo de la tarjeta.

## Alcance y límites

Solo se modifica el JS de autoguardado, CSS/vista del perfil y sus pruebas, más
esta guía. No se toca AvatarController, almacenamiento, autenticación, límites del
archivo, preparador de miniaturas, rutas o base de datos. No hay migraciones ni
dependencias nuevas. El import del preparador mantiene su huella porque no cambió.

Se adaptan las aserciones visuales de AvatarUiTest; el total PHP sigue en 187 casos.
Ejecutar AvatarUiTest, ProfileRulesTest y FrontendScreensTest: 17 casos declarados
para la vista del perfil, sus formularios y las pantallas que comparten avatares.
PHP no está disponible en la preparación: esa ejecución queda pendiente en ASUS.
La revisión visual y la subida real también quedan pendientes con Denys.

El instalador admite CRLF, compara los originales antes de escribir, guarda backup
fuera del repositorio y permite reinstalar. Se revisan huellas, ZIP, SVG, sintaxis JS,
finales de archivo y git diff --cached --check con todos los archivos del ajuste.

## Instalación y aceptación

1. Actualizar main limpio y crear fix/avatar-camara. Instalar sobre 15 ya integrado.
2. php artisan view:clear y las pruebas PHP indicadas; ejecutar toda la batería Node.
3. Probar primera foto y sustitución: Preparando/Guardando/Foto guardada, perfil y
   cabecera actualizados; recargar y confirmar persistencia.
4. Probar cámara y círculo, cancelar selector, elegir dos fotos y errores de archivo.
   Comprobar que no aparece Illegal invocation ni se anuncia éxito sin respuesta.
5. Revisar foto existente y estado sin foto, escritorio y teléfono. El marcador de
   estado no sustituye la comprobación de que la imagen se guardó realmente.

Tras pruebas: registrar los resultados reales, commit/PR, revisión de Joy, merge,
Live del nuevo commit y comprobación publicada. El desarrollo general sigue abierto.

## Registro IA

- Autor/petición: Denys, «ni de primera vez ni al sustituir ... no se upload» y
  petición visual de cámara con referencia.
- Fecha/herramienta: 26/11/2026, ChatGPT (OpenAI).
- Corrección: contexto de temporizadores y cámara, con regresión que detecta el
  fallo del paquete 15. No se atribuye al backend un error provocado en el cliente.
- Evidencia: Node 86 correctas; PHP, subida real y aceptación visual pendientes.
- Commit/PR del ajuste: pendientes.

## Despliegue de corrección — Denys — 26/11/2026
- La versión publicada falla al subir o sustituir fotos: Illegal invocation.
- Ajuste del contexto de temporizadores y nuevo botón de cámara.
- Node en la preparación: 86 pruebas correctas.
- Pruebas PHP del ajuste y carga real pendientes tras el despliegue.
