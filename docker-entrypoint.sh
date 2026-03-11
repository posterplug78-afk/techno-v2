#!/bin/bash
# Sets Apache to listen on Railway's injected $PORT at runtime

PORT="${PORT:-80}"

# Write ports.conf
echo "Listen ${PORT}" > /etc/apache2/ports.conf

# Write VirtualHost config
cat > /etc/apache2/sites-enabled/000-default.conf << VHOST
<VirtualHost *:${PORT}>
    DocumentRoot /var/www/html
    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
VHOST

echo "Apache configured on port ${PORT}"
exec "$@"
