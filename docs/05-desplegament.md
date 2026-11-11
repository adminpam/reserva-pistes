# 05 · Despliegue

## Plataforma elegida: Laravel Cloud (plan gratuito)

Comparadas las opciones gratuitas **sin tarjeta de crédito**, elegimos **Laravel Cloud**:
- Registro sin tarjeta con 5 $ de crédito inicial (documentación oficial: cloud.laravel.com/docs/free-trial).
- Soporte nativo de Laravel 12 y base de datos PostgreSQL gestionada.
- Dominio gratuito `*.free.laravel.cloud` para la demo.
- Con la **hibernación activada**, una app pequeña consume muy poco; desplegaremos cerca de la fecha de presentación para que el crédito cubra la demo.

Alternativa evaluada: Render (requiere Dockerfile para PHP y su PostgreSQL gratuito caduca a los 30 días).

## Pasos
1. Crear cuenta en cloud.laravel.com (correo, sin tarjeta) y conectar GitHub.
2. Importar el repositorio `adminpam/reserva-pistes`, rama `main`.
3. Añadir una base de datos PostgreSQL del propio panel (las variables `DB_*` se inyectan solas).
4. Variables de entorno: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` (generada), `SEED_PASSWORD` (solo si se siembran usuarios demo).
5. Comando de build: `npm ci && npm run build`. Tras el primer deploy: `php artisan migrate --force --seed` y `php artisan clubs:import`.
6. Activar hibernación y anotar la URL final aquí:

**URL de producción:** _pendiente_

## Seguridad
- `.env` nunca se sube (está en `.gitignore`); las claves solo viven en el panel de la plataforma.
- `APP_DEBUG=false` en producción.
