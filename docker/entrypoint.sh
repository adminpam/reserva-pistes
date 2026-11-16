#!/bin/sh
# Arranque del contenedor en Render:
# 1) Apache escucha en el puerto que asigna la plataforma (variable PORT).
# 2) Se aplican las migraciones pendientes.
# 3) Arranca Apache en primer plano.
set -e

PORT="${PORT:-80}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan migrate --force

exec apache2-foreground
