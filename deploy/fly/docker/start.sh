#!/bin/sh
set -e

# On first boot the mounted volume is empty — create the directory structure
# Laravel needs before the storage symlink is created.
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs

chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Refresh the public/storage symlink so it points into the mounted volume
cd /var/www/html && php artisan storage:link --force 2>/dev/null || true

exec /usr/bin/supervisord -c /etc/supervisord.conf