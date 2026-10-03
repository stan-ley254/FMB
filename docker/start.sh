#!/bin/bash

# Fix permissions
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Run artisan commands safely as www-data
su -s /bin/bash www-data -c "php artisan migrate --force"
su -s /bin/bash www-data -c "php artisan db:seed --force"
su -s /bin/bash www-data -c "php artisan config:clear"
su -s /bin/bash www-data -c "php artisan config:cache"
su -s /bin/bash www-data -c "php artisan route:cache"
su -s /bin/bash www-data -c "php artisan view:cache"

# Start Supervisor (manages php-fpm, nginx, cron)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf