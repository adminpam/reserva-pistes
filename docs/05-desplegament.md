# Documento adicional · Despliegue en Render

Este archivo conserva su nombre para no romper referencias antiguas. El documento
obligatorio número 05 de la práctica es [`05-manual-usuari.md`](05-manual-usuari.md).

## Estado real comunicado

- URL existente: https://reserva-pistes.onrender.com/.
- El panel compartido por Denys muestra un servicio Docker en Frankfurt.
- El frontend nuevo se ha instalado y probado parcialmente en su ASUS. Su
  publicación en Render no se ha confirmado.
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
