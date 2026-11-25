# 19 · Iconos y visibilidad de contraseña · Denys

Paquete 11-Denys · 24/11/2026 · base 873e439a24d546c472e988165e341020bdba3aed.
Este paquete es independiente de 11-Joy: no modifica sus archivos ni depende de
la nueva migración para mostrar las seis clases de deporte existentes.

## Iconos aprobados

Los siete deportes usan los SVG del generador aceptado: tenis de mesa, bádminton,
baloncesto 3x3, voleibol, fútbol sala, tenis y pádel. Hay variantes para claro y
oscuro; la variante clara de contorno se muestra en filtros seleccionados. El
resto de tarjetas/perfil usa el dibujo normal. Los nombres de deporte ya aportan
la etiqueta accesible: las imágenes decorativas tienen alt vacío. Deportes nuevos
sin dibujo siguen usando el SVG genérico anterior. Las clases de tamaño existentes
se conservan. Los SVG son locales y sus URLs se versionan por contenido.

## Contraseñas

La mejora encuentra los campos password existentes, conserva input, name, id,
valor, autocomplete, requisitos, CSRF y contratos de formularios. Añade un botón
type=button con Mostrar/Ocultar y aria-controls/aria-pressed. Cada campo se maneja
independientemente. El cursor y el valor se conservan. Se ocultan al enviar,
restablecer o abandonar/recuperar la página. Sin JS siguen ocultas como siempre.
No hay envíos, almacenamiento, logging o cambios de contraseña desde el botón.

Se aplica a entrar, registro, contraseña actual/nueva/confirmación del perfil y
futuros campos password presentes al cargar la página. No se toca el controlador
ni se cambia el criterio de seguridad del servidor. El círculo de avatar con plus,
la validación uniforme y el sonido móvil se trabajan después, en su propio bloque.

## Comprobaciones

Seis casos Node nuevos aprobados al preparar: conservación de valor/selección,
etiquetas, envío/reset, mejora repetida sin duplicados, campos independientes y
teclado que no permite restaurar selección. La batería combinada Frontend pasó
46 casos (40 anteriores y 6 nuevos) al preparar esta entrega.
PHP y la comprobación visual en móvil siguen pendientes en ASUS. No se atribuye
aceptación del ojo al conjunto de iconos que Denys ya aceptó.

Prueba con Denys:
1. Entrar: mostrar/ocultar contraseña sin enviar el formulario.
2. Crear cuenta: ojos separados para contraseña y confirmación.
3. Perfil: actual, nueva y confirmación independientes; cambiar visibilidad no
   modifica el contenido. Completar una operación válida conserva el comportamiento.
4. Teléfono 390 px y escritorio: ojo dentro del campo, sin tapar texto ni icono.
5. Filtros seleccionados y tarjetas: tamaños legibles y variante apropiada.
6. Las credenciales usadas para probar deben ser ficticias y no se pegan en el chat.

## Instalación y flujo del equipo

Rama de Denys: feat/iconos-passwords. Instalar en C:\Dev\ProjIA\reserva-pistes.
Ejecutar php artisan view:clear y las pruebas indicadas en INSTALACION.md. No hay
migraciones nuevas, dependencias npm/Composer o cambios de Vite en este paquete.
Después: commit/PR, revisión de Joy y merge. Puede instalarse antes o después de
11-Joy. En la siguiente fase Denys conecta el mapa de varios deportes al contrato
sports[] que prepara Joy. No publicar el cierre final: el desarrollo continúa.

## Registro IA de este bloque

- Fecha/autor: 24/11/2026 · Denys.
- Herramienta: ChatGPT (OpenAI).
- Objetivo: integrar los iconos aceptados y facilitar escribir contraseñas.
- Prompt: «estos iconos ya me sirven»; «En constraseñas necesitamos añadir ojo».
- Resultado: imágenes locales y alternancia de contraseña sin modificar formularios.
- Validación: seis casos Node nuevos aprobados; revisión visual y PHP pendientes.
- Commit/PR: pendientes; enlazar después del commit real y revisión de Joy.

## Validación local — Denys — 25/11/2026
- PHP: 10 pruebas y 74 aserciones correctas, en 0,92 s.
- JavaScript de contraseñas: 6 pruebas correctas.
- Iconos revisados visualmente en los filtros del catálogo local.
- Visibilidad de contraseñas de acceso/registro mostrada en capturas.
- Pendientes: revisión visual completa del perfil y teléfono, revisión de Joy y publicación.
