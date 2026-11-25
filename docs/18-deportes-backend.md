# 18 · Deportes múltiples y pádel · Joy

Paquete 11-Joy · 24/11/2026. Base recibida: 873e439a24d546c472e988165e341020bdba3aed,
merge del PR #23. Reserva/pago y perfil estaban aceptados antes de este paquete.

## Estado acreditado de la base

Joy: 151 pruebas PHP /1290 aserciones, 15,13 s; 10 casos Node de pago. Recorridos
manuales de pago aprobado/rechazado, cancelación y reversión, retención/caducidad,
partido abierto, organizador único y participantes conservados tras cancelar.
Denys confirmó 873e439 Live, persistencia de cuenta/foto/reserva/justificante tras
redesplegar y conflicto manual de dos cuentas en PostgreSQL. No se ha realizado
un ensayo automatizado de peticiones simultáneas en PostgreSQL. Estos resultados
corresponden a la base 873e439; no acreditan las modificaciones de 11.

## Alcance y reparto

Joy modifica selección/consultas, datos aditivos, la ficha de club necesaria para
conservar parámetros y pruebas PHP. Denys tiene un paquete independiente de iconos
y ojos de contraseña: no comparten archivos. El selector visual de varios deportes
en mapa y partidos se conectará en el siguiente paquete de Denys tras revisar este
contrato. En esta fase el selector actual todavía elige un deporte, y sus enlaces
ya conservan ese filtro al abrir el club. Varios deportes se comprueban por URL.

## Contrato de filtros

| Parámetro | Significado |
| --- | --- |
| Sin filtros o sports vacío | Todos los deportes |
| sport=tenis | Compatibilidad con enlaces/selector antiguos |
| sports[]=tenis&sports[]=padel | Cualquiera de esos deportes (OR) |

Se admiten hasta 32 elementos de una lista; cada slug debe existir en sports. Se
rechazan arrays anidados, listas asociativas, tipos incorrectos y slugs desconocidos.
Duplicados se eliminan y la lista se ordena para generar enlaces estables. No se
mezclan sport no vacío y sports no vacío: se devuelve validación 422 para JSON.
Los filtros son preferencias de búsqueda, no permisos para acceder a una pista.
Las reservas siguen verificando permisos, horario, disponibilidad y precio propios.

GET /api/clubs devuelve solamente clubes con alguna pista que cumpla la selección.
En cada resultado, sports, sport_slugs, court_count, imagen y min_price_per_hour se
calculan sobre las pistas filtradas. La imagen sigue siendo una ilustración del
deporte, no una fotografía verificada del club. url conserva sports[] y apunta
al club local. La respuesta no se almacena en caché.

GET /clubs/{club} carga únicamente esas pistas. Tanto la cabecera/deportes y el
indicador de exterior como el formulario de fecha usan la misma selección. El
formulario GET conserva inputs sports[] y el enlace de actualización JSON lleva
los mismos parámetros. Ver todas las pistas conserva la fecha y quita el filtro.
Si el club no tiene esos deportes hay un vacío específico, no una base supuestamente
sin datos. Un día inválido conserva el comportamiento anterior de la ficha.

GET /clubs/{club}/availability exige date y filtra por los mismos deportes. El
contrato JSON sigue siendo date, checked_at, courts[{id,hours}]. No añade nombres,
reservas, jugadores o pagos. La consulta UNION común sigue incluyendo reservas y
retenciones activas de las pistas seleccionadas; filtrar no libera horas ocupadas.

GET /matches también admite sports[] junto con club y my_level. El formulario
visual se ampliará en el paquete de mapa de Denys; los enlaces sport siguen válidos.

Ejemplos locales para pegar en el navegador:

```text
http://127.0.0.1:8000/api/clubs?sports[]=tenis&sports[]=padel
http://127.0.0.1:8000/clubs/1?sports[]=tenis&sports[]=padel&date=2026-11-25
```

Sustituir el ID y la fecha por un club real de la base y un día futuro.

## Pádel sin reiniciar la base

La migración nueva registra Pádel/padel con cuatro plazas solo si no existe. No
sobrescribe el aforo ni nombre de un pádel ya administrado. En una base con clubes,
revisa únicamente los tres primeros por ID y añade una pista por club si no tiene
ninguna de pádel. Son pistas explícitamente [Demo], interiores, 08:00–22:00, con
precios simulados 14/16/18 euros por hora. No afirma que esos clubes reales tengan
pádel verificado. No cambia una pista existente de otro deporte ni su tarifa.

La migración repetida no duplica pistas; si el club ya tiene una pista de pádel la
respeta. En una instalación nueva sin clubes registra solamente el deporte. Los
seeders de deportes/pistas incorporan pádel para futuras instalaciones; no es
necesario ejecutar el seeder completo en una base ya usada.

El rollback conserva los datos de catálogo porque pueden haber generado niveles,
reservas y pagos: down no los elimina. No se utiliza una reversión destructiva ni
se borra historial. La imagen padel.webp es la ilustración aportada por Denys.
El aforo, niveles, pago, retención y cancelación usan las reglas existentes.

## Instalación y aceptación

Rama de Joy: feat/deportes-filtros. Instalar 11-Joy en C:\Dev\reserva-pistes.
Ejecutar config:clear, migrate, view:clear y php artisan test. No usar fresh/seeders.
La copia integrada tiene 165 casos PHP declarados: 14 nuevos (SportSelectionTest
10 y PadelSetupTest 4). Se ajustan MapPageTest y tres conteos de administración al
catálogo inicial de pádel. PHP/Composer no están disponibles en la preparación:
la ejecución completa está pendiente en Lenovo. El instalador comprueba hashes,
backup, reinstalación, CRLF y cambios locales; eso no equivale a pasar PHPUnit.

Prueba manual con Joy:
1. Abrir API con tenis y pádel, ver clubes con cualquiera de los dos.
2. Seguir el url del club; ver únicamente esas pistas y un precio calculado con ellas.
3. Cambiar la fecha y recuperar foco: se conservan las pistas filtradas y las horas.
4. Abrir Ver todas las pistas: aparecen los demás deportes del club.
5. Ver Pádel en perfil, elegir nivel y crear/pagar un partido abierto para cuatro.
6. Completar aforo con cuentas ficticias, cancelar como organizador y comprobar
   reversión/historial. No usar datos personales reales en la demostración.

Tras tests/aceptación: commit y PR, revisión de Denys, merge. Denys integra su rama
con --no-edit para evitar abrir el editor de mensajes. Luego continúa el mapa
multiselección; no se declara terminado ese selector en este paquete.

## Desarrollo que continúa

- Avatares con plus/autoguardado, mensajes de validación y sonido móvil.
- Encuentra mi partido, Cancha viva, comparación de clubes, invitación/QR/calendario,
  favoritos y filtros guardados.
- Intro de vídeo 2 s separada del cargador, con omisión y movimiento reducido.
- Carga tras reposo: la primera visita directa a un backend dormido sigue siendo
  una limitación del origen Render; no se promete sustituirla cambiando CSS.
- El cierre y la presentación final se harán cuando Denys indique su formato.

## Validación local — Joy — 25/11/2026
- Migración de pádel aplicada correctamente.
- PHP: 165 pruebas y 1365 aserciones correctas, en 22,24 s.
- Filtro combinado de tenis y pádel comprobado mediante URL.
- Cambiar la fecha conserva el filtro; ver todas las pistas lo elimina.
- Partido abierto de pádel comprobado: 1/4 jugadores.
- Pendientes: revisión de Denys y publicación.
