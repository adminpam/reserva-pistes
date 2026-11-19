# Changelog

## [Sin publicar] — actualización 2026-11-18
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
