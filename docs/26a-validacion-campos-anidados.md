# 26a · Avisos en campos con iconos y ojo de contraseña

Ajuste 17a-Joy · 26/11/2026. Base: main con el paquete 17 integrado y publicado.
Último SHA anterior confirmado: 7898e11. El SHA del merge de 17 no se ha aportado;
el instalador verifica las huellas de los dos archivos afectados.
Rama: fix/validacion-iconos.

## Comprobación publicada

Joy confirmó la publicación tras pasar 207 pruebas PHP/1646 aserciones y 108 Node.
Después, el asistente abrió por HTTPS registro y acceso en un navegador real.
Comprobó el registro vacío, correo inválido, retirada de su error al corregirlo,
resumen, borde y foco en el primer campo incorrecto. También comprobó acceso vacío.
Estas pruebas no crearon cuentas ni iniciaron una sesión.

En la revisión visual del acceso se observó que el ojo de contraseña bajaba cuando
aparecía el mensaje debajo. El campo contiene field > field-icon > rp-password-wrap
> input. La mejora añadía el error al lado del wrapper más cercano, dentro de
field-icon. Al crecer ese contenedor, cambiaba el centro vertical usado por el ojo.

Se da prioridad al contenedor completo .field/.rp-field, fieldset o grupo. El
mensaje queda debajo del campo y fuera de los envoltorios del icono y del ojo.
Los envoltorios se usan como respaldo cuando no existe un contenedor completo.
La identidad del formulario, mensajes, validación y foco conservan su comportamiento.

## Regresión

Se añade un caso con la composición anidada real del acceso. Comprueba que el
mensaje sea hijo del campo completo y que el contenedor del icono no reciba otro
hijo que cambie su altura. Control negativo: con el JavaScript publicado de 17,
22 casos pasan y este caso falla; con 17a pasan los 23 casos del archivo.

La batería completa preparada pasa 109 pruebas Node. Los 207 casos PHP ya ejecutados
por Joy corresponden al paquete 17; este ajuste no modifica archivos PHP. No se
presenta el adaptador DOM como un navegador real. La posición corregida del ojo
necesita confirmación después de desplegar 17a.

## Aplicación y revisión

Instalar desde main actualizado en fix/validacion-iconos y ejecutar:

```powershell
node --test tests/Frontend/form-validation.test.mjs
```

Tras commit, PR y merge, Render despliega automáticamente. Comprobar el acceso vacío
por HTTPS: error debajo del campo y ojo centrado dentro del campo de contraseña.
Continuar con contraseñas distintas en registro y error de contraseña actual en el
perfil, también en teléfono. La revisión del punto 1 permanece abierta hasta que
Joy confirme estas comprobaciones.

## Registro IA

- Herramienta/fecha: ChatGPT (OpenAI), 26/11/2026.
- Detección: comprobación visual real de los formularios publicados.
- Cambio: anclaje del mensaje en campos anidados y regresión que falla sobre 17.
- Evidencia: 109 pruebas Node correctas; revisión visual del ajuste pendiente.
- No se adjuntan capturas de cuentas ni datos privados al paquete.

## Validación local — Joy — 26/11/2026
- Base confirmada: main d3820a1.
- JavaScript de formularios: 23 pruebas correctas, en 128,76 ms.
- Pasa la regresión de colocación del mensaje fuera del contenedor del icono.
- git diff --check sin errores.
- Revisión visual del ajuste publicado pendiente.
