# 22 · Respuesta JSON de avatares · Joy

Paquete 14-Joy · 26/11/2026 · base main 82db9ad, merge PR #27.
Rama: feat/avatar-json. Instalación: C:\Dev\reserva-pistes.

## Continuidad y reparto

Denys confirmó la aceptación de la versión publicada con los filtros de clubes
por todos los deportes elegidos, Limpiar/ayuda y fichas nuevas. La base de esa
entrega pasó 173 pruebas PHP/1407 aserciones y 62 Node; esos resultados pertenecen
al paquete 13 y no acreditan la ejecución de este paquete.

Este bloque prepara el backend para la foto circular con plus y autoguardado que
pidió Denys. Joy modifica únicamente AvatarController, un archivo nuevo de pruebas
y esta guía. El siguiente paquete de Denys modifica interfaz, CSS y JavaScript:
click en el círculo, preparar foto, guardar automáticamente y actualizar avatares.
Los archivos de ambos bloques no deben solaparse. Continúa el desarrollo acordado;
no se publica todavía el cierre final del proyecto.

## Contrato HTTP

Se mantienen las rutas de web.php con auth y auth.session. No hay rutas públicas
nuevas ni cambio del almacenamiento: la foto sigue en la base de datos.

| Petición | Respuesta |
| --- | --- |
| POST /profile/avatar sin Accept JSON | Redirección y mensaje de éxito anteriores |
| POST /profile/avatar con Accept: application/json | 200 y metadatos de la foto guardada |
| DELETE /profile/avatar con Accept JSON | 200 con url/version null e iniciales |
| Foto o referencia de versión inválida | 422 y errores de validación por campo |
| Referencia válida pero desactualizada | 409 y metadatos actuales, sin modificar la foto |
| Invitado o sesión invalidada con Accept JSON | 401 por los middleware existentes |

POST envía multipart/FormData, no una imagen base64 en JSON. El fetch enviará
Accept: application/json, el token CSRF de la página y credenciales del mismo
origen. No fijar Content-Type manualmente: el navegador añade el boundary.
DELETE puede usar JSON con el token CSRF en cabecera. La interfaz nueva no necesita
mostrar una acción Quitar foto; la ruta se conserva para compatibilidad.

Ejemplo de respuesta correcta (valores ilustrativos, no una foto real):

```json
{
  "message": "Foto de perfil actualizada.",
  "avatar": {
    "url": "https://ejemplo.test/users/1/avatar?v=HUELLA_SHA256",
    "version": "HUELLA_SHA256",
    "initials": "PD"
  }
}
```

La versión real es el SHA-256 de los bytes guardados, 64 caracteres hexadecimales.
La URL se genera en el servidor; no contiene base64 o credenciales. El objeto avatar
solo tiene url, version e initials. Las respuestas JSON 200/409 son private,
no-store y no crean mensajes flash. El GET autenticado de la imagen conserva su
ETag, MIME verificado y lectura conjunta de versión/bytes.

## Evitar sobrescrituras atrasadas

El nuevo cliente enviará siempre expected_avatar_version con la versión que ha
confirmado. Si esperaba no tener foto, manda vacío o null. El campo omitido mantiene
la compatibilidad con los formularios anteriores. No enviar el texto literal null.

La referencia se valida y se compara con el usuario recién leído dentro del mismo
lockForUpdate que guarda o elimina. Si ya cambió, no se escriben bytes ni versión:
se devuelve 409 con el estado actual. Un DELETE desactualizado tampoco elimina una
foto nueva. Dos pistas de trabajo:

1. Petición A esperaba V0; otra guardó V1. A llega tarde: devuelve conflicto y V1
   sigue guardada. Una referencia vacía tampoco puede reemplazar una foto existente.
2. El cliente recibe 409: puede usar la versión recibida para un reintento limitado
   de su selección más reciente, no para reintentar una selección antigua. Debe
   mostrar un mensaje si el conflicto continúa; no crear bucles de subida.

El siguiente cliente debe serializar los envíos, descartar preparaciones/respuestas
antiguas, conservar una selección nueva mientras termina la anterior y utilizar
la precondición en cada envío. Abortar fetch no garantiza cancelar PHP: la versión
protege los envíos que sí usan este contrato. Los clientes antiguos que omiten el
campo conservan su comportamiento anterior; no se atribuye protección optimista
a peticiones que no envían la precondición.

Los conflictos se prueban con referencias históricas y orden de peticiones. Eso
no equivale a ejecutar procesos simultáneos en PostgreSQL; esa prueba independiente
sigue sin acreditarse. Los locks existentes se conservan.

## Validación que se mantiene

JPEG, PNG y WebP verificados, máximo enviado 512 KB, entre 32 y 1600 píxeles por
lado. SVG/archivos falsos/grandes/pequeños se rechazan conservando la foto anterior.
El cliente posterior seguirá preparando una miniatura JPEG de 384 px, con límite
local previo de 10 MB. No se introduce un requisito de GD en el servidor.

El límite de subida sigue siendo diez intentos por minuto y cuenta intentos
inválidos y conflictos. Su respuesta conserva 422 con errors.avatar. No hay pagos,
proveedores o servicios externos de imágenes. user_id, name, role y otros campos
extra no eligen otra cuenta ni modifican el perfil. Solo se cambia el usuario
identificado por la sesión. Los controles CSRF de las rutas web se mantienen.

## Pruebas y estado de preparación

11 casos nuevos en AvatarJsonTest: metadatos/URL/no flash, sustitución y fila única,
subida tardía, referencia inicial vacía contra foto existente, referencias mal
formadas, borrado repetible, borrado atrasado, campos de otra cuenta ignorados,
invitados JSON, compatibilidad HTML y sesión invalidada. Las pruebas anteriores de
AvatarTest siguen cubriendo formatos, tamaño, almacenamiento, caché y límite.

Total declarado: 184 casos PHP (173 anteriores y 11 nuevos). PHP/Composer no están
disponibles en la preparación: la ejecución queda pendiente en Lenovo. No hay
JavaScript nuevo en este paquete, por lo que no se repite la batería Node.
El instalador comprueba huellas, backup, CRLF y reinstalación; eso no ejecuta PHP.

## Instalación y siguiente paso

Instalar en feat/avatar-json desde 82db9ad limpio. Ejecutar config:clear,
view:clear y php artisan test. No hay migraciones, seeders, dependencias nuevas
o cambios de .env. No ejecutar fresh/seeders completos en una base usada.

Tras la batería: registrar el resultado real, commit/PR y revisión de Denys antes
del merge. Después Denys conecta el círculo con plus y el guardado automático al
contrato de esta guía. La aceptación visual se hará cuando exista esa interfaz.

## Registro de asistencia IA

- Petición: «Al subir foto ... pequeño circuito con plus ... lo guarda automaticamente».
- Autor: Denys; implementación/validación backend con Joy.
- Fecha/herramienta: 26/11/2026, ChatGPT (OpenAI).
- Resultado: respuesta JSON negociada, metadatos y precondición de versión.
- Validación: 11 casos preparados; PHP pendiente en Lenovo; revisión y PR pendientes.

## Validación local — Joy — 26/11/2026
- PHP: 184 pruebas y 1491 aserciones correctas, en 15,82 s.
- Pasan los 11 casos nuevos de AvatarJsonTest y las pruebas anteriores.
- Pendientes: revisión de Denys e interfaz circular con autoguardado.
