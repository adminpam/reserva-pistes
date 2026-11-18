# 06 · Pruebas

## Pruebas automáticas (PHPUnit)

Antes de ejecutar toda la suite con `php artisan test`, fijar una base exclusiva
de pruebas y revisar los tests heredados. No ejecutar `RefreshDatabase` contra una
base de desarrollo con datos que deban conservarse. Los nuevos `MapPageTest` y
`FrontendScreensTest` preparan expresamente SQLite en memoria.

| Test | Qué comprueba |
|---|---|
| `BookingOverlapTest::test_no_se_puede_reservar_un_slot_ocupado` | la regla anti-solapamiento rechaza la segunda reserva del mismo slot |
| `BookingOverlapTest::test_un_slot_cancelado_se_puede_volver_a_reservar` | cancelar libera el slot |
| `RoleAccessTest::test_un_jugador_no_entra_en_los_paneles` | middleware de roles → 403 |
| `RoleAccessTest::test_cada_rol_entra_en_su_panel` | manager y admin acceden a su panel |
| `RoleAccessTest::test_un_invitado_va_al_login` | rutas protegidas redirigen al login |

Resultado de la última ejecución: _pendiente (pegar salida de `php artisan test`)_.

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
