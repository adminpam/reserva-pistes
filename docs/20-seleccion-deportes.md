# 20 · Selección visual de varios deportes

Paquete 12-Denys · 25/11/2026 · base main b57c417, merge del PR #25.
Rama: feat/seleccion-deportes. Instalación: C:\Dev\ProjIA\reserva-pistes.

## Punto de partida verificado por el equipo

Joy instaló 11-Joy y aplicó la migración de pádel: 165 pruebas PHP y 1365
aserciones correctas, en 22,24 s. Comprobó tenis/pádel por URL, conservación al
cambiar fecha, recuperación de todas las pistas y un partido de pádel 1/4. El
partido que mostraba 1/2 era de tenis, como indicaba su título; no hubo que cambiar
el aforo. Commit fbcfe75; merge PR #25 b57c417, confirmado por ambos equipos.

Denys tiene integrado el bloque de iconos/contraseñas: PR #24 ccf059e, publicado y
aceptado en teléfono, incluidos los tres campos de contraseña del perfil. Estos
resultados anteriores no acreditan la ejecución PHP o el aspecto visual de 12.

## Comportamiento de este paquete

- El mapa permite marcar uno o varios deportes. Se muestran clubes con cualquiera
  de ellos (OR), utilizando el contrato de Joy; no exige que un club los tenga todos.
- Todos quita el filtro. Desmarcar la última opción también vuelve a Todos. Marcar
  todos los deportes individualmente es válido y permite desmarcar uno después.
- Se mantiene la fila horizontal del teléfono y el panel móvil aceptado. Se usan
  checkboxes nativos con etiquetas, teclado, estado anunciado y botón Todos.
- Cada cambio actualiza sports[] en la dirección. Recargar, Atrás/Adelante y volver
  desde la caché de navegación recuperan la selección. Se conservan otros parámetros
  y fragmentos; el parámetro antiguo sport se convierte al formato común.
- Una petición anterior se cancela y su respuesta tardía se descarta. Los botones
  Reintentar usan la selección actual. Precios, etiquetas, pistas e imágenes siguen
  procediendo de la respuesta filtrada del servidor, sin inventar disponibilidad.
- La ficha seleccionada y cada tarjeta enlazan al club conservando los deportes.
  El nuevo enlace Volver al mapa y el vacío del club conservan la misma selección.
  El formulario de fecha y la disponibilidad JSON mantienen el contrato de 11-Joy.
- Partidos abiertos usa los mismos checkboxes: Filtrar envía un GET normal que
  combina deportes, nombre de club y Solo mi nivel. Sin JS se puede marcar varios
  y enviar; desmarcar todos elimina el filtro. Todos se ofrece cuando JS está activo.

## Archivos y límites

Se modifica MapController::index únicamente para validar/restaurar la selección
inicial usando SportSelection. No cambian clubs(), los controladores de reservas,
el pago, el aforo, las reglas de acceso o las migraciones. Se añade la vista parcial,
CSS y JS compartidos, y se conectan portada/partidos/ficha de club. Los recursos
propios usan FrontendAsset; el import del nuevo módulo también tiene huella de bytes.
No se requieren npm install, composer update o cambios en .env.

La instalación compara todos los originales antes de escribir, admite CRLF en
texto, crea backup fuera del proyecto y permite reinstalar sin duplicar. No ejecuta
migraciones ni toca la DB. Al venir de main recién actualizado, Denys debe ejecutar
migrate para aplicar la migración de pádel que ya revisó Joy; no ejecutar fresh o
seeders completos sobre una base usada.

## Evidencia de preparación

- 58 pruebas Node correctas: 46 previas y 12 nuevas (11 de selección y una de flujo).
- El flujo ejecuta el módulo real del mapa con DOM/Leaflet simulados: dos deportes,
  cancelación y respuesta tardía, ficha móvil/enlace, Atrás, Todos, recuperación
  desde bfcache y reintento tras error del API. No es una comprobación visual.
- Cinco casos PHP nuevos para render inicial/compatibilidad/validación de portada,
  formulario GET de partidos y enlaces de retorno desde el club; 170 casos PHP
  declarados en total. PHP/Composer no están disponibles en la preparación, por lo
  que la ejecución PHP queda pendiente en ASUS.
- Revisión visual en escritorio y teléfono real pendiente. El lanzador de Chromium
  del entorno no dispone del ejecutable; no se declara una prueba de navegador.

## Instalación y validación en ASUS

1. Instalar en feat/seleccion-deportes desde b57c417, con árbol limpio.
2. config:clear, migrate, view:clear y php artisan test. Ejecutar todos los tests Node.
3. Mapa: elegir tenis y pádel (o dos deportes con pistas en la base). Ambos quedan
   marcados; abrir un club, cambiar fecha y Volver al mapa conserva esos deportes.
4. Recargar y usar Atrás/Adelante tras cambiar filtros; recuperar la selección.
   Todos y desmarcar la última opción recuperan el catálogo completo.
5. Teléfono: deslizar los deportes, marcar dos, pulsar un marcador y abrir su ficha.
   Comprobar que el enlace sigue accesible y que el listado se abre/cierra como antes.
6. Partidos abiertos: marcar varios y Filtrar; verificar que se conservan los checks,
   el texto del club y Solo mi nivel si se activó. Un resultado vacío es válido si no
   hay partidos de esos deportes; no demuestra por sí solo un fallo del filtro.

Después registrar únicamente los resultados realmente obtenidos, commit, PR,
revisión de Joy y merge. Comprobar el commit publicado en Render y teléfono real.

## Continuidad autorizada

Continúan pendientes avatar circular con plus/autoguardado, validaciones uniformes,
sonido móvil, búsqueda de partido, Cancha viva, comparación, invitaciones/QR/calendario,
favoritos/filtros guardados e intro 3D/vídeo separada del cargador. No se cambia el
cargador en este bloque. La primera visita directa a un origen Render dormido no
puede personalizarse antes de que el origen responda solo con CSS/JS de Laravel.
El cierre y la presentación final se realizarán cuando Denys indique el formato.

## Registro de asistencia IA

- Autor: Denys; preparación con ChatGPT (OpenAI), 25/11/2026.
- Petición: «Sports could be selected all, several, or one» y «divide todos estos
  mejoreas de nuevo entre nosotros y vaya comunicando».
- Resultado: conexión visual al backend 11-Joy, recuperación por URL y pruebas.
- Validación y límites: los descritos arriba; no se atribuyen a este paquete los
  tests PHP anteriores de Joy. Commit/PR del paquete 12 pendientes.


## Validación en ASUS y ajuste visual 12a — 25/11/2026

Denys aplicó las tres migraciones pendientes (avatares, pagos y pádel). La batería
PHP pasó: 170 pruebas y 1386 aserciones, en 11,11 s. Node: 58 pruebas correctas,
125,0187 ms. Confirmó el funcionamiento del paquete y aportó una captura del mapa.
La comprobación del teléfono publicado sigue pendiente.

Petición posterior: conservar la tarjeta clara y el icono normal, con un círculo
naranja de selección y brillo suave, en lugar del check cuadrado/fondo verde.
Ajuste 12a: únicamente CSS, aplicado también al filtro de partidos y al botón
Todos. Los círculos siguen visibles en la fila horizontal del teléfono. Se mantiene
la semántica de checkboxes para selección múltiple y el foco de teclado. No se
cambia JavaScript, HTML, filtros, API o datos. Movimiento reducido elimina la
transición del indicador. Revisión visual de este ajuste pendiente con Denys;
no se atribuye a los tests de lógica una comprobación del aspecto de los círculos.

## Aceptación local — Denys — 25/11/2026
- PHP: 170 pruebas y 1386 aserciones correctas.
- JavaScript: 58 pruebas correctas.
- Selección múltiple, navegación y filtros de partidos comprobados.
- Ajuste 12a aceptado: tarjetas claras y círculos naranjas.
- Pendientes: revisión de Joy, publicación y comprobación en teléfono por HTTPS.
