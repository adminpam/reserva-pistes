# 06 · Pruebas

## Pruebas automáticas (PHPUnit)

Ejecutar: `php artisan test`

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
| `MapPageTest` del paquete 02 | Pendiente en el ASUS; emplea una SQLite nueva en memoria. |
| Portada a 320/390/768/1024/1440/1920 px | Pendiente en navegador del equipo. |
| Selección de filtro, marcador y ficha móvil | Pendiente de comprobar en Laravel. |
| Render con los paquetes nuevos | Pendiente de desplegar y probar. |

Las pruebas de contrato no sustituyen la validación visual ni certifican el
estado de las demás funciones del proyecto.
