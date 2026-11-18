# 09 · Interfaz completa

Paquete 03, 17/11/2026. Archivos preparados para aplicar encima de 01 + 01b + 02.
Implementación visual terminada en código; aceptación visual, pruebas PHP y
despliegue pendientes de la ejecución del equipo. No equivale a declarar cerrado el backend.

## Cobertura

| Pantalla | Resultado preparado |
| --- | --- |
| Cabecera | Fija, hueco de documento correspondiente, sin scroll interior del menú; menú compacto al faltar ancho. |
| Mapa | Escritorio conservado; móvil con mapa principal, hoja inferior de clubes y orden por nombre/precio. |
| Club | Resumen, deportes, tiempo exterior y selección de horas por pista. |
| Mis reservas | Próximas/en curso, pasadas y canceladas; estados reales y confirmación de cancelación. |
| Partidos abiertos | Buscador, nivel, aforo del deporte, inscripción y estado completo/apuntado. |
| Mis partidos | Participantes, organizador, salida/cancelación y estados históricos. |
| Perfil | Datos del usuario, niveles 1–5 y opción No juego, nombre público editable. |
| Gestor | Pistas, alta/edición y reservas del club, con los formularios del controlador existente. |
| Administrador | Clubes/asignación de gestor, deportes/aforo y usuarios/roles. |
| Errores | 403, 404, 419, 500 y 503, con diseño independiente de sesión y Vite. |
| Componentes | Banner, iconos, vacíos, confirmación, paginación, avisos y controles táctiles. |
| Login y registro | Plantillas conservadas sin cambios, tal como pidió Denys. |

No se crean datos para imitar los ejemplos de las referencias. Tenis de mesa,
tenis, 3×3 y los demás deportes muestran el aforo de `sports.players_per_match`.
Las reservas siguen durando una hora; los ejemplos de duración/aforo de las
imágenes no se copian como reglas del proyecto.

## Componentes y explicación del código

- `public/ui/screens.css`: tarjetas, formularios y composición de pantallas
  internas. Solo se carga fuera de login y registro.
- `public/ui/screens.js`: diálogo de confirmación y resumen de hora seleccionada.
  Las operaciones finales siguen siendo peticiones POST/PATCH/DELETE protegidas.
- `partials/banner.blade.php`: cabecera con foto ilustrativa y degradado verde.
- `partials/empty-state.blade.php`: estados vacíos reales, con SVG propio.
- `partials/confirm-dialog.blade.php`: diálogo nativo con foco y Escape; intercepta
  la confirmación antes de que el formulario se marque como enviado.
- `partials/pagination.blade.php`: controles propios de paginación sin depender
  de estilos Tailwind. En móvil se priorizan Anterior/Siguiente y el número de página.
- Las vistas mantienen nombres de campos y rutas de los controladores existentes.
  El frontend no sustituye las validaciones ni permisos del servidor.
- El precio mínimo del listado móvil procede de las pistas del club, en céntimos.
  Si no existen pistas, se muestra ese estado en vez de inventar un precio.

En teléfono, las listas se convierten en tarjetas por bloques: datos, participantes
y acción; las horas tienen tres columnas; el perfil separa identidad y niveles;
la administración coloca los formularios bajo la información. Se preservan controles
táctiles de 44–48 px y se respetan las preferencias de movimiento reducido.

Los iconos de las fotografías son ilustrativos, no fotos verificadas de cada club.
El formulario de club desactiva horas ocupadas o ya pasadas y selecciona la primera
hora disponible. Una reserva que ha empezado muestra «En curso» sin ofrecer cancelar.

## Validación y limitaciones

Preparadas seis pruebas de `FrontendScreensTest`, sobre una SQLite en memoria:
pantallas del jugador, vacíos, contratos de formularios, gestor/admin, errores/login
y paginación. Deben ejecutarse en el equipo; en este entorno no hay PHP ni Composer.
Se verifican sintaxis JS, integridad del paquete e instalación sobre copia del proyecto.
No se ha declarado una validación visual en navegador de estos archivos.

Desde el ASUS de Denys, después de instalar:

```powershell
php artisan view:clear
php artisan test --filter=FrontendScreensTest
node --test tests/Frontend/map-data.test.mjs
```

Si la última compilación de Vite de 02 ya está hecha, 03 no cambia sus entradas;
los estilos y scripts nuevos se sirven desde `public/ui`. Antes de desplegar se
debe ejecutar la compilación habitual de producción y verificar la aplicación.

Lista de aceptación: escritorio, tablet, teléfono vertical/horizontal; teclado;
menú fijo; confirmaciones y Escape; perfil con errores de validación; primer horario
ocupado; todas las horas ocupadas; lista vacía; registro cancelado; precios reales
del modelo; permisos por rol; vista paginada; error de red del mapa; regreso con
el botón Atrás. No marcar un caso aprobado antes de ejecutarlo.

## Próxima fase

`docs/10-traspaso-joy.md` conserva el acuerdo para continuar en español con Joy,
comprobar el backend y añadir una simulación de pago. El despliegue en Render
y la publicación por GitHub se terminan con Denys después de la revisión.

Fuentes oficiales: https://laravel.com/framework/docs/12.x/pagination,
https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/dialog,
https://leafletjs.com/reference-1.9.4.html.


## Ajuste 03b · cierre preliminar del 18/11/2026

Denys ejecutó `FrontendScreensTest`: 6 tests, 58 aserciones, aprobados, y aceptó
preliminarmente la interfaz. No hay confirmación de despliegue de este frontend.

Se añaden entradas de tarjetas de 320 ms con desplazamiento de 6 px, respuesta
suave de horas/niveles, borde de foco, sombreado al pasar el ratón y apertura breve
del diálogo. La cascada inicial se limita a las tres primeras tarjetas. Las
animaciones no retrasan envíos ni sustituyen la respuesta del servidor y solo se
activan cuando no se solicita movimiento reducido. Login, registro y cargador se
conservan. No se añaden dependencias ni cambios de backend.

Verificar el ajuste en el navegador del ASUS con movimiento normal y reducido.
Los archivos obligatorios de instalación y manual se incorporan en 03b; las
capturas finales y el pago siguen pendientes. Detalles en `10-traspaso-joy.md`.
