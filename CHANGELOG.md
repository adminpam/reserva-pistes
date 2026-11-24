# Changelog

## [Sin publicar · backend 10] — 2026-11-24
- Pago interno de prueba con retención temporal; solo aprobar confirma una reserva.
- Rechazo, cancelación, caducidad y reintento con nueva tarifa explícita.
- Resultado y justificante privados, repeticiones sin duplicar y reversión al cancelar.
- Disponibilidad común de reservas/retenciones y protección de historial.
- Migración aditiva con dos tablas; 31 pruebas PHP nuevas (151 declaradas), pendientes en Lenovo.
- Diez pruebas Node nuevas y 40 en toda la carpeta Frontend aprobadas.
- Joy confirmó 09: 120 pruebas/872 aserciones, ocho Node y recorrido manual completo.
- Perfil a423786 revisado e integrado por Denys según Joy; despliegue final pendiente.

## [Sin publicar · backend 09] — 2026-11-23
- Perfil y niveles guardados juntos, con validación de los IDs de deportes.
- Subida, sustitución y eliminación de avatar pequeño en la base de datos.
- Vista previa y preparación de miniatura; fotos en cabecera, perfil, admin y Mis partidos.
- Cambio de contraseña, rotación de token y control de sesiones privadas.
- Normalización de correo y límites de intentos en acceso, registro y cambios de cuenta.
- Una migración aditiva; 37 pruebas PHP pendientes en Lenovo y 8 pruebas Node aprobadas.
- Diseño de acceso/registro conservado; aceptación visual de 09 pendiente.

## [Sin publicar · backend 08] — 2026-11-23
- Asignación exclusiva a gestores; protección de clubes asignados al cambiar roles.
- Borrados condicionados para conservar reservas y participaciones de cualquier fecha/estado.
- Conservación de club/deporte de pistas con reservas y de horarios ya comprometidos.
- Aforo estable desde el primer partido; validación de slugs, búsquedas y precios.
- Bloqueos compartidos entre administración, gestión, reservas y altas a partidos.
- 30 regresiones HTTP añadidas; Joy confirmó batería global de 83 pruebas/534 aserciones y el recorrido manual de gestor/historial.
- Revisado por Denys e integrado en PR #21 / main b2a8895.

## [Sin publicar · backend 07] — 2026-11-23
- Bloqueo de la pista al reservar y del partido al entrar, salir o cancelar.
- Niveles mínimo/máximo opcionales; rango compatible también con el organizador.
- Precio y propietario controlados por servidor; historial conservado en cancelaciones.
- Protección de partidos privados, pasados/cancelados y participación de otros usuarios.
- Aislamiento de toda la batería con SQLite en memoria y 28 regresiones nuevas.
- Joy confirmó 53 pruebas y 262 aserciones correctas y el recorrido crear/unirse/salir.
- Commit ecbebfd integrado en main cf81124 tras revisión de Denys; concurrencia PostgreSQL pendiente.

## [Sin publicar] — actualización 2026-11-18
- Compatibilidad de la constante SSL de PDO entre PHP 8.2 y 8.5 (ajuste 04a).
- Disponibilidad de pistas al recuperar el foco, con cálculo común entre Blade y JSON.
- Pruebas y documentación de los datos locales, importe y cancelación comprobados por Joy.
- Microanimaciones breves con movimiento reducido y documentación de instalación/manual según AEA6.
- Resultado confirmado de FrontendScreensTest: 6 tests, 58 aserciones; continuidad con Joy y pago al final.
- Composición común de perfil, club, reservas, partidos, gestor y administrador.
- Cabecera fija sin scroll interior; mapa móvil con lista inferior y ordenación.
- Confirmaciones accesibles, paginación propia, vacíos y errores 403/404/419/500/503.
- Plan de continuidad para Joy y simulación de pago pendiente de la fase de backend.
- Portada según la referencia: cabecera con símbolo original, panel de deportes y mapa ancho.
- Estructuras de tablet y teléfono, ficha móvil inferior, estados de carga, vacío y error.
- Validación del contenido de los marcadores y corrección de los SVG del sonido.
- Marca con el PNG original y cargador basado en la referencia del equipo.
- Entrada estática opcional que espera a Laravel antes de abrir la aplicación.
- Accesibilidad con movimiento reducido, espera limitada y reintento.

## [Sin publicar · frontend 06] — 2026-11-19
- Sustitución de la hoja móvil: mapa, ficha seleccionada y listado a pantalla completa.
- Corrección de la ficha oculta y navegación desde el marcador al club.
- Acción de invitado en una fila propia, con ancho completo en teléfono.
- Versionado de recursos para evitar estilos y scripts obsoletos después de publicar.
- Registro de la entrada estática publicada en Render; aceptación en reposo pendiente.

## [Sin publicar · frontend 05] — 2026-11-19
- Cuadrículas adaptables de pistas, acciones, fecha y disponibilidad.
- Mapa móvil con filtros en una fila y panel Mapa/Ambos/Lista, arrastre y teclado.
- Conservación del acceso a clubes si falla Leaflet; precios legibles y margen de login.
- Validaciones del constructor estático y guía de configuración de Render.

## [v1.0] — Pendiente de publicar

Fecha y etiqueta por confirmar después de la revisión del backend, el pago de
prueba, el despliegue final y la documentación. La anotación anterior con fecha
04/12/2026 era una previsión; no acredita una versión publicada. El servicio
existente utiliza Render, no Laravel Cloud.

## [v0.1] — 2026-11-14
Esqueleto funcional del proyecto.
- Laravel 12 con `platform.php = 8.2.0` (compatibilidad Herd/XAMPP).
- Modelo de datos completo (7 tablas) con seeders de deportes y usuarios demo.
- Autenticación con sesiones y middleware de roles (player/manager/admin).
- Base visual: layout, paleta propia, CSS sin frameworks, SVG provisionales.
- Mapa Leaflet con clubes y filtro por deporte; ficha de club con reserva de slots de una hora sin solapamientos (pago simulado).

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
