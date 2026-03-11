#!/bin/bash
set -e

PORT="${PORT:-8080}"
echo "Starting EduQueue on port ${PORT}"

# Inject real port into Nginx config
sed -i "s/PORT_PLACEHOLDER/${PORT}/g" /etc/nginx/sites-enabled/default

# Remove default nginx site if it exists separately
rm -f /etc/nginx/sites-enabled/default.bak

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground (keeps container alive)
exec nginx -g "daemon off;"
