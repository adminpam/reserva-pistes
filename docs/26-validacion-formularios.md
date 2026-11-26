# 26 · Avisos junto a los campos de los formularios

Paquete 17-Joy · 26/11/2026. Base confirmada: main 7898e11, PR #31 integrada.
Rama: feat/validacion-formularios. Joy continúa también con la parte visual.

## Comportamiento

Los formularios muestran mensajes españoles junto al dato incorrecto y un resumen
suave de color cálido. Los campos se distinguen con borde, texto e icono; no solo
por color. Al enviar, el primer campo incorrecto recibe el foco. Los mensajes se
revisan al corregir los datos, sin marcar todos los campos en rojo al abrir la página.

Se conservan las asociaciones aria-describedby anteriores y se añade la del error.
Los grupos de horas y niveles comparten su mensaje. Si un error de negocio no tiene
campo editable, su texto se muestra en el resumen del formulario correspondiente.
El desplazamiento es inmediato y deja margen para la cabecera, sin animación forzada.

La mejora comprueba las restricciones HTML y algunas relaciones entre campos:
confirmación de contraseña, límite de la contraseña nueva, cierre posterior a la
apertura, niveles mínimo/máximo de un partido abierto y datos exclusivamente
ficticios del pago de prueba. El acceso conserva la posibilidad de usar contraseñas
antiguas: no impone la política de creación al formulario de login.

Los límites de longitud de correo, contraseña nueva y búsquedas se explicitan en
las plantillas. En registro se añade una ayuda breve relacionada con la contraseña.
No se calculan disponibilidad, caducidad del pago, permisos ni precio en el cliente.
El servidor sigue aplicando todas las reglas y devolviendo los mensajes del paquete 16.

## Formularios repetidos y respuestas del servidor

El módulo prepara un marcador _validation_form solo en formularios que envían POST.
Contiene ruta, método efectivo y, para las reservas, ID de pista. No contiene datos
personales, contraseñas, tarjetas, valores editados ni tokens CSRF. Laravel conserva
el marcador mediante el mecanismo existente de old input si rechaza un formulario.

La vista entrega únicamente el marcador y los errores en un bloque application/json,
con caracteres HTML escapados. Nunca serializa old input completo. El cliente usa
textContent para los mensajes y compara identidades exactas; no interpreta mensajes
como HTML ni construye selectores a partir del contenido del error.

Así, dos tarjetas que envían a /bookings se distinguen por su pista. Las filas de
administración se distinguen por su ruta y método. Los errores no se asignan a la
primera fila que casualmente tenga un campo con el mismo nombre.

Sin marcador, solo se asocian errores si existe un único formulario candidato.
Si hay ambigüedad, un marcador desconocido o un fallo al leer el bloque, se conserva
el aviso HTML del servidor, que sigue siendo accesible y puede recibir el foco.
El aviso general se oculta únicamente cuando se ha trasladado al formulario exacto.

## Compatibilidad

La validación visual se registra antes de screens.js y checkout.js. Un envío
incorrecto se detiene antes de la confirmación o de cambiar los botones a estado
de envío. Una vez corregido, el formulario sigue su recorrido normal.

No hay AJAX de formularios ni nuevas peticiones. No se modifica el método, destino,
CSRF, clave de pago o mecanismo de confirmación. No se cambia la lógica de reservas,
pagos, sesión, roles ni almacenamiento. Los filtros GET no reciben marcadores.
La foto con autoguardado y los formularios de salir quedan fuera de esta mejora.

No se añade novalidate a las plantillas: solo se activa la validación personalizada
cuando el módulo está cargado y sus manejadores están registrados. Sin JavaScript
permanecen las restricciones nativas y los avisos del servidor. Si falla la
comprobación durante un envío, se recupera la validación nativa; el servidor continúa
siendo la autoridad. No se guardan datos de formularios en localStorage o sessionStorage.

## Verificación de preparación

- Node: 108 pruebas correctas, incluidas 22 nuevas de form-validation.test.mjs.
- Los casos nuevos comprueban mensajes, contraseña visible/oculta, Unicode, límites,
  datos ficticios, rangos, grupos, ámbito entre pistas, errores de negocio, foco,
  protección del texto, conservación de ARIA, reinicio e instalación sin duplicados.
- El adaptador DOM de Node recibe flags de ValidityState preparadas. No se presenta
  como prueba ejecutada en un navegador real ni como comprobación de CSS en teléfono.
- PHP: 5 casos nuevos de FormValidationUiTest, pendientes de ejecutar en el Lenovo.
  Cubren las plantillas, orden de carga, restricciones sin JS, contexto tras un error
  real, bloque JSON sin secretos y protección frente a cierres de script.
- Se verifica el paquete, las huellas y el diff completo con Git, incluidos archivos
  nuevos. Se prueba instalación CRLF, backup, reinstalación, rechazo atómico y rollback.

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

```powershell
$pruebas17 = @(Get-ChildItem -LiteralPath 'tests/Frontend' -Filter '*.test.mjs' -File | Select-Object -ExpandProperty FullName)
node --test $pruebas17
```

No se necesitan migraciones ni seeders. No se ha ejecutado PHPUnit durante la
preparación porque PHP no está disponible en ese entorno. Los resultados anteriores
del paquete 16 fueron 202 pruebas/1602 aserciones, confirmados por Joy; no se usan
como si validaran las nuevas vistas.

## Revisión visual después de publicar

El despliegue de Render ya se inicia automáticamente después del merge. Joy no
necesita entrar en el panel para realizar esta fase. Comprobar por HTTPS y teléfono:

1. Registro incompleto y correo incorrecto: mensajes legibles y foco en el dato.
2. Contraseñas distintas: aviso junto a la confirmación, también con el ojo activado.
3. Corregir y enviar: desaparece el error y el botón permite continuar.
4. Registro con un correo ya registrado: mensaje real del servidor junto al correo.
5. Contraseña actual incorrecta en perfil: error en el formulario de contraseña,
   manteniendo independiente el formulario de nombre/niveles y la foto automática.
6. Alta/edición de una pista ficticia: decimales, límites y horario invertido.
7. Reserva sin hora: un aviso del grupo; error devuelto para una pista no debe
   aparecer en la tarjeta de otra pista.
8. Pago de prueba: solo datos ficticios; completar un error no debe dejar el botón
   bloqueado ni confirmar pagos o reservas por anticipado.

No guardar contraseñas ni tarjetas reales en capturas o en la documentación.
Esta revisión corresponde al punto 1; la revisión global del punto 10 sigue
asignada a Denys.

## Continuidad y registro IA

- Petición: avisos claros y comprobación de formularios; Joy asume esta fase visual.
- Herramienta: ChatGPT (OpenAI), 26/11/2026.
- Aportación: módulo y estilos, bloque seguro de errores, ajustes de restricciones,
  22 casos Node y 5 PHP, documentación de instalación y comprobación real.
- Referencia primaria: https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#constraint-validation
- Resultado PHP, revisión visual publicada, commit y PR: pendientes.
- Siguiente bloque de Joy, después de aceptar este: sonido en teléfono (punto 2).
- Reparto vigente: Joy 1, 2, 4, 5, 6, 7, 8; Denys 3, 9, 10.

## Validación local — Joy — 26/11/2026
- PHP: 207 pruebas y 1646 aserciones correctas, en 28,93 s.
- JavaScript: 108 pruebas correctas, sin fallos.
- Pasan los 5 casos PHP y los 22 casos JavaScript nuevos.
- Revisión visual por HTTPS y teléfono pendiente tras publicar.
