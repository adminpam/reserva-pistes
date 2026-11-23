# 14 · Reservas, partidos y aislamiento de pruebas

Paquete 07 · Joy · 23/11/2026. Preparado a partir del ZIP de la rama de Joy,
commit `fbbf879fbf56967ccd07e716a224799205455ac9`. Cierre confirmado el 23/11: Joy obtuvo 53 pruebas y 262 aserciones correctas
y comprobó crear/unirse/salir; Denys revisó y Joy confirmó el merge. Commit de
trabajo ecbebfd, integrado en main cf81124. El despliegue no se da por confirmado.

## Problemas encontrados y solución

### Reserva de una hora

La versión anterior comprobaba el solapamiento con un bloqueo sobre reservas
existentes. Una pista sin reservas no ofrecía una fila común que bloquear. Ahora
se bloquea la fila de la pista dentro de `DB::transaction` y después se consulta
la regla `inicio < otro_fin && fin > otro_inicio`. Dos creaciones de la misma pista
deben pasar por ese mismo bloqueo en PostgreSQL/MySQL.

La pista se vuelve a consultar dentro de la transacción: horario y precio se
toman del estado actual del servidor. El usuario no decide propietario, estado
ni importe mediante campos extra del formulario. `price_total` conserva el precio
de la hora aunque el gestor cambie después el precio de la pista.

Los errores de negocio se devuelven como validación, conservando los datos del
formulario. No se ocultan errores de base de datos como si todos fueran una plaza
ocupada. La transacción puede reintentarse hasta tres veces ante un deadlock.

La fecha debe tener formato Y-m-d y la hora debe ser futura y estar dentro del
horario de la pista. Cada reserva dura una hora. Una reserva cancelada no bloquea.

### Niveles y organizador

Un partido puede no tener límites, tener solo mínimo, solo máximo o ambos.
Si hay ambos, el mínimo no supera al máximo. Los campos ocultos de nivel se
ignoran cuando se crea una reserva privada.

El organizador ocupa una plaza desde la creación. Si se fija un rango, también
debe tener en su perfil un nivel compatible para ese deporte. Sin rango no se
exige indicar nivel. Es la misma regla que se aplica a los demás participantes.

### Entrada, salida y cancelación

`join`, `leave` y `cancel` bloquean y vuelven a leer la misma fila de reserva.
La comprobación del aforo usa un count actual en la base de datos después del
bloqueo. Se conserva el índice único existente de booking_players para impedir
duplicados. El aforo procede de sports.players_per_match, no de un número fijo.

- Entrar exige partido abierto, confirmado, futuro, plaza libre, nivel compatible
  y no estar ya apuntado. Repetir el alta produce un mensaje y no otra fila.
- Salir exige participar en un partido abierto, confirmado y futuro.
- Si sale el organizador, se cancela la reserva y se conserva la lista de
  participantes para el historial. Si sale otra persona, se libera su plaza.
- Nadie puede cancelar la reserva de otro usuario. La cancelación propia repetida
  es segura y conserva la fila. Un partido privado no acepta las rutas de partidos.
- Los partidos pasados, en curso o cancelados no permiten borrar participaciones
  mediante una petición directa. La interfaz y el servidor aplican la misma regla.

La búsqueda valida el tipo de los filtros y carga organizador, participantes,
club y deporte con sus relaciones. «Solo mi nivel» usa el nivel del deporte concreto.

## Pruebas: aislamiento y resultados

El phpunit.xml anterior tenía comentadas las variables de la base de pruebas.
Se fuerzan APP_ENV=testing, SQLite en memoria y un DB_URL vacío. Tests/TestCase
refuerza esta configuración antes de que RefreshDatabase pueda migrar y se detiene
si la aplicación no está en modo testing. Los tests existentes que preparaban
otra conexión manualmente se unifican con RefreshDatabase para ejecutar la batería
completa con el mismo mecanismo. Ningún test necesita usuarios o reservas reales.

El nuevo ExampleTest ya prepara sus tablas; no depende de que exista una base local.
Las pruebas de HTTP no requieren que Vite esté ejecutándose.

Se añaden 28 casos: 15 en BookingRulesTest y 13 en MatchRulesTest. Cubren importe,
propietario, duración, límites opcionales, organizador, horas cerradas/pasadas,
solapamientos parciales, cancelaciones, aforo, repetición, nivel, participantes,
historial, búsqueda y accesos directos de invitados o no participantes.

| Revisión | Estado |
| --- | --- |
| Base de partida, antes de 07: ClubAvailability/MapPage/FrontendScreens/FrontendAsset | Joy confirmó 17 pruebas y 123 aserciones correctas en Lenovo |
| Base de partida: availability.test.mjs | Joy confirmó 6 pruebas correctas |
| Cancelar en otra pestaña y recuperar disponibilidad | Confirmado manualmente por Joy |
| Pruebas de 07 y ejecución global | Joy confirmó 53 pruebas y 262 aserciones correctas, 2,38 s |
| Recorrido crear partido, unirse con otra cuenta y salir | Confirmado por Joy; no sustituye los otros casos manuales |
| Revisión de código, XML y contrato de instalación | Ejecutada al empaquetar; no sustituye PHPUnit |
| Solicitudes realmente paralelas en PostgreSQL | Pendientes; SQLite secuencial no prueba el bloqueo de filas |
| Pago de prueba | Pendiente del bloque final |

Ejecutar en el Lenovo, dentro del proyecto, después de instalar:

```powershell
php artisan config:clear
php artisan view:clear
php artisan test
```

Registrar la salida real en docs/06-proves.md y en el registro IA. Si aparece la
protección APP_ENV, limpiar configuración y repetir; no desactivar esa protección.

## Prueba manual con datos ficticios

1. Abrir un partido sin límites y comprobar que el organizador ocupa una plaza.
2. Con otra cuenta, entrar una vez y verificar el aforo del deporte; intentar
   superar las plazas o repetir la petición debe producir un mensaje.
3. Probar un rango compatible e incompatible con el nivel del perfil. También
   crear partidos con solo mínimo o solo máximo.
4. Salir como participante y comprobar que se libera una plaza. Salir como
   organizador debe cancelar para todos, liberar la hora y conservar el historial.
5. Repetir una reserva del mismo slot con otra cuenta; solo una debe confirmarse.
6. En el entorno de prueba PostgreSQL, enviar simultáneamente dos creaciones de
   un mismo slot y dos altas a la última plaza. Comprobar filas y aforo después.

## Explicación breve para la defensa

El controlador recibe la petición y valida datos. La transacción agrupa los cambios
para que se confirmen completos o se deshagan. El bloqueo es como un turno sobre
una fila compartida: la pista al reservar y el partido al cambiar participantes.
Después del turno se consulta la información actual y se decide. El servidor
controla el precio y los permisos aunque se modifique el formulario del navegador.

## Continuidad

La revisión también detectó trabajo pendiente para los próximos paquetes:
comprobar rol manager al asignar clubes; proteger historial ante borrados de
clubes, pistas o usuarios; revisar cambios de aforo; validar los IDs de deporte
del perfil; añadir fotografía persistente. El pago de prueba sigue al final.
El frontend aprobado se conserva y no se añaden migraciones en 07.

Fuentes oficiales revisadas el 23/11/2026:
- https://laravel.com/docs/12.x/queries#pessimistic-locking
- https://laravel.com/docs/12.x/database#handling-deadlocks
- https://laravel.com/docs/12.x/validation
- https://laravel.com/docs/12.x/database-testing
- https://docs.phpunit.de/en/11.5/configuration.html#the-env-element


## Cierre e integración

- [Commit de Joy ecbebfd](https://github.com/adminpam/reserva-pistes/commit/ecbebfd).
- [Main integrado cf81124](https://github.com/adminpam/reserva-pistes/commit/cf811246e619e52108bee341e8a8735e0acb1491).
- Concurrencia PostgreSQL y otros casos manuales no confirmados siguen pendientes.
- La administración se prepara en 08; perfil/avatar y pago aún no están terminados.
