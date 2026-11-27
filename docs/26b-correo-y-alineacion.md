# 26b · Correo con extensión y alineación de avisos

Ajuste 17b-Joy · 27/11/2026. Base: main con el ajuste 17a integrado y publicado.
Último SHA anterior confirmado: d3820a1; commit del ajuste 17a: 5b02625.
El SHA de su merge no se ha aportado. Se comprueban las huellas de los originales.
Rama: fix/correo-y-alineacion.

## Revisión de Joy y alcance

Joy confirmó los casos 1 y 2 en ordenador y móvil. Detectó que el ojo de Nueva
contraseña bajaba al mostrar un error largo en el campo de confirmación, y que el
símbolo de admiración no quedaba centrado. También consiguió registrar el correo
david@reservapistes y pidió exigir dominio y extensión, admitiendo .test.

La corrección 17a resolvía la colocación del error dentro del contenedor del icono.
La captura nueva muestra otro caso: los elementos de la cuadrícula se estiran al
igualar la altura de dos columnas con distinto número de filas. Este ajuste hace
que .rp-field y el wrapper de contraseña se alineen al inicio y mantengan su altura
natural. El centro del ojo vuelve a depender del campo, no de la altura del vecino.

El aviso usa una columna fija de 18 px y texto flexible. La admiración se dibuja
con un SVG de fondo estático, sin depender de la línea base de una fuente. Los
mensajes de varias líneas conservan su alineación y el icono no añade texto repetido
al nombre accesible. Los mensajes siguen siendo texto, con su asociación ARIA.

## Política de correo del registro

Se conserva la validación email de Laravel y se añade EmailDomainWithExtension.
Exige al menos un punto entre etiquetas del dominio y una extensión de dos o más
letras, o su representación punycode. Admite subdominios y no limita el registro a
.com o .es. La misma política adicional se aplica en JavaScript. El campo HTML de
registro incluye además un patrón básico con punto y una ayuda visible, también
para cuando no se cargue el módulo.

Ejemplos:

| Correo | Registro nuevo |
| --- | --- |
| david@reservapistes | Se rechaza con una indicación junto al correo |
| david@reservapistes.test | Se admite por formato |
| jugador@example.com | Se admite por formato |
| jugadora@example.es | Se admite por formato |
| cuenta@sub.club.co.uk | Se admite por formato |
| cuenta@example. o cuenta@example..test | Se rechaza |

Se comprueba el formato; esto no demuestra que un dominio o buzón exista ni que
pertenezca a la persona. No se consulta DNS, porque las pruebas deben poder usar
.test. La verificación mediante un correo de confirmación no forma parte de este
ajuste. La normalización y el control de duplicados existentes siguen aplicándose.

La regla adicional solo afecta al registro. Las cuentas creadas anteriormente
con un dominio sin extensión conservan el acceso; no se modifican ni eliminan
usuarios. Los datos de ejemplo de las pruebas son ficticios.

## Primera pulsación del botón

Al pulsar Enviar desde un campo, el evento de pérdida de foco podía insertar un
aviso antes de completar el clic y desplazar el botón. Si el foco pasa al botón de
envío de ese mismo formulario, se deja la comprobación para submit. Los demás
cambios de foco conservan su ayuda. El teclado y las comprobaciones del servidor
siguen funcionando; no se convierte el formulario en AJAX.

## Pruebas

- Node: 112 pruebas correctas, incluidas 26 del archivo de formularios.
- Tres casos nuevos: tabla de dominios compartida, política aplicada solo al
  registro y pérdida de foco hacia el botón sin desplazarlo antes del clic.
- Cinco casos PHP nuevos en RegistrationEmailDomainTest: tabla de política,
  rechazo JSON sin crear usuario, error HTML seguido de corrección con .test,
  acceso de una cuenta anterior y patrón/ayuda exclusivos del registro.
- La tabla tests/Fixtures/registration-email-domains.json se utiliza en ambos
  lenguajes. Complementa las comprobaciones completas de email del navegador y
  del servidor; no pretende reemplazar sus validadores de direcciones.
- PHP no está disponible en la preparación. Las cinco pruebas nuevas y la batería
  completa quedan pendientes en el Lenovo. El último resultado aportado por Joy
  fue 207 PHP/1646 aserciones para el paquete 17, y 23 Node del archivo tras 17a.
- Instalador: huellas, ZIP, CRLF, copia de seguridad, reinstalación, rechazo previo
  a escrituras, rollback y diff preparado con Git comprobados.
- La alineación corregida del perfil y del aviso está preparada a partir de la
  captura y los estilos. No se ha comprobado en un navegador antes de publicar;
  requiere revisión real en ordenador y teléfono después del merge.

## Ejecución y aceptación

Después de instalar: config:clear, view:clear, php artisan test y todos los tests
Node. No requiere migraciones, seeders, dependencias nuevas ni cambios en Render.
El despliegue sigue siendo automático tras el merge.

Comprobar después de publicar:

1. Registro: david@reservapistes se rechaza junto al correo. Para probar .test sin
   crear otra cuenta, dejar otro campo obligatorio vacío y comprobar que el error
   de correo desaparece al añadir la extensión.
2. Perfil: provocar contraseñas distintas y verificar que los dos campos empiecen
   a la misma altura y sus ojos estén centrados aunque el aviso ocupe varias líneas.
3. Acceso vacío: admiración centrada en ordenador y móvil, con texto legible.
4. Corregir los datos y comprobar que el botón responde al primer intento.

No incluir contraseñas en capturas o en los registros de validación. El punto 1
sigue abierto hasta la aceptación de Joy; después continúa su punto 2, sonido.
Denys conserva los puntos 3, 9 y 10 según el reparto acordado.

## Registro IA

- Petición y revisión: Joy, ajustes visuales y exigencia de extensión del correo.
- Herramienta/fecha: ChatGPT (OpenAI), 27/11/2026.
- Cambios: alineación, icono vectorial, regla de registro y ayudas HTML/JS,
  pruebas compartidas y protección del primer clic.
- Referencias primarias: https://html.spec.whatwg.org/multipage/input.html#email-state-(type=email)
  y https://laravel.com/docs/12.x/validation#rule-email.
- Resultado PHP, commit, PR y aceptación publicada: pendientes.

## Validación local — Joy — 27/11/2026
- Base confirmada: main 864ea63.
- PHP: 212 pruebas y 1708 aserciones correctas, en 31,22 s.
- JavaScript: 112 pruebas correctas, sin fallos.
- Comprobados automáticamente: rechazo del correo sin extensión, aceptación de .test y acceso de cuentas anteriores.
- Alineación visual en ordenador y móvil pendiente tras publicar.
