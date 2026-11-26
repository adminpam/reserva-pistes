# 25 · Validación del servidor y mensajes en español

Paquete 16-Joy · 26/11/2026. Base confirmada: main 6987b58, merge de PR #30.
Rama: feat/validacion-servidor. Revisión: Denys.

## Problema y resultado

La interfaz es española, pero no existía un catálogo de validación del proyecto.
Además, .env.example indicaba APP_LOCALE=en. Los errores genéricos podían mostrar
inglés o nombres técnicos como current_password y price_euros, mientras los errores
de negocio ya tenían textos españoles propios.

Se incorpora lang/es/validation.php con mensajes de las reglas utilizadas y nombres
legibles para los campos. Los errores de fecha, confirmación de contraseña, rol,
horario y referencia de reserva tienen indicaciones específicas. Los catálogos JSON
traducen también el resumen de varios errores, incluido su singular y plural.

lang/en/validation.php reutiliza el mismo catálogo español; en.json contiene los
resúmenes equivalentes. Es compatibilidad deliberada con instalaciones existentes
que conservan APP_LOCALE=en. La aplicación actual no ofrece selector de idiomas.
No representa una traducción inglesa. Un futuro modo multilingüe deberá sustituir
esa compatibilidad por traducciones reales y su propia revisión.

.env.example pasa a es/es y el valor predeterminado del idioma de respaldo pasa a
es. El instalador no modifica el .env existente ni las variables de Render.
No hay migraciones ni nuevas dependencias.

## Contrato para la interfaz de Denys

Se utiliza la validación y traducción estándar de Laravel 12. Las reglas y los
controladores permanecen iguales; se conservan sus mensajes particulares.

| Petición | Error de validación | Lectura del cliente |
| --- | --- | --- |
| Formulario HTML | Redirección y bolsa de errores de sesión | $errors; valores seguros en old() |
| Accept: application/json | HTTP 422 con message y errors | errors contiene listas de mensajes por campo |
| Perfil: nivel de un deporte | errors["levels.ID"] | Campo levels[ID] del formulario enviado |
| Pago ficticio | errors["payment.expiry"], etc. | Campo payment[expiry], etc.; nunca restaurar la tarjeta |
| Filtro de deportes | errors["sports.0"], etc. | Grupo sports[] y su resumen |
| Confirmación de contraseña | errors["password"] | Relacionar también el campo de confirmación |

Ejemplo del cuerpo JSON de registro sin datos:

```json
{
  "message": "Completa el campo nombre. (y 2 errores más)",
  "errors": {
    "name": ["Completa el campo nombre."],
    "email": ["Completa el campo correo electrónico."],
    "password": ["Completa el campo contraseña."]
  }
}
```

Las claves con puntos son propiedades literales de errors, no objetos anidados.
El cliente debe usar acceso por clave y mostrar texto, sin interpretar HTML.
Los errores de negocio sin campo editable, por ejemplo match o booking, necesitan
un aviso del formulario. Si hay varias filas o formularios con el mismo nombre de
campo, la interfaz debe relacionar la respuesta con el formulario enviado.

Un 401, 403, 419 o el 409 de versión del avatar mantiene su significado; no se
convierte en un error 422. El JSON se solicita expresamente cuando haga falta:
los formularios normales no se transforman globalmente en peticiones JSON.

La siguiente fase de Denys añadirá el aspecto uniforme, asociación accesible de
mensajes, foco en el primer campo incorrecto y validación visual del navegador.
Este paquete prepara los textos del servidor; no da por terminada esa fase visual.

## Comprobaciones preparadas

ValidationMessagesTest añade 15 casos de integración con peticiones reales:

- Registro vacío, resumen español con varios errores y compatibilidad en inglés.
- Correo inválido, longitud mínima y confirmación de contraseña.
- Redirección HTML con nombre/correo conservados y secretos excluidos de old input.
- Contraseña recibida como array y contraseña actual ausente.
- Nivel fuera de rango con su clave de deporte y sin cambios parciales de nombre.
- Precio/horario de pista incorrectos y rol administrativo inválido.
- Fecha imposible, hora incorrecta y referencia de reserva inválida sin retención.
- Datos de pago incompletos con claves literales, sin pago ni reserva confirmada.
- Error HTML de pago sin recuperar datos de tarjeta.
- Filtro de deportes mal formado y disponibilidad con fecha imposible.

Los escenarios usan los datos ficticios y la SQLite en memoria de tests/TestCase.php.
Las baterías previas siguen comprobando permisos, límites, solapamientos, aforo,
historial, caducidad, pago y seguridad de cuentas. No se relajan esas reglas.

## Ejecución por Joy

```powershell
php artisan config:clear
if ($LASTEXITCODE -eq 0) {
    php artisan view:clear
    if ($LASTEXITCODE -eq 0) {
        php artisan test
    }
}
```

No hace falta ejecutar migraciones ni seeders. PHP no está disponible en el entorno
de preparación: las 15 pruebas nuevas están escritas, pero su ejecución y la de la
batería completa quedan pendientes en el Lenovo. No se atribuye un resultado de
pruebas correcto antes de recibir su salida.

Después de integrar y desplegar, comprobar por HTTPS correo incorrecto, contraseñas
que no coinciden y un error de perfil. La tarjeta de error actual seguirá siendo
la existente hasta que Denys integre la presentación junto a cada campo.

## Evidencia de preparación y continuidad

Se comprueban huellas, ZIP, instalación sobre finales CRLF, copia de seguridad,
reinstalación sin cambios y rechazo previo a toda escritura ante modificaciones
locales o un paquete dañado. Se comprueba el diff preparado con Git, incluidos
archivos nuevos y final de archivo. Estas comprobaciones no sustituyen PHPUnit.

La foto circular y su corrección de cámara se consideran aceptadas según la
confirmación del usuario tras publicar. No se reabre esa incidencia en este paquete.
Persistencia tras redesplegar y concurrencia real de PostgreSQL siguen siendo
comprobaciones independientes; no se acreditan mediante SQLite.

## Referencias

- https://laravel.com/docs/12.x/validation#specifying-custom-messages-in-language-files
- https://laravel.com/docs/12.x/validation#specifying-attributes-in-language-files
- https://laravel.com/docs/12.x/validation#validation-error-response-format
- https://laravel.com/docs/12.x/localization

## Registro IA

- Petición: mensajes comprensibles y comprobaciones de los datos de los formularios.
- Fecha/herramienta: 26/11/2026, ChatGPT (OpenAI).
- Aportación: catálogo común, compatibilidad de idioma, contrato y 15 casos nuevos.
- Revisión humana: Joy ejecuta y valida; Denys revisa y continúa con la interfaz.
- Resultado PHP, commit, PR y aceptación publicada: pendientes.

## Validación local — Joy — 26/11/2026
- PHP: 202 pruebas y 1602 aserciones correctas, en 33,35 s.
- Pasan los 15 casos nuevos de ValidationMessagesTest.
- Pendientes: revisión de Denys, publicación y presentación de errores junto a cada campo.
