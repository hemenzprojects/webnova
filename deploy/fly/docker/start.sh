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

# Rebuild the package manifest against this container's vendor/. A manifest
# generated on the host (dev install) lists dev-only providers like Pail, which
# don't exist in the --no-dev vendor and crash artisan before it can rediscover.
cd /var/www/html
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
php artisan package:discover --ansi

# Refresh the public/storage symlink so it points into the mounted volume
php artisan storage:link --force 2>/dev/null || true

exec /usr/bin/supervisord -c /etc/supervisord.conf