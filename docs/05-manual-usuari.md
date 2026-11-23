# 05 · Manual de usuario

Aplicación: ReservaPistes. URL existente: https://reserva-pistes.onrender.com/.
La versión del frontend validada localmente todavía no se ha confirmado como
publicada en esa URL. Manual basado en las pantallas y rutas actuales; comprobar
los recorridos con Joy antes de cerrar la entrega.

**Proyecto educativo:** las reservas y los importes son de demostración.
Las imágenes de pistas son ilustrativas. No representan instalaciones verificadas
de los clubes de OpenStreetMap. No se realiza ningún cobro.

## Invitado: encontrar un club

1. Abrir «Mapa» y elegir un deporte o «Todos».
2. Seleccionar un marcador y pulsar «Ver detalles».
3. En teléfono, usar los deportes sobre el mapa y la lista inferior de clubes;
   la lista permite ordenar por nombre o precio disponible. No se muestran
   distancias inventadas.
4. Si la base no tiene clubes, aparece un estado vacío. Si falla la consulta,
   utilizar el reintento disponible. Cambiar el diseño no carga datos en la base.

## Jugador: cuenta y perfil

1. Pulsar «Crear cuenta», completar el formulario y entrar como jugador.
2. Usar «Entrar» para acceder con una cuenta existente.
3. En «Perfil», editar el nombre público y elegir nivel 1–5 por deporte.
   «No juego» elimina el nivel de ese deporte.
4. Pulsar «Guardar cambios» y comprobar el aviso de resultado.

Las cuentas de prueba se explican en [`04-installacio.md`](04-installacio.md).
Para las capturas y la demostración utilizar únicamente identidades ficticias.

## Jugador: reservar y cancelar

1. En la ficha del club, seleccionar una fecha y consultar disponibilidad.
2. Elegir una pista y una hora disponible. Cada reserva ocupa una hora.
3. Si se desea buscar compañeros, marcar «Partido abierto» y, cuando corresponda,
   indicar el rango de nivel.
4. Pulsar «Reservar». En la implementación actual se confirma directamente la
   reserva de demostración; la pantalla de pago con datos ficticios sigue pendiente.
5. Revisar «Mis reservas»: próximas, pasadas y canceladas se muestran separadas.
6. Para cancelar una reserva futura propia, pulsar «Cancelar» y aceptar el diálogo.
   Cerrar o rechazar el diálogo no debe enviar la cancelación.
   Si la ficha está en otra pestaña, al volver a ella las horas se vuelven a
   consultar automáticamente. Si falla la red, aparece el aviso para utilizar
   «Ver disponibilidad».

Una hora ocupada no puede seleccionarse. Si otra persona reserva mientras el
formulario está abierto, el servidor debe rechazar el conflicto; este recorrido
y las peticiones simultáneas forman parte de la revisión pendiente del backend.

## Jugador: partidos abiertos

1. Abrir «Partidos abiertos» y utilizar los filtros disponibles.
2. Comprobar deporte, club, fecha, organizador, plazas y nivel.
3. Pulsar «Unirme» en un partido compatible con plazas libres.
4. Revisar «Mis partidos» y usar «Salir» si se desea abandonar uno futuro.
5. Si se organiza el partido, su cancelación afecta al conjunto de participantes.

El aforo procede del deporte guardado en la base, no del número de círculos del
diseño. No se ofrecen acciones sobre partidos pasados o cancelados. La validación
de niveles, aforo y concurrencia se comprobará con Joy.

## Gestor de club

1. Entrar con rol `manager` y abrir el panel de pistas.
2. Crear una pista en un club asignado: nombre, deporte, precio por hora,
   horario y condición interior/exterior.
3. Guardar, comprobar la ficha pública y editar cuando sea necesario.
4. Consultar las reservas de sus clubes en el panel correspondiente.
5. Para eliminar una pista, leer y aceptar la confirmación.

El servidor debe limitar todas las operaciones a los clubes del gestor, incluso
al introducir una URL ajena directamente. Antes de eliminar pistas con historial,
revisar con Joy las reglas de conservación de reservas; no probar borrados sobre
datos que deban conservarse.

## Administrador

1. Entrar con rol `admin` y abrir el panel de administración.
2. En «Clubes», asignar gestores y consultar los datos importados.
3. En «Deportes», crear deportes, modificar su aforo y respetar las restricciones
   de borrado de deportes con pistas.
4. En «Usuarios», gestionar roles y revisar las protecciones de la propia cuenta.

El alcance real de cada formulario y sus permisos deben validarse en la fase de
backend. El panel no implica una integración comercial con los clubes reales.

## Ayudas de la interfaz

- Sonidos desactivados por defecto y activables desde la cabecera.
- Menú compacto en tablet y navegación inferior en teléfono.
- Foco visible con teclado y confirmaciones antes de acciones destructivas.
- Movimiento reducido cuando el sistema lo solicita; las animaciones no
  representan una reserva o un pago aprobados por el servidor.
- Páginas de error con salida a la aplicación; un error no confirma una operación.

## Pago de prueba — siguiente fase, todavía no disponible

Con Joy se añadirá **al final del backend** un resumen del importe del servidor,
datos de prueba admitidos, aprobación/rechazo/cancelación y recibo de demostración.
Se actualizará este manual cuando el flujo exista y sus pruebas hayan pasado.
No introducir tarjetas reales.

## Capturas que faltan para la entrega

Las capturas son de la aplicación terminada, no del proceso de trabajo. Guardarlas
en `docs/captures/`, sin datos personales reales y después del despliegue final.
No se crean enlaces de imagen hasta que los archivos existan.

| Archivo previsto | Contenido | Estado |
| --- | --- | --- |
| `01-mapa-desktop.png` | Mapa con datos y filtro | Pendiente |
| `02-mapa-movil.png` | Mapa y lista propios de teléfono | Pendiente |
| `03-club-reserva.png` | Disponibilidad y aviso de simulación | Pendiente |
| `04-jugador.png` | Reserva/partido y perfil con identidad ficticia | Pendiente |
| `05-gestor.png` | Pistas y reservas del club del gestor | Pendiente |
| `06-admin.png` | Gestión por administrador | Pendiente |
| `07-pago-prueba.png` | Confirmación simulada, una vez implementada | Pendiente |

Añadir cada captura a su sección y seleccionar varias para el README.
