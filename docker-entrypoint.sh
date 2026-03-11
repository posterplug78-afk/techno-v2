#!/bin/bash
# docker-entrypoint.sh
# Railway injects a $PORT env variable at runtime.
# This script updates Apache to listen on that port before starting.

PORT="${PORT:-80}"

# Update Apache to listen on the Railway-assigned port
echo "Listen ${PORT}" > /etc/apache2/ports.conf

# Update the VirtualHost to match
cat > /etc/apache2/sites-enabled/000-default.conf <<EOF
<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

echo "Apache configured to listen on port ${PORT}"

# Hand off to the CMD (apache2-foreground)
exec "$@"
