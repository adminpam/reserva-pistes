# 06 · Pruebas

## Pruebas automáticas (PHPUnit)

El paquete 07 fija SQLite en memoria en phpunit.xml y Tests/TestCase, antes de
que RefreshDatabase ejecute migraciones. Las pruebas de datos usan el mismo
mecanismo y no dependen de la base local. Primero limpiar configuración y después
ejecutar la suite completa en el Lenovo:

```powershell
php artisan config:clear
php artisan test
```

El paquete 07 añadió 28 casos de reservas/partidos. Joy confirmó la ejecución
global de 07: **53 pruebas y 262 aserciones correctas**, en 2,38 s. Los apartados fechados anteriores se conservan como histórico.

| Test | Qué comprueba |
|---|---|
| `BookingOverlapTest::test_no_se_puede_reservar_un_slot_ocupado` | la regla anti-solapamiento rechaza la segunda reserva del mismo slot |
| `BookingOverlapTest::test_un_slot_cancelado_se_puede_volver_a_reservar` | cancelar libera el slot |
| `RoleAccessTest::test_un_jugador_no_entra_en_los_paneles` | middleware de roles → 403 |
| `RoleAccessTest::test_cada_rol_entra_en_su_panel` | manager y admin acceden a su panel |
| `RoleAccessTest::test_un_invitado_va_al_login` | rutas protegidas redirigen al login |

Última ejecución confirmada antes de 08: **53 pruebas, 262 aserciones, sin fallos**
en Lenovo (23/11/2026, commit de trabajo ecbebfd). La ejecución del código de 08
está pendiente; los apartados fechados siguientes conservan el histórico.

## Pruebas manuales

| # | Caso | Pasos | Esperado | Resultado |
|---|---|---|---|---|
| M1 | Registro | Crear cuenta nueva | Entra como jugador | pendiente |
| M2 | Mapa | Filtrar por «Tenis» | Solo clubes con pistas de tenis | pendiente |
| M3 | Reserva | Reservar slot libre | Flash verde, aparece en «Mis reservas» | pendiente |
| M4 | Solapamiento | Con otro usuario, mismo slot | Error «acaba de ser reservado» | pendiente |
| M5 | Partido abierto | Crear con nivel 2–4; apuntarse con nivel 5 | Rechazo por nivel | pendiente |
| M6 | Plazas | Llenar un partido de tenis (2) | Tercero ve «Completo» | pendiente |
| M7 | Cancelación de organizador | Organizador sale del partido | Partido cancelado para todos | pendiente |
| M8 | Gestor | Crear/editar/borrar pista propia | OK; pista ajena → 403 | pendiente |
| M9 | Admin | Cambiar rol de un usuario | Rol cambiado; no puede quitarse admin a sí mismo | pendiente |
| M10 | Import | `php artisan clubs:import` dos veces | Clubes reales, sin duplicados | pendiente |
| M11 | Tiempo | Club con pista exterior | Temperatura actual visible | pendiente |
| M12 | Producción | Abrir la URL desplegada | La app funciona igual que en local | pendiente |

La columna «Resultado» se rellena durante la semana de pruebas con ✅/❌ y fecha.

## Portada y carga · seguimiento del 17/11/2026

| Comprobación | Resultado registrado |
| --- | --- |
| `npm run build` del paquete 01 | Correcto en ASUS de Denys, según salida compartida. |
| `php artisan view:clear` del paquete 01 | Correcto en ASUS de Denys. |
| `BrandReadyTest` | Aprobado en ASUS: 1 test, 5 aserciones. |
| Pista y pelota del cargador | Aprobadas visualmente por Denys; nombre y barra corregidos en 01b. |
| `map-data.test.mjs` del paquete 02 | 5 pruebas aprobadas al preparar el código. |
| `MapPageTest` del paquete 02 | Aprobado en ASUS: 2 tests, 15 aserciones; SQLite en memoria. |
| Portada a 320/390/768/1024/1440/1920 px | Pendiente en navegador del equipo. |
| Selección de filtro, marcador y ficha móvil | Pendiente de comprobar en Laravel. |
| Render con los paquetes nuevos | Pendiente de desplegar y probar. |

Las pruebas de contrato no sustituyen la validación visual ni certifican el
estado de las demás funciones del proyecto.

## Continuación frontend · paquete 03

Denys confirmó en el ASUS: build y limpieza de vistas de 02 correctos;
`MapPageTest` aprobado con 2 tests y 15 aserciones. La revisión visual de la
portada pidió únicamente quitar la barra interior del menú y fijar la cabecera.

| Comprobación | Estado al preparar 03 |
| --- | --- |
| Confirmación de envío/cancelación en Node | 3 pruebas aprobadas. |
| Contrato JSON y contenido seguro del mapa | 5 pruebas Node aprobadas con el módulo actualizado. |
| `FrontendScreensTest` | Aprobado en ASUS de Denys: 6 tests, 58 aserciones, 1,31 s. |
| Login/registro y cargador aprobados | Sus archivos no se incluyen en las sustituciones de 03. |
| Cabecera fija sin scroll interno | Implementado; pendiente de observación en el navegador del equipo. |
| Perfil, club, reservas y partidos | Implementados; pendientes de revisión visual y flujos reales. |
| Gestor y administrador | Vistas/formularios implementados; revisión funcional con Joy pendiente. |
| Pago con datos ficticios | Pendiente con Joy; no implementado en 03. |
| Publicación en Render | Pendiente con Denys. |

No usar datos de tarjetas ni datos personales reales en el registro de pruebas.


## Cierre preliminar del frontend · 18/11/2026

Denys comunicó que la interfaz está terminada de forma preliminar. El test PHP de
pantallas pasó en el ASUS; sus seis casos comprueban vistas pobladas/canceladas,
vacíos, contratos de formularios, paneles, errores/auth y paginación. No prueban por
sí solos la concurrencia, el pago ni un recorrido real en Render.

03b añade movimiento breve en CSS, limitado por `prefers-reduced-motion`, y
documentación. Comprobar con Ctrl+F5: entrada discreta de tarjetas, foco visible,
selección de horas y diálogo; repetir con movimiento reducido activado. Pendiente
en el navegador del equipo, sin declarar pruebas visuales que no se han observado.

## Golden test de aceptación final

| Paso | Acción con datos ficticios | Resultado esperado | Resultado observado |
| --- | --- | --- | --- |
| G1 | Admin asigna un club a un gestor; gestor crea una pista | Solo el gestor asignado puede modificarla | Pendiente con Joy |
| G2 | Jugador localiza el club y elige hora futura libre | Disponibilidad e importe coinciden con servidor | Pendiente con Joy |
| G3 | Completar pago de prueba aprobado | Una sola reserva y un solo pago demo, incluso al repetir envío | Pendiente tras implementar pago |
| G4 | Otro jugador solicita el mismo hueco | Rechazo de solapamiento, también con envíos simultáneos | Pendiente con Joy |
| G5 | Crear partido, unirse, intentar superar aforo o nivel | Límites validados en servidor | Pendiente con Joy |
| G6 | Salir y cancelar; consultar historial | Plazas/horarios coherentes y estados conservados | Pendiente con Joy |
| G7 | Repetir en URL final, móvil y tablet; reiniciar servicio | Aplicación operativa y datos conservados | Pendiente con Denys |

Anotar fecha, autor y commit desplegado al completar cada caso. La última ejecución
global y las capturas por rol siguen pendientes, aunque existan tests parciales verdes.


## Backend con Joy · 18/11/2026

| Comprobación en Lenovo | Resultado comunicado |
| --- | --- |
| Actualizar main desde GitHub | Correcto: fast-forward a `8a8b39c` |
| Composer y requisitos de PHP | Correctos; PHP CLI 8.5.11 |
| npm ci y npm run build | Correctos; aviso de postinstall de esbuild bloqueado, build funcional |
| Estado de migraciones | Todas aplicadas en SQLite local |
| Datos antes de completar pistas | 6 deportes, 11 clubes, 4 pistas, 0 reservas |
| CourtSeeder | 29 pistas; 0 clubes sin pistas después de ejecutarlo |
| Registro/acceso usado en prueba | Sin error comunicado; sesión iniciada observada en la captura |
| Ficha de club | Pistas, precios y horarios visibles |
| Importe de la reserva | Visible en Mis reservas, confirmado por Joy |
| Cancelación de reserva futura | Cancelada; la hora queda libre al consultar la ficha de nuevo |
| Actualización automática entre pestañas | Paquete 04 preparado; instalación/prueba manual nueva pendientes |
| ClubAvailabilityTest | Ejecutados en Lenovo: 7 tests, 45 aserciones, sin fallos; 2 avisos de PHP 8.5 en config/database.php |
| Regresión Node del paquete 04 | 14 pruebas aprobadas al preparar el código: 6 nuevas, 8 anteriores |

Las comprobaciones manuales anteriores no acreditan los casos negativos de registro,
los permisos, las reservas simultáneas, el aforo o el pago. Se revisarán por separado.
Detalle y explicación de 04: `11-datos-y-disponibilidad.md`.


## Compatibilidad PHP · ajuste 04a

Joy ejecutó `ClubAvailabilityTest` con PHP 8.5.11 y PHPUnit 11.5.57:
7 tests, 45 aserciones. PHPUnit identificó dos avisos de deprecación del mismo
símbolo `PDO::MYSQL_ATTR_SSL_CA`, en `config/database.php`, líneas 61 y 81 de la
versión anterior. Las aserciones se completaron, pero la ejecución no estaba limpia.

04a usa `Pdo\Mysql::ATTR_SSL_CA` cuando está definida, y la constante anterior
como alternativa para PHP 8.2. Conserva el valor de la opción SSL y el control de
extensión disponible. No desactiva los avisos del runner ni modifica `vendor/`.

Repetir después de aplicar 04a, en el Lenovo dentro del proyecto:

```powershell
php artisan config:clear
php vendor/phpunit/phpunit/phpunit --filter=ClubAvailabilityTest --display-deprecations
```

Resultado después del ajuste: pendiente de la ejecución de Joy. No marcar los
avisos como solucionados hasta observar la nueva salida.


## Integración confirmada con Joy · 23/11/2026

- ZIP de referencia: commit fbbf879fbf56967ccd07e716a224799205455ac9.
- Main dd20e67 incorporado a feat/backend-datos sin conflictos. Denys revisó el PR
  según la confirmación de Joy; no se ha recibido aquí su URL ni una confirmación
  separada del merge de ese PR.
- Lenovo: ClubAvailabilityTest, FrontendAssetTest, FrontendScreensTest y MapPageTest:
  **17 pruebas, 123 aserciones, sin fallos**, duración 1,87 s.
- availability.test.mjs: **6 pruebas correctas**, duración 97,8 ms.
- Joy comprobó la cancelación y que la hora vuelve a estar disponible al regresar
  a la ficha del club durante la prueba entre pestañas.
- Denys confirmó la publicación del frontend 06 y la corrección de los errores.

## Regresión de reservas y partidos · 07

| Archivo | Casos | Alcance |
| --- | --- | --- |
| BookingRulesTest | 15 | Precio/propietario, duración, límites opcionales, organizador, fechas/horas, solapamiento, cancelación y acceso |
| MatchRulesTest | 13 | Última plaza, aforo del deporte, repetición, nivel, alta/baja, cancelación, historial, búsqueda y permisos |

Estado de ejecución de 07: **confirmado por Joy**, 53 pruebas y 262 aserciones,
2,38 s, sin fallos. Joy confirmó también crear partido, entrar con otra cuenta,
verlo en Mis partidos y salir liberando plaza. Denys revisó; merge confirmado en
main cf81124. No se recibió aquí el número del PR; se enlaza el commit real. SQLite comprueba reglas y transacciones secuenciales, pero no acredita
la prueba simultánea con bloqueo de filas de PostgreSQL/MySQL. Esa comprobación
se documentará al preparar el despliegue con Denys.


## Administración y gestor · 08 · 23/11/2026

Base: main cf81124, después de integrar 07. Resultado previo confirmado:
53 pruebas/262 aserciones y recorrido manual crear/unirse/salir. Solo los casos
confirmados se consideran realizados; el golden test completo sigue abierto.

| Archivo nuevo | Casos | Alcance |
| --- | --- | --- |
| AdminManagementTest | 17 | Escrituras por rol, invitados, asignación/reasignación, roles, bajas, historial, clubes, slugs, aforo y búsqueda |
| ManagerManagementTest | 13 | Propiedad, accesos directos, crear/editar, precio y céntimos, destino/deporte, horarios futuros/en curso, medianoche y borrado |

Las 30 pruebas nuevas están **pendientes en Lenovo**. Son 83 casos declarados en
la batería completa de esta copia, no 83 pruebas ejecutadas ni aprobadas.
Al preparar el ZIP se comprueban estructura, integridad e instalación sobre copia
CRLF, reinstalación y rechazo de deriva local. No hay PHP/Composer en ese entorno.
Ejecutar php artisan config:clear, php artisan view:clear y php artisan test.
Registrar la salida real después, junto con el recorrido de la guía 15.

Quedan pendientes las carreras reales en PostgreSQL, la aceptación manual de 08,
perfil/avatar, pago de prueba y aceptación final del despliegue con Denys.

## Cierre de validación del paquete 08 — Joy — 23/11/2026
- Batería completa: 83 pruebas y 534 aserciones correctas, en 3,33 s.
- Comprobados manualmente: asignación de gestor, creación y edición de pista.
- La reserva conserva 12 euros aunque la tarifa cambie a 15 euros.
- El borrado de la pista queda bloqueado antes y después de cancelar la reserva.
- Revisión de Denys y prueba de concurrencia en PostgreSQL pendientes.


## Integración confirmada de 08 y preparación de 09 · 23/11/2026

Joy confirmó 83 pruebas y 534 aserciones correctas en 3,33 s. También asignó un
gestor, creó/editó una pista, reservó a 12 euros y comprobó que se conservan tras
subir la tarifa a 15. El borrado siguió bloqueado antes y después de cancelar.
Commit 25b3ccc, revisado por Denys, integrado en PR #21 / main b2a8895.
La prueba paralela de PostgreSQL y el despliegue de ese commit siguen sin confirmar.

| Casos nuevos de 09 | Cantidad | Estado |
| --- | --- | --- |
| ProfileRulesTest | 8 | Pendientes en Lenovo |
| AvatarTest | 11 | Pendientes en Lenovo; imágenes sintéticas PNG/JPEG/WebP |
| PasswordChangeTest | 8 | Pendientes en Lenovo |
| AccountAccessTest | 10 | Pendientes en Lenovo |
| profile.test.mjs | 8 | Aprobados al preparar el paquete |

La copia integrada tiene 120 casos PHP declarados, no ejecutados en este entorno:
no dispone de PHP/Composer. Tampoco se completó la comprobación Chromium porque
faltaba un ejecutable funcional. La revisión visual y la miniatura en un navegador
real siguen pendientes con Joy; las pruebas Node usan dobles del DOM/canvas.

El instalador se comprueba sobre copia CRLF, con backup, reinstalación y rechazo
de cambios locales. Las migraciones y PHP se ejecutarán en Lenovo; antes, limpiar
configuración y aplicar únicamente las migraciones pendientes con artisan migrate.
El golden test completo y el pago no están terminados. Guía de aceptación de este
bloque: [16-perfil-cuentas-y-foto.md](16-perfil-cuentas-y-foto.md).

## Cierre de validación del paquete 09 — Joy — 24/11/2026
- Migración de avatares aplicada correctamente.
- Batería PHP: 120 pruebas y 872 aserciones correctas, en 13,43 s.
- JavaScript del perfil: 8 pruebas correctas.
- Nombre, niveles y foto se guardan y se conservan tras cerrar sesión.
- Sustituir y quitar la foto funciona.
- La contraseña actual incorrecta se rechaza; la correcta permite cambiarla.
- La sesión actual continúa y las otras requieren volver a entrar.
- La contraseña anterior falla y la nueva permite acceder.
- Revisión de Denys, persistencia tras redesplegar y concurrencia PostgreSQL pendientes.
