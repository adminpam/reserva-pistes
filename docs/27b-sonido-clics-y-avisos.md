# Ajuste 18b — sonido de clic y avisos explícitos

Fecha: 27/11/2026. Responsable: Joy. Rama: fix/sonido-clics.
Base: main con el ajuste 18a integrado. El instalador verifica las huellas
exactas de los archivos existentes antes de sustituirlos.

## Resultado previo confirmado por Joy

Los cuatro puntos del ajuste 18a se cumplen en la versión publicada: mantener
el icono al navegar, recargar, apagar antes del primer gesto y volver de otra
pestaña. Queda registrada la confirmación recibida el 27/11/2026.

Joy detecta después avisos repetidos al navegar, sonidos al pasar el ratón que
varían según la página y diferencias percibidas de volumen al tocar el menú móvil.

## Cambios

- Los mensajes Sonidos activados y Sonidos desactivados corresponden únicamente
  a una pulsación explícita sobre el altavoz. Reanudar al navegar o volver de una
  interrupción no anuncia una nueva activación.
- Se eliminan los listeners de pointerover y keydown: pasar el ratón, enfocar
  un elemento o escribir no genera tonos. Los clics de controles siguen funcionando,
  incluidos los que el navegador produce al activarlos con el teclado.
- Activar y pulsar controles utilizan el mismo clic: una nota triangular de
  1320 Hz, duración de 35 ms, envolvente de amplitud 0,07 y ganancia maestra 0,8.
  Se sustituye el tono doble de activación y se retiran las recetas de hover y tecla.
- Se conserva el límite de ráfagas, los gestos requeridos por el navegador, el
  icono persistente y el apagado inmediato. No se acumulan tonos mientras reanuda.
- Si falla realmente la preparación del audio se conserva el aviso de error.

La igualdad comprobada es la señal generada por la aplicación. No se modifica el
volumen del sistema del teléfono. El nivel percibido al navegar se comprobará en
los dispositivos de Joy después del despliegue.

## Alcance

Solo public/ui/sounds.js, tests/Frontend/sounds.test.mjs y este documento.
Los formularios, perfil, menú y despliegue no necesitan cambios propios.
No requiere migraciones, npm run build ni modificar opciones de Render.
El módulo público mantiene su URL versionada por contenido y se desplegará
tras el merge mediante el flujo automático existente.

## Verificación durante la preparación

- 33 pruebas de sonido correctas; batería completa: 145 pruebas JavaScript correctas.
- Cuatro regresiones nuevas fallan con 18a y pasan con 18b: aviso al reanudar,
  estado visible del aviso, sonidos sin clic y consistencia de la señal generada.
- Una prueba adicional conserva el aviso de error ante un fallo real.
- Instalación comprobada con CRLF, copia de seguridad, reinstalación idempotente,
  rechazo de cambios locales o archivos corruptos y restauración ante fallo de copia.
- git diff --cached --check sin errores en una copia de verificación.
- No se ha ejecutado PHP de nuevo: no cambian PHP, Blade, CSS ni dependencias.
- Pendiente: comprobación visual y audible del ajuste publicado.

## Pruebas locales

```powershell
node --test tests/Frontend/sounds.test.mjs
if ($LASTEXITCODE -eq 0) { git diff --check }
```

## Revisión después del despliegue

En ordenador y teléfono, como visitante y con sesión:

1. Activa el altavoz: aparece una sola confirmación. Tras navegar y pulsar otros
   controles no se repite el mensaje. El icono permanece encendido.
2. En ordenador, pasa el ratón por el menú y Salir, tanto en Perfil como en Mapa
   y Partidos: no debe sonar. Escribir tampoco produce sonidos.
3. En teléfono, abre el menú y pulsa sus opciones: el tono y su nivel deben ser
   uniformes. Volver a la página o reanudar tras cambiar de pestaña no añade melodías.
4. Apaga el altavoz: aparece una confirmación y todo queda en silencio, incluso
   después de navegar y recargar. Vuelve a activarlo para comprobar la recuperación.

Registrar los resultados publicados únicamente después de recibir la comprobación.

## Validación local — Joy — 27/11/2026
- Base confirmada: main cc9d30a.
- JavaScript de sonido: 33 pruebas correctas.
- git diff --check sin errores.
- Pendiente: comprobar avisos, silencio al pasar el ratón y volumen percibido en móvil tras publicar.
