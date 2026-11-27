# Ajuste 18c — hover y teclado móvil

Fecha: 27/11/2026. Responsable: Joy. Rama y PR existentes: fix/sonido-clics.
Base de instalación: ajuste 18b instalado, probado y todavía sin merge.
Este documento sustituye el alcance de 27b: se recuperan hover y escritura.
La validación local de 27b se conserva como registro de su revisión anterior.

## Comportamiento final acordado

- Se conservan clic y hover con sus tonos diferenciados. El hover incluye enlaces
  del menú principal, el menú desplegable, los botones y acciones de cabecera
  como Perfil y Salir. Se gestiona de la misma forma desde Perfil y las demás páginas.
- El hover solo corresponde a un puntero mouse, no a los toques del teléfono.
  Entrar en un icono hijo del mismo enlace no repite el tono.
- La edición genera una nota a partir del evento input, sin depender de keydown.
  Cubre teclado físico y móvil, composición, borrado y sustitución de texto.
- keydown prepara audio silenciosamente para el teclado físico. No genera una
  segunda nota. No se lee ni almacena el texto escrito.
- Se mantiene la exclusión de contraseñas, pagos y autocompletado de códigos
  sensibles. Los campos deshabilitados y de solo lectura no suenan al editar.
- Los avisos Sonidos activados/desactivados solo corresponden al botón del altavoz.
  El hover, la edición y reanudar al navegar no los repiten.
- El icono sigue representando la elección persistida. Apagarlo detiene los sonidos,
  cancela las esperas e impide reproducir respuestas tardías.

## Reanudación y límite del navegador

Pasar el ratón con la preferencia activada intenta preparar el audio sin avisos.
Algunos navegadores exigen un clic antes de permitir Web Audio en un documento.
Si bloquean o dejan pendiente ese intento, se conserva el icono activado; no se
muestra un error por esa espera. Un clic real sustituye el intento pendiente y
vuelve a llamar a resume dentro del gesto válido. No se reproduce nada al cargar.

Referencias oficiales:

- [Política de reproducción de Chrome](https://developer.chrome.com/blog/autoplay/).
- [UI Events: composición y edición](https://www.w3.org/TR/uievents/).

La reanudación no puede garantizar sonido de hover antes del permiso del navegador.
No se cambian sus permisos ni el volumen del sistema del dispositivo.

## Niveles

La ganancia maestra sigue en 0,8. Cada tipo mantiene siempre su propia receta:
clic triangular 1320 Hz/35 ms/amplitud 0,07; hover sinusoidal
1760 Hz/25 ms/amplitud 0,025; edición cuadrada 880 Hz/22 ms/amplitud 0,035.
No hay hover de ratón sintetizado por toques ni melodía extra de reanudación.
El clic tiene prioridad sobre un hover recién reproducido; el hover se limita
para evitar ráfagas. Comprobar el resultado audible en el móvil después de publicar.

## Alcance e instalación

Tres archivos: public/ui/sounds.js, tests/Frontend/sounds.test.mjs y este documento.
No se sobrescriben 27 ni 27a ni 27b ni sus validaciones locales. Se instala sobre
18b en fix/sonido-clics; no crear otra rama ni hacer merge antes de este ajuste.

No requiere migraciones, reconstrucción de Vite ni cambios dentro de Render.
El módulo público usa una URL versionada por contenido. Después del nuevo commit
y push, la PR existente debe describir este comportamiento final antes del merge.

```powershell
node --test tests/Frontend/sounds.test.mjs
if ($LASTEXITCODE -eq 0) { git diff --check }
```

## Verificación en la preparación

- 45 pruebas de sonido correctas y batería completa de 157 pruebas JavaScript correctas.
- Regresiones de hover desde documento nuevo, bloqueo y espera de permiso, prioridad
  del clic, entrada móvil sin keydown, composición, borrado, sustitución y exclusiones.
- Se conserva la cobertura de avisos explícitos, icono persistente, silenciamiento,
  cancelación de respuestas tardías y temporizadores del navegador.
- Instalación CRLF, copias de seguridad, reinstalación, rechazo de modificaciones
  inesperadas y de archivos corruptos, restauración ante fallo de copia comprobados.
- git diff --cached --check sin errores en una copia de verificación.
- No se ejecuta PHP de nuevo: no se modifica PHP, Blade ni dependencias.
- Pendiente: probar la versión publicada en ordenador y teclado móvil real.

## Revisión publicada

Con y sin sesión, en ordenador y teléfono:

1. Activa el sonido y navega: icono encendido y sin repetir el aviso.
2. En ordenador, entra en Perfil y pasa el ratón por Mapa, Partidos, Perfil y Salir.
   Debe sonar al entrar en cada control. Si el navegador exige un primer clic,
   comprueba el hover después de realizarlo. Repite desde otras páginas.
3. En ordenador y móvil escribe, borra y acepta una sugerencia en Nombre público
   o un buscador. Deben producirse notas de edición sin sonidos duplicados por tecla.
4. En móvil abre el menú y pulsa opciones: debe sonar clic sin hover añadido.
   Compara su nivel y mantén el volumen del sistema igual durante la prueba.
5. Apaga el altavoz: aviso único, silencio total y preferencia conservada al navegar.

No registrar pruebas físicas o publicadas como realizadas sin confirmación de Joy.

## Validación local — Joy — 27/11/2026
- JavaScript de sonido: 45 pruebas correctas.
- git diff --check sin errores.
- Pendiente: comprobar hover desde Perfil y escritura con teclado móvil en la versión publicada.
