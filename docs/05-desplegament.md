# Documento adicional · Despliegue en Render

Este archivo conserva su nombre para no romper referencias antiguas. El documento
obligatorio número 05 de la práctica es [`05-manual-usuari.md`](05-manual-usuari.md).

## Estado real comunicado

- URL existente: https://reserva-pistes.onrender.com/.
- El panel compartido por Denys muestra un servicio Docker en Frankfurt.
- Denys confirmó la publicación del frontend 06 y la corrección del mapa móvil.
  Esto no confirma qué commit de backend está servido actualmente.
- El despliegue final lo realizará Denys, después de cerrar el backend con Joy.

El plan anterior de Laravel Cloud no describe el servicio utilizado y no debe
ejecutarse como si fuese la configuración actual. Para la revisión final se
contrastarán el `Dockerfile`, los scripts de `docker/`, la rama conectada y el
commit realmente desplegado.

## Decisiones y comprobaciones pendientes

1. Confirmar cómo se inicia el contenedor, se compilan assets y se ejecutan
   migraciones sin borrar la base ni sembrar contraseñas demo en cada arranque.
2. Confirmar base persistente, modalidad gratuita sin tarjeta y disponibilidad
   durante la presentación. El filesystem del servicio Free es efímero y el
   Postgres Free de Render expira a los 30 días; no basta con que el sitio abra hoy.
3. Comprobar variables de producción en Render sin copiarlas al chat o al repositorio:
   entorno de producción, depuración desactivada, URL HTTPS y conexión de base.
4. Coordinar merge y despliegue con Denys, observar logs y probar los tres roles,
   reserva, conflicto, partido y pago simulado en la URL final.
5. Registrar fecha, commit desplegado y resultados en `06-proves.md`; completar
   README, capturas y v1.0 solo después de verificarlo.

La entrada estática del cargador es opcional y está descrita en
[`07-marca-y-carga.md`](07-marca-y-carga.md). Instalar sus archivos no despliega
automáticamente un segundo servicio.

Fuentes oficiales revisadas el 18/11/2026:

- https://render.com/docs/free
- https://render.com/docs/deploys


## Actualización · 24/11/2026 · antes del pago

Joy confirmó que Denys revisó e integró el perfil a423786. El pago 10 añade las
tablas booking_checkouts y demo_payments. El docker/entrypoint.sh recibido ya
ejecuta php artisan migrate --force antes de iniciar Apache; no incluye seeders.
No se cambia ese arranque en 10. Hay que observar que la nueva migración termine
en los logs del despliegue y que el servicio vuelva a responder.

Tras aceptar 10, verificar con Denys una reserva aprobada y su justificante,
un rechazo sin Booking, liberación por caducidad, cancelación/reversión y la
conservación de foto, cuenta, reserva y pago tras redesplegar. Probar peticiones
paralelas de dos usuarios a la misma hora en PostgreSQL y confirmar que solo una
consigue la retención; repetir la aprobación de una solicitud no debe duplicar
reserva ni resultado. Usar cuentas ficticias y documentar los resultados reales.
La base persistente y su vigencia deben cubrir la defensa. Nada de esto se da por
realizado al construir el ZIP o por haber fusionado la rama en GitHub.
