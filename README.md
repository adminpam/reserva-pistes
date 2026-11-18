# ReservaPistes 🏸

Aplicación web para **reservar pistas deportivas** y **encontrar compañeros de juego** en la zona Sabadell–Terrassa–Barcelona. Proyecto del módulo **M0617 Programación asistida con IA** (AEA6 «Vibe Coding»), IES Sabadell.

**Autores:** Denys Peresunko (@adminpam) y Joy Liliana Castro Valencia (@joyvalencia)

> ⚠️ Las reservas y pagos son **simulados**: es un proyecto educativo, no se cobra nada.

## Qué hace
- Mapa de **clubes reales** (importados de OpenStreetMap) con filtro por deporte.
- Reserva de slots de una hora con comprobación transaccional de solapamientos; la concurrencia real se revisa en la fase final del backend.
- **Partidos abiertos**: plazas según el deporte (tenis de mesa 2, fútbol sala 10…), rango de nivel 1–5 opcional, apuntarse/salir.
- Perfil con nivel por deporte; «Mis reservas» y «Mis partidos» con cancelación.
- Panel de **gestor de club** (CRUD de pistas, reservas del club) y panel de **admin** (clubes, deportes, usuarios).
- Tiempo actual en pistas exteriores (Open-Meteo). Todo **sin claves de API**.

## Stack
Laravel 12 · Blade + CSS propio · JS vanilla + Leaflet/OpenStreetMap · SQLite (local) / PostgreSQL previsto para producción (configuración final pendiente) · Vite

## Herramientas de IA utilizadas
- **Claude (Anthropic):** generación inicial guiada de modelos, controladores,
  autenticación, pruebas y documentos, según el registro del equipo.
- **ChatGPT (OpenAI):** revisión del código aportado, rediseño de marca y pantallas,
  CSS/JS adaptable, pruebas de contrato y vistas, depuración y documentación.

Registro de prompts, resultados y enlaces: [`docs/registre-ia.md`](docs/registre-ia.md).
Los enlaces pendientes se completan al publicar los cambios. Regla del equipo:
solo entra en `main` código que al menos uno de los dos pueda explicar y que el
otro haya revisado. El pago con formulario de prueba todavía está pendiente.

## Puesta en marcha local

Seguir [`docs/04-installacio.md`](docs/04-installacio.md): descargar el código,
instalar dependencias desde los locks, configurar `.env`, migrar la SQLite local
y arrancar Laravel/Vite. El documento distingue una instalación nueva de la
sincronización de un repositorio que ya contiene trabajo.

Los usuarios demo son ficticios, solo para desarrollo. Sus datos se encuentran
en `database/seeders/UserSeeder.php`; no ejecutar el seeder de usuarios
automáticamente sobre producción.

## Producción
Desplegado en **Render** con Docker: https://reserva-pistes.onrender.com/.

La pantalla de carga y la entrada estática opcional están documentadas en [`docs/07-marca-y-carga.md`](docs/07-marca-y-carga.md). El despliegue de la versión nueva, la rama de Render y la persistencia de la base se verificarán con Denys. El documento adicional `docs/05-desplegament.md` indica ese estado; no se presenta un plan como despliegue realizado.

## Documentación

- [`01-proposta.md`](docs/01-proposta.md): propuesta y originalidad.
- [`02-requisits.md`](docs/02-requisits.md): usuarios, roles y aceptación.
- [`03-disseny.md`](docs/03-disseny.md): modelo de datos, arquitectura y API.
- [`04-installacio.md`](docs/04-installacio.md): instalación local paso a paso.
- [`05-manual-usuari.md`](docs/05-manual-usuari.md): uso por rol y capturas pendientes.
- [`06-proves.md`](docs/06-proves.md): pruebas, resultados reales y golden test.
- [`registre-ia.md`](docs/registre-ia.md): usos significativos de IA.
- [`CHANGELOG.md`](CHANGELOG.md): v0.1 y preparación de v1.0.

## Capturas
Pendientes de la versión desplegada: mapa en escritorio/teléfono, reserva, jugador, gestor y administrador. Se guardarán en `docs/captures/` y se insertarán aquí y en el manual. Se usarán usuarios ficticios; no se piden capturas del proceso de trabajo.

## Interfaz y continuidad del equipo

El estilo de las pantallas y su comprobación están en [`docs/09-frontend.md`](docs/09-frontend.md).
El traspaso a Joy, la sincronización por GitHub y la simulación de pago pendiente
están en [`docs/10-traspaso-joy.md`](docs/10-traspaso-joy.md). La interfaz preparada
requiere validación en los equipos y no implica que la revisión final del backend
ni el despliegue de esta versión estén completados.
