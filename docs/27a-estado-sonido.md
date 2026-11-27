# Ajuste 18a — estado del botón de sonido

Fecha: 27/11/2026. Responsable: Joy. Rama: fix/estado-sonido.
Base: main con el paquete 18 de sonido móvil integrado. El instalador comprueba
las huellas de los dos archivos existentes y conserva copias de seguridad.

## Incidencia observada

Joy confirma sonido audible en ordenador y teléfono, con y sin sesión.
Al navegar, el altavoz vuelve a dibujarse apagado aunque la preferencia sigue
activada y los clics siguen produciendo sonidos. Apagar expresamente sí funciona.

Cada documento espera un gesto antes de reanudar el audio. El botón estaba
representando esa disponibilidad transitoria como si fuera la elección del usuario.

## Comportamiento corregido

- Una preferencia activada conserva el altavoz encendido, su color y aria-pressed
  al navegar, recargar, ocultar la página o sufrir una interrupción temporal.
- La carga no crea un contexto ni reproduce audio. El siguiente gesto permitido
  reanuda la reproducción; esto no cambia la selección visual.
- Pulsar el altavoz encendido lo apaga incluso antes del primer clic de la página.
  Los clics posteriores permanecen en silencio hasta volver a activarlo.
- Una primera activación desde apagado se confirma cuando el audio está preparado.
  Un fallo real desactiva la preferencia y conserva el aviso de error existente.
- No depende de iniciar sesión: la elección se guarda en el mismo navegador.

El controlador separa enabled (elección confirmada) de active (reproducción
permitida en ese momento). El botón alterna según enabled. La reproducción sigue
limitada por active y por los gestos originales del usuario.

## Archivos

- public/ui/sounds.js: estado visual y acción del botón.
- tests/Frontend/sounds.test.mjs: tres regresiones nuevas y expectativas ajustadas.
- docs/27a-estado-sonido.md: explicación y comprobaciones.

No requiere cambios en Render, migraciones ni reconstruir Vite. El módulo público
ya lleva una URL versionada por su contenido. El despliegue sigue siendo automático
tras el merge. Se conserva docs/27-sonido-movil.md y su validación local.

## Verificación durante la preparación

- 28 pruebas Node de sonido correctas; batería completa: 140 pruebas correctas.
- Las tres regresiones nuevas fallan con el código del paquete 18 y pasan con el ajuste.
- Instalación validada con finales CRLF, copia de seguridad, reinstalación, rechazo
  de cambios locales y de paquetes incompletos, y restauración tras fallo de copia.
- git diff --cached --check sin errores en una copia de prueba.
- Este ajuste modifica solo JavaScript y documentación. No se ha ejecutado PHP
  de nuevo durante su preparación. La validación visual y audible publicada queda pendiente.

## Comprobación local

```powershell
node --test tests/Frontend/sounds.test.mjs
if ($LASTEXITCODE -eq 0) { git diff --check }
```

## Comprobación tras publicar

En ordenador y teléfono, como visitante y con sesión:

1. Activa el sonido y navega entre las opciones disponibles del menú. El altavoz
   debe seguir encendido; los clics deben seguir sonando cuando el navegador lo permita.
2. Recarga y apágalo antes de pulsar otro elemento. Debe apagarse al primer toque,
   permanecer apagado al navegar y no producir sonidos con los clics posteriores.
3. Actívalo de nuevo, cambia de pestaña o bloquea/desbloquea el teléfono y vuelve.
   El icono conserva la selección; el siguiente gesto permite reanudar el audio.

No registrar la comprobación publicada como realizada hasta recibir el resultado de Joy.

## Validación local — Joy — 27/11/2026
- Base confirmada: main d50fd6d.
- JavaScript de sonido: 28 pruebas correctas.
- git diff --check sin errores.
- Pendiente: comprobar el icono publicado en ordenador y móvil, con y sin sesión.
