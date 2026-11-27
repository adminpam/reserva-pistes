# Ajuste 20b · carga solo durante esperas

## Problema y resultado

El ajuste 20a quitaba la espera previa del probe, pero devolvía la escena antes
de cada GET. La pantalla parpadeaba incluso con el servidor ya preparado.
Ahora se solicita primero la página real. Si Laravel responde en menos de
800 ms, se devuelve su respuesta intacta y no se muestra la escena.
Si pasan 800 ms, falla la red o responde la pantalla de Render, se ofrece
la escena pública ya instalada; si no está disponible, la entrada estática.
El límite es una tolerancia visual, no una estimación del tiempo de arranque.
La escena mantiene la pelota, la pista y su comprobación de preparación.

## Cambios

- Middleware global que añade X-Reserva-Pistes-App: 1 a respuestas de Laravel.
  Permite distinguir la página real del HTML de arranque del proxy, incluso
  si ese HTML tiene estado 200. No consulta la base de datos.
- El worker 20b conserva respuesta, estado y cuerpo de los GET rápidos.
  Los redirects manuales opacos de navegación se devuelven intactos.
- Un error propio de Laravel identificado se muestra con su estado original.
- No se crea un caché de páginas personales. Solo se conserva la escena
  pública verificada por SHA-256, igual que en 20a.
- Al vencer la tolerancia se cancela la lectura GET pendiente; una respuesta
  tardía se descarta. La escena comprueba ready y abre después el destino
  con rp_ready=1. Ese segundo GET es deliberado y no se aplica a escrituras.
- Sin worker actualizado, enlaces y búsquedas GET comprueban brevemente ready.
  Si responde, abren el destino directamente con rp_ready=1 para evitar el
  comportamiento del worker 20a durante su actualización. Si no, usan inicio.
- Los filtros repetidos, fecha, ancla y submitter se conservan. Se respetan
  descargas, ventanas nuevas y navegación con teclas modificadoras.
- La última navegación prevalece; pagehide, desmontar o un POST posterior
  invalidan una comprobación anterior. POST/PATCH/DELETE siguen nativos.

## Base e instalación

Base: archivos del ajuste 20a. No se asume un SHA de merge no confirmado.
El instalador verifica las huellas de todos los archivos afectados antes de
escribir, admite CRLF y hace copia de seguridad. Una edición distinta detiene
la instalación completa. No cambia .env ni datos de usuarios.
Rama propuesta: fix/carga-selectiva. Guía incluida: INSTALACION.md.
No requiere nuevas dependencias, migraciones ni ajustes en Render.
Ambos servicios existentes continúan desplegándose automáticamente tras merge.

## Comprobación preparada

- 205 pruebas Node correctas: 37 del gateway, 14 de la escena, 5 del builder
  y 149 restantes del frontend. Incluyen GET rápido, espera, HTML de Render
  200/503, errores propios, redirects, respuestas tardías, caché no disponible,
  versión antigua, formularios, cambio de destino y cancelación al salir.
- Construidas escena pública de 37318 bytes y entrada loading-site/dist.
- Cinco casos PHP añadidos: login, ready sin cookies, perfil autenticado,
  redirección al login y 404 de Laravel. PHP y construcción Docker completa
  no ejecutados en la preparación: ejecutar php artisan test localmente.
- ZIP, huellas e instalador comprobados con CRLF, backup, reinstalación,
  rechazo de cambios desconocidos y restauración ante un fallo de copia.

## Validación local requerida

```powershell
php artisan config:clear
php artisan view:clear
php artisan test
node scripts/build-navigation-shell.mjs
node scripts/build-loading-site.mjs
$pruebas20b = @(Get-ChildItem -LiteralPath 'tests/Frontend' -Filter '*.test.mjs' -File | Select-Object -ExpandProperty FullName)
node --test $pruebas20b
git diff --check
```

## Publicación y comprobación visual

Después de las pruebas, subir únicamente los ocho archivos de este ajuste.
PR: fix: mostrar la carga solo durante esperas reales.
Descripción: Evita el parpadeo al navegar con Laravel preparado. Conserva la
escena pública al superar 800 ms o recibir HTML de arranque de Render, sin
almacenar respuestas de cuentas ni reenviar escrituras. Añadir los resultados
PHP locales antes del merge; 205 pruebas Node correctas en preparación.

Tras Live, entrar por reserva-pistes-inicio.onrender.com y abrir una vez
la aplicación para recibir los módulos y el worker 20b. La pestaña antigua
puede conservar 20a hasta esa actualización; no borrar todos los datos ni
cerrar sesiones para probarlo.

1. Con servidor preparado: varios cambios de menú, filtros y recargas rápidas
   deben abrir páginas sin la escena intermedia. Un clic que no navega no la abre.
2. Dejar dormir el backend sin mantener un monitor que lo despierte. Desde una
   pestaña que ya tenga worker actualizado, un GET lento debe mostrar la escena
   tras la tolerancia y terminar en la ruta solicitada con filtros conservados.
3. Comprobar perfil y acciones de reserva/pago: la sesión se conserva y las
   operaciones se envían una sola vez.
4. Repetir en móvil. Una espera real superior a la tolerancia puede mostrar la
   escena aunque el servidor esté despierto; ese comportamiento es intencionado.

Un primer acceso directo al backend dormido, sin worker instalado, sigue
bajo control del proxy de Render. La entrada pública estable continúa siendo
reserva-pistes-inicio.onrender.com, según la opción gratuita elegida.

## Validación local — Denys — 27/11/2026
- Base: main b528149.
- PHP: 219 pruebas y 1748 aserciones correctas, en 12,09 s.
- Pasan los cinco casos de GatewayResponseTest.
- JavaScript: 205 pruebas correctas, sin fallos.
- Escena pública de 37318 bytes y entrada estática construidas.
- git diff --check sin errores.
- Pendiente: revisión visual tras el despliegue en ordenador y móvil.
