# Entrada estática en Render

Esta página es una entrada independiente para mostrar el cargador mientras el
Web Service de Laravel despierta. No sustituye la página interna de Render al
visitar directamente la URL del Web Service dormido. La entrada tendrá su propia
URL; cuando esté creada, será la que se comparta como acceso inicial al proyecto.

Laravel conserva su servicio y URL actuales. Los enlaces internos y las sesiones
continúan en ese origen después de la redirección.

## Comprobar la compilación en el ASUS de Denys

Desde la carpeta del repositorio:

```powershell
node scripts/build-loading-site.mjs
```

Se crea `loading-site/dist/index.html` y sus archivos en `brand/`. El comando usa
solo Node, sin instalar paquetes. El destino está en `loading-site/config.json`:
`https://reserva-pistes.onrender.com/`. No contiene claves.

El cargador consulta una imagen de 2×2 generada por `/brand/ready`. Una respuesta
HTML de Render no se interpreta como aplicación preparada. Cuando Laravel responde
con la imagen, se abre la app; si la espera se alarga, hay aviso, reintento y enlace
directo. El chequeo de esta imagen no necesita habilitar CORS para toda la aplicación.

## Crear el servicio estático con Denys

Publicar y revisar el PR de este paquete antes de elegir `main` para el nuevo servicio.
En Render: **New → Static Site**, conectar el repositorio `adminpam/reserva-pistes`
y completar estos campos:

| Campo | Valor |
| --- | --- |
| Nombre propuesto | `reserva-pistes-inicio`, si Render lo admite |
| Branch | `main` |
| Root Directory | Vacío |
| Build Command | `node scripts/build-loading-site.mjs` |
| Publish Directory | `loading-site/dist` |

En la configuración de entorno de ese Static Site, definir
`SKIP_INSTALL_DEPS=true`. Esta entrada no necesita las dependencias PHP/npm de la
aplicación. Crear el Static Site y observar su primer despliegue; no crear otro
Web Service para alojar este HTML.

No añadir una redirección inmediata de la raíz hacia Laravel: evitaría mostrar
el cargador durante la espera. La redirección la realiza el JavaScript cuando
la comprobación de Laravel tiene éxito.

## Aceptación

1. Copiar la URL exacta entregada por Render para el Static Site.
2. Abrirla en ventana privada: el cargador debe verse con su CSS y fuente.
3. Comprobar el caso con Laravel despierto y, después de su inactividad normal,
   el arranque desde reposo. No programar peticiones para mantenerlo despierto.
4. La entrada debe pasar al sitio real solo al recibir la imagen válida.
5. Revisar teléfono, movimiento reducido y el caso sin JavaScript.
6. Añadir la URL estática al README cuando realmente exista y esté verificada.

Estado del paquete: archivos preparados. El Static Site todavía no se ha creado
ni se declara desplegado por generar este ZIP.

Fuentes oficiales revisadas el 19/11/2026:

- https://render.com/docs/static-sites
- https://render.com/docs/free
