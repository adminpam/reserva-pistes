# 17 · Pago de prueba integrado

Paquete 10 · Joy · 24/11/2026. Base recibida: a423786971dbdf7b9c01ff6073b9df23496a0f21.
Rama de trabajo prevista: feat/pago-simulado. Joy confirmó la revisión y el merge
del perfil por Denys. No se atribuye un número de PR o commit de integración no aportado.

## Alcance

Simulación educativa interna, sin proveedor, claves ni dinero. El formulario admite
solo los datos ficticios indicados abajo. No habilita pagos reales ni reembolsos
bancarios. No añade dependencias Composer/npm, servicios, cron o almacenamiento de
tarjetas. La composición de mapa, perfil y acceso aprobada se conserva.

## Recorrido y datos

1. El jugador elige pista, fecha, hora y modalidad en la ficha del club.
2. POST /bookings valida la selección, calcula el importe y crea booking_checkouts.
   Es una retención, no una reserva: todavía no hay Booking ni participantes.
3. La hora queda bloqueada hasta cinco minutos, como máximo hasta el comienzo del
   slot. Se abre el formulario de pago con el resumen y el tiempo restante.
4. Aprobar crea una reserva confirmada, un resultado de prueba y, si es abierta,
   la participación del organizador, en una misma transacción.
5. Rechazar, cancelar o caducar libera la retención. No crea una reserva ni publica
   un partido. Una confirmación antigua sigue teniendo su justificante.
6. Al cancelar una reserva aprobada, su resultado pasa a refunded (simulación
   revertida). Referencia, importe, reserva y participantes se conservan.

| Tabla nueva | Qué guarda |
| --- | --- |
| booking_checkouts | UUID, usuario, pista, slot, límite de tiempo, importe en céntimos, modalidad/niveles, estado, Booking opcional y motivo de conflicto |
| demo_payments | Checkout único, Booking opcional único, referencia DEMO única, importe, resultado, fecha de proceso/reversión |

Las FKs nuevas usan RESTRICT: no se pueden borrar usuario/pista/reserva que sostienen
estos registros. La interfaz da errores comprensibles antes de intentar esos borrados.
Una pista con solicitudes históricas conserva club/deporte; su tarifa puede cambiar.
No se inventan pagos para las reservas anteriores a 10: siguen funcionando y pueden
cancelarse según sus reglas anteriores.

## Estados

| Estado de la solicitud | Resultado | Efecto sobre la hora |
| --- | --- | --- |
| pending y vigente | Sin resultado | Retenida |
| paid | approved, Booking confirmed | Ocupada por la reserva |
| declined | declined, sin Booking | Libre si no existe otra ocupación |
| cancelled | Sin resultado | Libre si no existe otra ocupación |
| expired | Sin resultado | Libre sin necesidad de visitar la solicitud |
| conflict | Sin resultado; disponibilidad/horario/nivel cambió | Se vuelve a comprobar al consultar |
| paid con resultado refunded | La reserva se canceló | Libre si no existe otra ocupación |

Caducar se calcula al leer la disponibilidad: status=pending, expires_at>ahora y
starts_at>ahora. No hace falta un proceso que limpie filas para liberar la hora.
La pantalla de la solicitud actualiza el estado persistido al abrirla. El historial
puede mostrar Tiempo agotado aunque la fila todavía contenga pending.

## Datos ficticios del formulario

| Campo | Valor permitido |
| --- | --- |
| Titular | Persona Demo |
| Tarjeta aprobada | 4242 4242 4242 4242 |
| Tarjeta rechazada | 4000 0000 0000 0002 |
| Caducidad | MM/AA actual o futuro; por ejemplo 12/30 para esta práctica |
| CVC | 123 |

Los dos botones rellenan el formulario; no lo envían. El usuario confirma con
Simular pago. El servidor acepta números con espacios/guiones y valida los datos.
Un error de formato conserva la retención para corregirlo; un rechazo de prueba
termina esa solicitud y libera la hora. Datos desconocidos o arrays mal formados
no se convierten en aprobaciones.

El controlador retira payment de los datos de la petición antes de consultar la
base. La configuración de excepciones excluye payment de old input. No hay columnas
para número, titular, caducidad o CVC ni llamadas de logging que los registren.
Se guarda únicamente el resultado y su referencia. El despliegue debe mantener
APP_DEBUG=false, como el resto de la aplicación; no introducir tarjetas reales.

## Repeticiones, precio y concurrencia

checkout_key es un UUID preparado por el servidor en el formulario. Cambiar la
selección lo renueva en navegadores con crypto.randomUUID; sin ese soporte, recargar
la ficha genera una clave nueva. El servidor no acepta reutilizar la misma clave
para otra selección o usuario. Repetir un envío idéntico devuelve la misma solicitud,
aunque ya esté pagada; no aumenta el tiempo ni cambia el importe. Iniciar la misma
selección activa desde otra pestaña del mismo usuario también reanuda esa retención.

El importe se copia de la tarifa de la pista al crear la retención y se mantiene
hasta que termina. Consultar de nuevo una solicitud terminada crea otra con la
tarifa vigente y muestra el resumen antes de pagar; nunca confirma automáticamente.
El intento anterior se conserva. Se admiten hasta tres retenciones activas por
usuario y diez solicitudes de inicio por minuto; no se limitan los reintentos del
resultado ya confirmado mediante un cargo adicional.

Para iniciar/aprobar se bloquean usuario, deporte, pista y solicitud en ese orden.
Se vuelven a verificar propietario, horario, nivel, tiempo y ocupación después de
los bloqueos. La lectura orientativa del deporte va fuera de la transacción y se
verifica dentro, antes de escribir. Se reintentan hasta tres veces los deadlocks
que reconoce Laravel. El índice único de checkout_id impide dos resultados para
una solicitud; booking_id y referencia también son únicos. Una colisión de UUID
se resuelve fuera de la transacción abortada verificando usuario y selección.

Reservas confirmadas y retenciones activas se leen con una única UNION ALL para
ver ambas en la misma instantánea. Dos consultas separadas podrían observar una
falsa hora libre justo durante la transformación de retención en reserva. La
aprobación excluye su propia retención al buscar solapamientos. Las actualizaciones
de horario del gestor también respetan las retenciones activas.

Estos mecanismos no sustituyen la prueba de peticiones simultáneas: queda pendiente
con Denys en PostgreSQL. SQLite en memoria y los casos secuenciales no certifican
la concurrencia de producción. Tampoco se bloquea la misma persona en dos pistas
diferentes: se conserva la regla del proyecto de impedir solapamientos de la pista.

## Rutas privadas

Todas exigen auth y auth.session. Solo el propietario puede acceder a la solicitud,
incluso si otro usuario tiene rol admin/manager. El precio/usuario/estado recibidos
por el navegador no son autoridad.

| Método | Ruta | Acción |
| --- | --- | --- |
| POST | /bookings | Comenzar o recuperar selección con checkout_key |
| GET | /my/payments | Historial propio paginado (15 por página) |
| GET | /checkout/{UUID} | Resumen, estado y justificante propio |
| POST | /checkout/{UUID}/pay | Validar tarjeta ficticia y resolver una sola vez |
| DELETE | /checkout/{UUID} | Cancelar una retención pendiente |
| POST | /checkout/{UUID}/retry | Consultar otra vez con clave nueva y tarifa vigente |

Cancelar una retención después de haber aprobado no cancela la reserva confirmada:
para ello se usa Mis reservas o Mis partidos. El organizador simula el pago completo
del partido; apuntarse/salir como participante no genera pagos ni reversiones.

## Instalación y pruebas

Aplicar php artisan config:clear, php artisan migrate y php artisan view:clear.
La migración añade dos tablas; no recrea usuarios, fotos ni reservas. No ejecutar
migrate:fresh ni seeders. Revertir esa migración borraría los nuevos justificantes:
la restauración debe coordinar código y copia de base; el instalador no toca la DB.

Ejecutar php artisan test y node --test tests/Frontend/checkout.test.mjs.
Se adaptan tres archivos de pruebas existentes al recorrido con pago; no se deja
un acceso sin pago para satisfacer los tests antiguos. Se añaden 31 casos HTTP:
CheckoutRulesTest (15) y DemoPaymentTest (16). Total declarado: 151 casos PHP.
No se han ejecutado aquí porque no hay PHP/Composer. Los 120/872 de 09 fueron
confirmados por Joy antes de estos cambios; no son un resultado de 10.

Node: 10 casos nuevos y 40 en toda la carpeta Frontend aprobados al preparar 10.
Se comprueban contador, relleno ficticio sin envío, bloqueo de doble envío,
validación y renovación de claves. Son pruebas de estado con dobles del DOM;
no acreditan una revisión visual en un navegador real.

## Recorrido manual de aceptación con Joy

1. Con una cuenta ficticia, elegir una hora futura: ver resumen y precio. Abrir
   Mis reservas: pendiente, todavía sin confirmada. Para abierta, ver Mis partidos.
2. Desde otra cuenta en incógnito, comprobar que esa hora está ocupada mientras
   el pago está pendiente y que no se publica un partido abierto aún.
3. Rellenar la tarjeta aprobada y enviar: ver justificante; volver a Mis reservas.
   Repetir/recargar la URL del resultado: debe seguir habiendo una sola reserva.
4. Crear otra selección y usar rechazo: ver el resultado, ninguna confirmada y
   hora disponible al actualizar la otra pestaña. Consultar de nuevo muestra un
   nuevo resumen, no paga por sí solo.
5. Cancelar una solicitud pendiente: hora libre, sin resultado de pago aprobado.
6. Dejar otra solicitud cinco minutos sin pagar. Consultar disponibilidad desde
   la otra cuenta sin visitar primero la solicitud: debe volver a estar libre.
   Enviar el formulario antiguo no debe crear una reserva.
7. Aprobar una reserva y cancelarla desde Mis reservas: estado Cancelada e historial
   de pago Simulación revertida, manteniendo el importe y la referencia.
8. Aprobar un partido abierto, unir otro jugador y cancelar como organizador:
   conservar participantes en el historial y revertir solo el pago del organizador.
9. Probar datos ficticios incorrectos y acceso al UUID con otra cuenta: errores de
   validación/403; no mostrar el justificante ajeno ni dejar cambios parciales.
10. Revisar 390/768/1024 px, teclado, campos, acciones y vuelta del historial.
    Registrar solo lo observado. No atribuir una comprobación móvil real a Node.

Tras la revisión de Denys y el merge: migraciones/persistencia tras redespliegue,
PostgreSQL paralelo, URLs/commit servido, capturas del producto, documentos y v1.0.

Fuentes primarias de implementación:
- https://laravel.com/docs/12.x/database#database-transactions
- https://laravel.com/docs/12.x/queries#pessimistic-locking
- https://laravel.com/docs/12.x/migrations#foreign-key-constraints
- https://api.laravel.com/docs/12.x/Illuminate/Foundation/Configuration/Exceptions.html

## Cierre de validación del paquete 10 — Joy — 24/11/2026
- Migración de pagos aplicada correctamente.
- PHP: 151 pruebas y 1290 aserciones correctas, en 15,13 s.
- JavaScript del pago: 10 pruebas correctas.
- Aprobación con justificante, importe correcto y sin reservas duplicadas.
- Rechazo sin reserva confirmada y con liberación de la hora.
- Cancelación de reserva con simulación revertida, conservando importe y referencia.
- Cancelación pendiente y caducidad de cinco minutos comprobadas; el pago tardío no confirma.
- Partido publicado solo tras aprobar, organizador contado una vez y participación con otra cuenta.
- Cancelación del partido con participantes conservados y simulación revertida.
- Pendientes: revisión de Denys, despliegue, persistencia tras redesplegar y concurrencia PostgreSQL.
