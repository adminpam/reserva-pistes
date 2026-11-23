# 15 · Administración y gestión de clubes

Paquete 08 · Joy · 23/11/2026. Base recibida: `cf811246e619e52108bee341e8a8735e0acb1491`.
Rama de trabajo en Lenovo: `feat/admin-gestion`.

## Resultado anterior confirmado

Joy instaló 07: la batería completa pasó con **53 pruebas y 262 aserciones**, en
2,38 s. También confirmó crear un partido futuro, entrar con una segunda cuenta,
verlo en Mis partidos y salir liberando una plaza. Denys revisó el cambio y Joy
confirmó el merge. El commit de trabajo fue `ecbebfd`; el ZIP de este bloque ya
corresponde a main `cf81124`. No se afirma que Render haya desplegado este commit.

## Reglas del panel

| Operación | Regla del servidor |
| --- | --- |
| Asignar un club | El usuario debe tener rol manager; vacío retira la asignación; omitir el campo es un error |
| Cambiar el rol de un gestor | Primero retirar o reasignar todos sus clubes |
| Eliminar un gestor | Primero retirar o reasignar sus clubes; además no puede tener reservas ni participaciones |
| Cambiar o eliminar la cuenta propia del admin | No puede eliminarse ni quitarse su propio rol admin |
| Eliminar otro usuario | Solo si no tiene clubes, reservas ni participaciones, incluidas pasadas y canceladas |
| Eliminar un club | Debe estar vacío: las pistas se eliminan individualmente y solo si nunca tuvieron reservas |
| Eliminar una pista | Debe pertenecer al gestor y no tener ninguna reserva, de cualquier estado o fecha |
| Cambiar club o deporte de una pista | Solo si nunca ha tenido reservas; el club de destino también pertenece al gestor |
| Cambiar precio, nombre o interior/exterior | Permitido; el importe de las reservas existentes conserva su copia price_total |
| Cambiar horario | No puede dejar fuera ninguna reserva confirmada que termine después de ahora |
| Cambiar aforo | Permitido antes de crear partidos abiertos; después se conserva, incluso con partidos cancelados o pasados |
| Eliminar un deporte | No debe tener pistas ni niveles de jugadores asociados |

Las reglas protegen las acciones de la aplicación, incluidas peticiones manuales
a sus rutas. Las migraciones existentes conservan sus claves foráneas en cascada;
no se debe confundir la protección HTTP con un bloqueo de DELETE ejecutado a mano
en SQL, una consola o un script de mantenimiento. 08 no añade migraciones.

No se añade un archivado ni se presenta una pista con reservas como eliminable.
Una futura función de retirada temporal exigiría un estado de publicación propio.

## Por qué se conserva el aforo

El modelo actual muestra las plazas leyendo sports.players_per_match. No existe
una copia de ese aforo en cada reserva. Reducirlo podría dejar más participantes
que plazas; aumentarlo también modificaría la capacidad mostrada en el historial.
Por eso, cuando existe cualquier partido de ese deporte, se mantiene el aforo.
Guardar el mismo valor sigue permitido. Las reservas privadas no fijan el aforo.

## Validación y mensajes

- Los IDs del formulario son enteros y deben existir; asignar un jugador o admin
  como gestor devuelve validación y conserva la asignación anterior.
- Los precios admiten de cero a dos decimales y un máximo de 500 euros. El servidor
  convierte a céntimos y descarta un price_per_hour enviado para manipular el importe.
- El horario respeta 0–23 de apertura y 1–24 de cierre, siempre con cierre posterior.
  Se comprueban los extremos completos de cada reserva: también minutos parciales,
  una reserva en curso y el cierre a las 00:00 del día siguiente.
- Se comprueba el slug de nuevos deportes: nombres diferentes que producen el
  mismo identificador generan un error comprensible, no un fallo SQL. La restricción
  única y su excepción específica cubren también una colisión durante la creación.
- Las búsquedas de clubes y usuarios rechazan arrays y textos de más de 150 caracteres.
- Los diálogos existentes explican las condiciones del borrado. Solo cambia su
  texto; se conserva la composición visual aprobada por Denys.

## Transacciones y turnos compartidos

Las operaciones vuelven a leer los registros dentro de DB::transaction y usan
lockForUpdate. Una comprobación y su escritura se hacen dentro del mismo turno.

- Las asignaciones y los cambios de rol comparten el bloqueo del usuario gestor.
  Crear/editar/borrar pistas vuelve a comprobar su rol y la propiedad del club.
- Los cambios de rol/baja bloquean los administradores ordenados por ID y vuelven
  a verificar al actor: evita que dos bajas simultáneas se autoricen con cuentas
  antiguas dejando la aplicación sin administrador.
- Las escrituras del gestor toman usuario, clubes ordenados, deporte cuando
  corresponde y pista. El borrado de un club comparte su fila con la creación
  de pistas para que no aparezca una pista nueva mientras se comprueba si está vacío.
- La creación de reservas toma usuario, deporte y pista; entrar a un partido toma
  usuario y reserva. Son los mismos registros que protegen bajas, aforo y pistas.
  Si cambió una relación desde la lectura inicial, se pide recargar el formulario.

Las pruebas SQLite secuenciales no acreditan una carrera real. La comprobación
de envíos paralelos en PostgreSQL sigue pendiente para el despliegue con Denys.
Los bloqueos pueden reintentarse hasta tres veces si la base detecta un deadlock.

## Pruebas preparadas

Se añaden **30 casos HTTP**: 17 en AdminManagementTest y 13 en ManagerManagementTest.
Cubren roles, acceso directo, asignación/reasignación, búsquedas, altas, bajas,
historial de propietario/participantes, aforo, slug, precios y horarios.

El paquete se ha revisado y su instalador se prueba sobre una copia del ZIP con
finales CRLF, reinstalación y rechazo de cambios locales. **Las pruebas PHP de 08
están pendientes en Lenovo**: el entorno que prepara el ZIP no tiene PHP/Composer.
No se presentan los 53 resultados anteriores como resultados del código nuevo.

Después de instalar, en PowerShell del Lenovo, dentro de C:\Dev\reserva-pistes:

```powershell
php artisan config:clear
if ($LASTEXITCODE -eq 0) {
    php artisan view:clear
    if ($LASTEXITCODE -eq 0) {
        php artisan test
    }
}
```

No se necesita ejecutar migrate, seeders ni npm para este paquete. La base local
se conserva y las pruebas usan SQLite en memoria según la protección de 07.

## Comprobación manual con Joy

Usar cuentas y nombres ficticios. No cambiar las cuentas de los compañeros.

1. Con admin, cambiar el rol de una cuenta ficticia sin historial a gestor.
2. Asignarle un club de prueba. Abrir otra sesión con ese gestor y comprobar que
   solo ve sus clubes, pistas y reservas. Crear una pista sin reservas y editarla.
3. Crear una reserva futura con una cuenta de jugador. Volver al gestor: cambiar
   el precio debe funcionar conservando el importe anterior en Mis reservas.
4. Intentar borrar esa pista y el usuario que reservó: deben aparecer mensajes
   sin borrar registros. Cancelar la reserva tampoco permite borrar su historial.
5. Intentar un horario que excluya la reserva antes de cancelarla: debe rechazarse.
6. Con admin, intentar quitar el rol de gestor mientras tiene el club: debe
   rechazarse. Retirar o reasignar el club y repetir: ahora debe funcionar.
7. Borrar una pista ficticia sin reservas y después su club ya vacío: debe funcionar.

Registrar únicamente los pasos que Joy confirme. El pago, avatar y persistencia
final siguen pendientes; el siguiente bloque será perfil/cuentas y fotografía.

## Explicación para la presentación

El middleware decide qué rol entra en cada panel. El controlador comprueba además
qué club pertenece al gestor: conocer un ID no da permiso para editarlo. Antes
de borrar se consultan las relaciones, porque una cascada podría borrar reservas
y participantes. La transacción hace que se compruebe y cambie todo junto; el
bloqueo permite que las escrituras que comparten un registro se turnen.

Fuentes oficiales revisadas el 23/11/2026:
- https://laravel.com/docs/12.x/validation
- https://laravel.com/docs/12.x/queries#pessimistic-locking
- https://api.laravel.com/docs/12.x/Illuminate/Validation/Rules/Exists.html
- https://api.laravel.com/docs/12.x/Illuminate/Database/UniqueConstraintViolationException.html

## Cierre de validación del paquete 08 — Joy — 23/11/2026
- Batería completa: 83 pruebas y 534 aserciones correctas, en 3,33 s.
- Comprobados manualmente: asignación de gestor, creación y edición de pista.
- La reserva conserva 12 euros aunque la tarifa cambie a 15 euros.
- El borrado de la pista queda bloqueado antes y después de cancelar la reserva.
- Revisión de Denys y prueba de concurrencia en PostgreSQL pendientes.
