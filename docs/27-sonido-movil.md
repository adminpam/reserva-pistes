# 27 · Sonidos opcionales en teléfono y ordenador

Paquete 18-Joy · 27/11/2026. Base: main con el ajuste 17b integrado y aceptado.
Último SHA anterior confirmado: 864ea63. El SHA del merge de 17b no se ha aportado;
el instalador comprueba las huellas de los archivos que modifica.
Rama: fix/sonido-movil. Corresponde al punto 2 de Joy.

## Cierre del punto 1

Joy confirmó que la validación, el correo con extensión, el ojo y los avisos ya
quedan correctos en móvil y ordenador. Los resultados aportados para 17b fueron
212 pruebas PHP/1708 aserciones y 112 pruebas Node correctas. Esa aceptación cierra
el punto 1 y permite continuar con el sonido, sin reabrir los formularios.

## Problema del código anterior

El botón cambiaba su estado y guardaba la preferencia antes de confirmar que el
AudioContext pudiera reproducir. Se llamaba resume() sin esperar su resultado y
se programaban las notas inmediatamente. Los fallos se ocultaban. Además, un
mensaje del servidor podía intentar crear el contexto durante la carga, sin gesto.

Ahora el sonido se monta desde public/ui/sounds.js, con versión de contenido en el
layout. Se retira el motor anterior de resources/js/app.js para evitar dos motores
sobre el mismo botón. El tiempo y los detalles de los formularios se conservan.

## Activación y estados

La función produce tonos breves de interfaz; no es lectura por voz ni solicita
micrófono. No descarga archivos de audio ni utiliza servicios externos.

- Se mantiene apagada por defecto. Leer rp-sonido=on no crea audio al cargar.
- Al tocar el botón se crea el contexto y se llama resume() dentro del gesto,
  antes de cualquier espera. Un frame silencioso prepara también motores antiguos.
- El botón solo indica activación después de confirmar el contexto en ejecución.
  La confirmación reproduce dos notas breves y muestra Sonidos activados.
- Mientras espera, otro toque permite cancelar. La espera termina en 2,5 segundos;
  un fallo deja el botón apagado, con una indicación de reintento en español.
- Si WebAudio no existe, se ocultan botón y separador. Sin el módulo cargado, la
  plantilla tampoco deja un botón visible sin función.
- La preferencia guardada se conserva entre páginas, pero cada documento vuelve
  a habilitar el contexto mediante un gesto. No hay sonido automático al cargar.
- Una interrupción, ocultar la página o cambiar de aplicación silencia y cancela
  las esperas. El siguiente gesto permite reanudar si el usuario lo había activado.
- Apagar silencia la ganancia y detiene/desconecta los nodos inmediatamente.
  Los resultados atrasados no vuelven a activar una decisión anterior.
- Reactivar mientras suspend() sigue pendiente solicita su propio resume() y
  espera su resultado, para respetar la última decisión.

Los clics y algunas teclas reciben tonos cortos con límite de frecuencia y de
nodos simultáneos. Los tonos de hover solo se aplican al ratón, no al toque. No
se generan tonos de tecleo en contraseñas o formularios de pago. Las confirmaciones,
validación y operaciones normales no esperan al sonido ni dependen de él.

Se usa un solo montaje por documento. Los temporizadores llaman a globalThis con
su receptor correcto, para evitar la regresión Illegal invocation ya observada en
la foto. Las promesas rechazadas se gestionan; no se muestran errores técnicos.

## Compilación necesaria

Este paquete modifica resources/js/app.js para retirar el motor anterior. En el
Lenovo hay que ejecutar npm run build antes de comprobar la interfaz local.
No se debe evaluar una mezcla del Vite anterior y el módulo nuevo.

Dockerfile ya compila los assets en su primera etapa con npm ci y npm run build.
El despliegue automático después del merge incorpora ambos cambios. No requiere
entrar en Render, cambiar variables, migrar o ejecutar seeders.

```powershell
npm run build
if ($LASTEXITCODE -eq 0) {
    php artisan view:clear
    if ($LASTEXITCODE -eq 0) { php artisan test }
}
```

```powershell
$pruebas18 = @(Get-ChildItem -LiteralPath 'tests/Frontend' -Filter '*.test.mjs' -File | Select-Object -ExpandProperty FullName)
node --test $pruebas18
```

Si faltan dependencias y falla el build, conservar la salida para revisarla; no se
modifican versiones o lockfiles para resolverlo sin identificar el error.

## Evidencia de preparación

- Node: 137 pruebas correctas, incluidas 25 nuevas de sonido.
- Se comprueban autorización dentro del gesto, preferencia sin autoplay, contexto
  suspendido/interrumpido/cerrado, rechazo y límite temporal, cancelación, resultados
  atrasados, ráfagas, silencio y liberación de nodos, pérdida de visibilidad,
  almacenamiento bloqueado, contexto parcial, receptor nativo de temporizadores,
  botón/ARIA, ausencia de duplicados y omisión de campos sensibles.
- Dos casos PHP nuevos comprueban el control opcional, estado accesible y referencias
  versionadas en acceso/registro y perfil, manteniendo los formularios existentes.
- PHP y las dependencias de Vite no están disponibles durante la preparación.
  La ejecución de PHPUnit y npm run build queda pendiente en el Lenovo.
- Se verifica el ZIP, huellas, instalación CRLF, backup, reinstalación, rechazo
  previo a escrituras, rollback y diff completo con Git, incluidos archivos nuevos.

Los dobles de WebAudio comprueban la secuencia y las carreras, no que el altavoz
físico de un teléfono emita sonido. La aceptación en un dispositivo real queda
pendiente después de publicar. Que el contexto esté activo no prueba el volumen
multimedia, la salida elegida o el modo silencio del dispositivo.

## Prueba publicada de Joy

Con volumen multimedia audible, en el navegador normal del teléfono:

1. Recargar: no debe sonar por sí solo.
2. Tocar el altavoz: comprobar dos notas, icono activo y aviso de activación.
3. Tocar una acción: comprobar el tono breve y que la acción sigue funcionando.
4. Apagar: los siguientes toques no deben sonar.
5. Activar y recargar: comprobar que no hay autoplay y el siguiente gesto habilita
   la preferencia guardada.
6. Cambiar de aplicación o bloquear la pantalla; volver y tocar una acción para
   comprobar la recuperación sin duplicar tonos.
7. Repetir en ordenador. Si el navegador no tiene WebAudio, el botón debe ocultarse.

Si no se oye, anotar sistema y navegador, si el icono se activa y qué aviso muestra.
No se considera aceptado por el resultado de Node solamente.

## Continuidad y registro IA

- Herramienta/petición: ChatGPT (OpenAI), corrección del botón de sonido en móvil.
- Fecha: 27/11/2026. Joy implementa, prueba y publica según el reparto acordado.
- Cambios: motor de sonidos con activación confirmada, control accesible, retirada
  del motor anterior, 25 casos Node y 2 PHP.
- Referencias primarias: https://developer.chrome.com/blog/autoplay#web_audio
  y https://www.w3.org/TR/webaudio/#dom-audiocontext-resume.
- Pendientes: build y PHP en Lenovo, commit/PR/merge y aceptación audible en móvil.
- Tras aceptar el punto 2, continúa Joy con el punto 4, Encuentra mi partido.
  Denys conserva 3, 9 y 10. El proyecto sigue abierto; documentación final pendiente.

## Validación local — Joy — 27/11/2026
- Base confirmada: main 24fa74e.
- Compilación Vite correcta, en 704 ms.
- PHP: 214 pruebas y 1727 aserciones correctas, en 31,17 s.
- JavaScript: 137 pruebas correctas, sin fallos.
- Prueba audible en teléfono y ordenador pendiente tras publicar.
