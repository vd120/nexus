#!/bin/bash
set -e

npm run build

# Clear all Laravel caches so new routes, config, and views are picked up immediately
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear
php artisan event:clear
php artisan queue:restart
php artisan octane:reload
