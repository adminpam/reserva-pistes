# Assets pendientes de sustituir

Los gráficos actuales son **SVG provisionales** creados para el desarrollo.
Antes de la entrega final, Denys los sustituirá por versiones definitivas
(propias o con licencia libre — nunca imágenes con copyright de terceros).

| Archivo | Ahora | Sustituir por |
|---|---|---|
| `public/img/logo.svg` | logo provisional (pelota + rebote) | logo definitivo del proyecto |
| `public/img/hero.svg` | ilustración de pista | ilustración o foto propia de portada |
| `public/img/club-cover.svg` | portada genérica de club | fotos reales hechas por nosotros o CC0 |
| `public/img/avatar.svg` | avatar genérico | avatares subidos por el usuario (mejora futura) |
| `public/img/empty.svg` | estado vacío | versión final del mismo estilo |
| `public/img/sports/*.svg` | 6 iconos de deportes | versiones pulidas en el mismo estilo |

Los dos sonidos de interfaz se generan con WebAudio (sin archivos), así que no hay audio pendiente.

## Actualización de marca y portada · 17/11/2026

- La cabecera ya utiliza el PNG original en `public/brand/logo-original.png`.
  `public/img/logo.svg` es un recurso antiguo y ya no representa la cabecera actual.
- La ilustración de portada y los iconos nuevos se trazan en las vistas
  `map/index.blade.php` y `partials/sport-icon.blade.php`.
- Las miniaturas por deporte son ilustrativas y no fotos verificadas de cada club.
  La ficha del mapa identifica ese carácter; el equipo puede sustituirlas más adelante.
