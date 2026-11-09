# Propuesta inicial: ReservaPistes

**Autores:** Denys Peresunko y Joy
**Módulo:** M0617 Programación asistida con IA (Vibe Coding), IES Sabadell

## Descripción

ReservaPistes es una aplicación web para reservar pistas y salas deportivas (pádel, tenis, baloncesto, sala de fitness...). Los usuarios registrados ven la disponibilidad de cada instalación y reservan franjas de una hora. El administrador gestiona las instalaciones y puede ver y cancelar todas las reservas.

## Problema que resuelve

Reservar pistas por teléfono o en papel provoca reservas dobles y mucho trabajo para el responsable. Queremos una web donde se vea la disponibilidad y se reserve en pocos clics.

## Roles

- **Usuario registrado:** consulta la disponibilidad, reserva, ve y cancela sus reservas.
- **Administrador:** gestiona las instalaciones y todas las reservas.

## Funcionalidades mínimas

- Registro e inicio de sesión.
- Listado de instalaciones y disponibilidad por fecha.
- Reservar una franja de una hora sin solapamientos.
- Ver y cancelar mis reservas.
- Administrador: crear, editar y desactivar instalaciones, y ver y cancelar cualquier reserva.

## Funcionalidades opcionales

- Filtro por deporte.
- Límite de reservas por usuario y día.
- Calendario semanal.

## Datos

Tres tablas principales: usuarios, instalaciones y reservas. Un usuario tiene muchas reservas y una instalación tiene muchas reservas.

## Tecnologías

- Backend: Laravel.
- Frontend: plantillas Blade con CSS propio (o Tailwind).
- Base de datos: MySQL o PostgreSQL en producción, según la plataforma gratuita de despliegue (se decide antes de programar).
- Control de versiones: GitHub, con ramas por funcionalidad y pull requests revisados por la otra persona.
- Herramienta de IA: Claude.

## Plan de trabajo (12 horas)

1. Preparación (1 h): repositorio, colaboradores y proyecto Laravel.
2. Base de datos y autenticación (2 h).
3. Gestión de instalaciones (2 h).
4. Reservas (3 h).
5. Pruebas (1,5 h).
6. Despliegue (1 h).
7. Documentación y presentación (1,5 h).

## Riesgos

- El despliegue gratuito de Laravel: lo decidimos al empezar, con la documentación oficial de la plataforma.
- Los solapamientos de reservas: los cubrimos con pruebas automáticas.
