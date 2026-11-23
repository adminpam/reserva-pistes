# 16 · Perfil, cuentas y fotografía

Paquete 09 · Joy · preparado el 23/11/2026. Base del ZIP recibido:
`25b3cccfb92368e30e235e1011b59cafe14f1c43`. Joy confirmó después main `b2a8895`,
merge del PR #21 de administración revisado por Denys. Su rama de perfil está limpia.

## Estado anterior confirmado

08 pasó en Lenovo: **83 pruebas y 534 aserciones**, 3,33 s. Joy confirmó asignación
del gestor, creación/edición de pista, una reserva de 12 euros conservada al subir
la tarifa a 15 y bloqueo del borrado antes/después de cancelar. Commit 25b3ccc.
Son resultados anteriores a 09: no certifican las modificaciones de este paquete.

## Perfil y niveles

PATCH /profile conserva el contrato name y levels[ID]. El controlador valida
también las claves del array: deben ser IDs enteros positivos canónicos de deportes
existentes. Un ID eliminado, desconocido o manipulado devuelve validación.

El usuario y los deportes se vuelven a consultar y bloquear dentro de una
transacción. Se comprueban todos los IDs antes de escribir nombre o niveles.
Así no queda un nombre actualizado con los niveles a medias. Un valor vacío se
convierte en null y elimina solo el nivel de ese deporte. Omitir levels lo conserva.
Los campos user_id, role, email, password y avatar_version no alteran la cuenta
desde este formulario. Los niveles siguen siendo una autoevaluación de 1 a 5.

## Fotografías pequeñas guardadas en la base de datos

Una migración añade users.avatar_version y la tabla user_avatars:

| Campo | Uso |
| --- | --- |
| user_id | Clave primaria y FK a users: una foto por usuario |
| mime_type | Tipo comprobado: JPEG, PNG o WebP |
| image_base64 | Bytes codificados, en longText separado de la tabla de usuarios |
| timestamps | Fecha de creación y actualización |
| users.avatar_version | SHA-256 del contenido, incluido en la URL de la foto |

El nombre original del archivo y las rutas del navegador no se guardan. El servidor
solo acepta una imagen válida, MIME permitido, hasta **512 KB**, y dimensiones
entre 32 y 1600 píxeles por lado. El base64 ocupa aproximadamente un tercio más
que los bytes; esta elección se limita a avatares pequeños de la práctica, no a
álbumes. Las consultas de usuarios/participantes no cargan los bytes de la foto.

Con JavaScript se pueden seleccionar fotos JPG/PNG/WebP de hasta **10 MB**. El
navegador prepara un recorte cuadrado central de **384 × 384**, muestra la vista
previa y envía un JPEG pequeño mediante el formulario multipart nativo. Si cambia
la selección, una preparación atrasada no sustituye la imagen más reciente. Un
error deja la foto guardada sin cambios. La preparación del navegador no sustituye
las validaciones del servidor. Sin JavaScript hay que elegir una imagen que cumpla
directamente el límite del servidor; no se promete compresión ni retirada de
metadatos en ese recorrido.

Las fotos se muestran en perfil, cabecera, usuarios del administrador y los
participantes de Mis partidos. Son visibles para usuarios autenticados de la
plataforma. Sin foto se conservan las iniciales. Quitar foto borra la fila y la
versión del propio usuario, dentro de la misma transacción; repetirlo es seguro.

GET /users/{user}/avatar exige autenticación. Devuelve solo los bytes con MIME
permitido, nosniff, protección de recurso del mismo origen y caché privada con
revalidación/ETag. La lectura une versión y bytes en una sola consulta. No se sirve
base64 en JSON/HTML ni se acepta un enlace externo para cargar la foto.

## Persistencia y Render

La imagen no depende de storage/app/public ni de un enlace simbólico, de GD o de
un servicio con claves. Se conserva junto con la base de datos. Un redespliegue
del contenedor no la elimina **si la base utilizada es persistente**. El disco de
Render Free es efímero: guardar SQLite o fotos allí no soluciona la persistencia.

Con Denys queda verificar la base externa real, su vigencia, copias de seguridad,
migración en Render y foto recuperada después de un reinicio/redespliegue. Guardar
una fila en SQLite de pruebas no acredita ese recorrido de producción.

## Acceso y contraseña

Las plantillas de Entrar y Crear cuenta permanecen byte a byte iguales a la base.
La dirección de correo se recorta y normaliza a minúsculas. El acceso contempla
cuentas anteriores con mayúsculas, y el registro comprueba duplicados sin distinguir
ese uso de mayúsculas. Un alta pública siempre crea player; ignoramos roles extra.

Los límites se guardan en la caché de Laravel, mediante claves que no incluyen
correo/IP en texto claro:

| Acción | Límite por 60 segundos |
| --- | --- |
| Acceso fallido | 5 por correo+IP y 30 por IP |
| Registro | 5 solicitudes por IP |
| Cambio de contraseña | 5 solicitudes por usuario+IP |
| Subida de foto | 10 solicitudes por usuario |

Los mensajes no revelan si falló el correo o la contraseña. Al entrar correctamente
se renueva la sesión y se limpia el contador del correo+IP, no el global de IP.
Los límites son una protección básica; no se presentan como defensa completa ante
ataques distribuidos. En producción se debe usar una caché compartida estable.

El cambio de contraseña exige la actual y confirmar la nueva. Se vuelve a comprobar
la contraseña actual después de bloquear el usuario. La nueva se calcula con
Hash::make, incluso si el texto elegido parece ya un hash. Se rechazan entradas
con NUL y longitudes que superen el límite de bcrypt. No se guardan contraseñas
en texto claro ni se recuperan en old input.

Se rota remember_token y se utiliza logoutOtherDevices con auth.session en las
rutas privadas. La sesión actual se renueva y permanece abierta; las anteriores
deben volver a entrar en su siguiente petición protegida. El cambio de contraseña
no modifica nombre, rol, correo ni la contraseña de un user_id añadido al cuerpo.
No se añade recuperación por correo ni un servicio de envío de emails ficticio.

## Instalación y comprobación en Lenovo

Antes de instalar, incorporar el merge ya observado en origin/main dentro de
feat/perfil-cuentas (si aún está en 25b3ccc, permite fast-forward a b2a8895).
El instalador verifica el contenido de todos sus archivos antes de escribir y
se detiene si hay modificaciones nuevas. Hace copia de seguridad fuera del repo.

Después de instalar, ejecutar en C:\Dev\reserva-pistes:

```powershell
php artisan config:clear
if ($LASTEXITCODE -eq 0) {
    php artisan migrate
    if ($LASTEXITCODE -eq 0) {
        php artisan view:clear
        if ($LASTEXITCODE -eq 0) {
            php artisan test
        }
    }
}
```

Después ejecutar node --test tests/Frontend/profile.test.mjs. No se necesita npm
run build ni composer install: no hay dependencias nuevas y CSS/JS se sirven como
recursos públicos versionados. No ejecutar migrate:fresh ni seeders para instalar.
La migración agrega campos y tabla, sin recrear usuarios o reservas. Su down
elimina las fotos; el instalador no ejecuta rollback de base de datos.

## Pruebas y límites de la validación recibida

| Grupo nuevo | Casos | Estado |
| --- | --- | --- |
| ProfileRulesTest | 8 | Preparados; pendientes en Lenovo |
| AvatarTest | 11 | Preparados; PNG/JPEG/WebP sintéticos, sin GD ni personas; pendientes en Lenovo |
| PasswordChangeTest | 8 | Preparados; incluye sesión anterior y actual; pendientes en Lenovo |
| AccountAccessTest | 10 | Preparados; registro, acceso, límites y cierre; pendientes en Lenovo |
| profile.test.mjs | 8 | Aprobados al preparar el paquete |

Hay **120 casos PHP declarados** en la copia integrada: no son 120 aprobados.
El entorno que preparó el ZIP no tiene PHP/Composer. Se intentó la comprobación
Chromium de una composición representativa, pero no había un ejecutable funcional;
no se da por realizada la comprobación visual ni la compresión con un navegador real.
Las pruebas Node controlan el estado y las carreras asíncronas con dobles del DOM.

Tests/TestCase ajusta actingAs al cambiar de identidad: no se hereda el hash de
sesión del actor anterior. No se desactiva auth.session. La prueba de revocación
recupera el guard y una sesión anterior para simular una petición nueva.

## Prueba manual con Joy

1. Con una cuenta ficticia, abrir Perfil, cambiar nombre y niveles y recargar.
   Marcar No juego en un deporte: debe quedar sin nivel sin borrar los demás.
2. Elegir una imagen ficticia JPG/PNG/WebP. Ver la vista previa, guardar y comprobar
   foto en perfil y cabecera. Recargar, salir/entrar y volver a comprobar.
3. Sustituir la foto y comprobar la nueva; quitarla y comprobar las iniciales.
   Probar un archivo no admitido: la foto anterior debe conservarse.
4. Cambiar contraseña con la actual correcta, confirmar la nueva, salir y entrar
   con ella. La anterior debe fallar. Comprobar que una contraseña actual incorrecta
   no cambia nada. Usar solo credenciales ficticias y no pegarlas en el chat.
5. Mantener otra sesión del mismo usuario en incógnito antes de cambiar la
   contraseña: al abrir una ruta privada después, debe llevar al acceso.
6. Probar el perfil a 320/390/768/1024 píxeles y comprobar que controles, foto y
   botones quedan dentro de los paneles. Registrar únicamente resultados observados.

El siguiente bloque, después de aceptar este, es el pago de prueba solicitado por
Denys. La concurrencia real y el despliegue final siguen coordinándose con él.

Fuentes oficiales revisadas el 23/11/2026:
- https://laravel.com/docs/12.x/authentication
- https://laravel.com/docs/12.x/validation
- https://laravel.com/docs/12.x/rate-limiting
- https://api.laravel.com/docs/12.x/Illuminate/Http/Testing/FileFactory.html
- https://developer.mozilla.org/en-US/docs/Web/API/HTMLFormElement/formdata_event
- https://developer.mozilla.org/en-US/docs/Web/API/HTMLCanvasElement/toBlob
- https://render.com/docs/free

## Cierre de validación del paquete 09 — Joy — 24/11/2026
- Migración de avatares aplicada correctamente.
- Batería PHP: 120 pruebas y 872 aserciones correctas, en 13,43 s.
- JavaScript del perfil: 8 pruebas correctas.
- Nombre, niveles y foto se guardan y se conservan tras cerrar sesión.
- Sustituir y quitar la foto funciona.
- La contraseña actual incorrecta se rechaza; la correcta permite cambiarla.
- La sesión actual continúa y las otras requieren volver a entrar.
- La contraseña anterior falla y la nueva permite acceder.
- Revisión de Denys, persistencia tras redesplegar y concurrencia PostgreSQL pendientes.
