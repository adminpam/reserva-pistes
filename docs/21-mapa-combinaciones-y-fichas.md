# 21 · Clubes con todos los deportes elegidos y nueva ficha

Paquete 13-Denys · 25/11/2026. Instalar tras el merge de feat/seleccion-deportes
(paquete 12 y ajuste 12a). El SHA de ese merge todavía no se ha aportado; se valida
la base por huellas de los archivos afectados. Último ancestro confirmado: b57c417.
Rama nueva: feat/mapa-filtros-y-fichas. Revisión antes del merge: Joy.

## Cambio de requisito confirmado

El paquete 11-Joy y el 12 buscaban clubes con cualquiera de los deportes elegidos
(OR). La nueva petición exige que el mismo club tenga todos (AND). Esta sección
sustituye ese apartado del contrato de la guía 18; no cambia las reservas.

Ejemplo: con tenis y pádel, un club solo de tenis ya no aparece; uno con ambos sí.
Al marcar cuatro o cinco deportes puede no existir ningún club que los reúna. El
resultado debe ser vacío, sin volver silenciosamente a OR o quitar una selección.
Sin deportes marcados se muestran todos los clubes, incluidos los que aún no
tienen pistas. Limpiar elimina el filtro y recupera el catálogo completo.

La API añade un whereHas por deporte, combinados con AND. No cuenta pistas como
deportes: dos pistas de tenis no equivalen a tenis y pádel. El filtro de las pistas
cargadas sigue usando whereIn, pues cada pista pertenece a un solo deporte. Por
eso los precios, etiquetas, imágenes y court_count siguen limitados a las pistas
seleccionadas. No se comprueba que todas estén libres a una hora: el filtro es del
catálogo; la disponibilidad real se consulta en la ficha como antes.

La búsqueda de partidos conserva su combinación de modalidades: un partido
individual pertenece a un deporte, no a varios simultáneamente. El control visual
compartido pierde Todos también allí, pero Filtrar sigue confirmando el GET.

## Selección y ayuda

- No existe la opción Todos. Al abrir la portada sin parámetros no hay checks.
- En su lugar: «Selecciona algún deporte», una mano gris y un gesto de pulsación
  discreto. El texto explica que los clubes deben reunir todos los deportes elegidos.
- La primera selección oculta la ayuda y muestra el contador y Limpiar. Limpiar
  desmarca los checks, recupera todos los clubes y devuelve la ayuda. Desmarcar el
  último deporte produce el mismo resultado. El foco vuelve al primer deporte.
- La ayuda no se ofrece si el catálogo no tiene deportes. El modo de movimiento
  reducido deja el dibujo quieto. La mano es decorativa y no se anuncia como botón.
- Se conservan las tarjetas claras, los círculos naranjas y la fila horizontal en
  teléfono. La barra de ayuda/limpieza queda por encima, fuera del scroll horizontal.
- URL, Atrás/Adelante, recarga, bfcache, aborto de consultas y reintentos mantienen
  su comportamiento. Un vacío con deportes seleccionados conserva esos checks y
  permite limpiar. El panel muestra «Ningún club reúne todos estos deportes».

## Ficha del mapa

Tarjeta con ilustración etiquetada, ciudad si está disponible, nombre, dirección,
deportes y cantidad de pistas del filtro. El pie separa el precio desde/hora y
una acción amarilla Ver detalles. El precio procede del servidor; si falta se
indica Precio por consultar. El nombre comercial del club no se usa para inferir
deportes: las etiquetas reflejan sus pistas registradas, que son de demostración.

El popup ajusta su ancho al viewport, reserva espacio para el cierre y mejora
espaciado, bordes y jerarquía. La ficha elegida del teléfono y la lista comparten
el contenido y conservan enlaces con sports[]. El panel móvil sigue teniendo mapa,
ficha y lista; el límite de la ficha pasa del 58 al 72 % de la altura disponible
para alojar el pie, manteniendo parte del mapa visible. En ventanas muy bajas la
ficha conserva su scroll interior. No se introducen gestos nuevos.

Todos los textos se construyen como textContent, sin interpretar HTML recibido;
los enlaces e imágenes mantienen la validación de origen del mapa. Los imports
JS y el CSS llevan huellas actualizadas para evitar módulos viejos tras publicar.

## Evidencia y límites

Base 12: Denys aportó 170 pruebas PHP/1386 aserciones y 58 pruebas Node correctas;
aceptó el funcionamiento y los círculos de 12a. Joy revisó y se comunicó el merge.
Eso no acredita los cambios de este paquete ni una prueba visual del teléfono.

Preparación de 13: 62 pruebas Node correctas. Se ajustan los casos de ayuda y
limpieza y el flujo real del módulo con DOM/Leaflet simulados, incluyendo vacío
con selección y recuperación del catálogo. Se añaden casos de precio, cantidad y
enlace de la ficha. Los tres casos PHP nuevos comprueban combinación de cuatro
deportes sin club común, pistas repetidas del mismo deporte y reducción progresiva
del catálogo. Total declarado: 173 casos PHP. Los casos OR anteriores del mapa
se actualizan a AND; los de búsqueda de partidos siguen verificando modalidades.

PHP/Composer y un navegador ejecutable no están disponibles en la preparación.
PHP y el aspecto visual deben comprobarse en ASUS y después en teléfono por HTTPS.
El SVG y la estructura CSS se revisaron; eso no equivale a una prueba de navegador.

## Instalación y comprobación con Denys

1. Partir de main limpio y actualizado, crear feat/mapa-filtros-y-fichas e instalar.
2. php artisan config:clear, php artisan view:clear y php artisan test; después Node.
   No hay migraciones nuevas, cambios de dependencias ni necesidad de seeders.
3. Portada sin parámetros: todos los clubes, ningún check, ayuda visible, sin Todos.
4. Seleccionar tenis/pádel: solo clubes con ambos, ayuda oculta y Limpiar visible.
   Marcar muchos deportes sin club común: vacío claro y selección conservada.
5. Limpiar y desmarcar el último: catálogo completo y ayuda recuperada. Probar la
   recarga, Atrás y el enlace de retorno del club.
6. Abrir una ficha: precio, deportes, dirección, cierre sin solaparse y botón Ver
   detalles. En teléfono comprobar fila de deportes, ficha y lista, sin perder CTA.

Tras resultados reales: registrar aceptación, commit/PR, revisión de Joy, merge y
publicación. No se declara terminado el proyecto. El avatar circular/autoguardado
y el resto de mejoras acordadas siguen pendientes; este bloque atiende la revisión
solicitada del mapa y sus tarjetas.

## Registro IA

- Autor de la petición: Denys, 25/11/2026; asistencia de ChatGPT (OpenAI).
- Petición: «quitas la opcion todos», «si ... selecciona 4-5 o todos ... no aparece
  nada», «una simple animacion en gris ... mano», «la tarjeta en mapa ... mejoraria».
- Resultado: contrato AND para clubes, Limpiar/ayuda y nueva ficha compartida.
- Validación: Node 62; PHP y revisión visual pendientes. Commit/PR pendientes.

## Validación y preparación de publicación — Denys — 26/11/2026
- PHP: 173 pruebas y 1407 aserciones correctas, en 18,83 s.
- JavaScript: 62 pruebas correctas.
- Revisión visual aplazada hasta publicar por HTTPS.
- Pendiente comprobar ayuda, Limpiar, combinación de deportes y fichas en escritorio y teléfono.
