# 1. Remove old build artifacts and dependencies
sudo rm -rf node_modules vendor public/build public/js public/css

# 2. Ensure correct ownership for all files and folders
sudo chown -R proxy1000:www-data /var/www/1000proxy

# 3. Set correct permissions for storage and cache directories
sudo chmod -R 775 /var/www/1000proxy/storage /var/www/1000proxy/bootstrap/cache

# 4. Install PHP dependencies as proxy1000

sudo -u proxy1000 composer install --no-interaction --prefer-dist --optimize-autoloader

# 5. Install JS dependencies as proxy1000
sudo -u proxy1000 npm install

# 6. Build frontend assets as proxy1000
sudo -u proxy1000 npm run build

# 7. Clear all Laravel caches as proxy1000
sudo -u proxy1000 php artisan optimize:clear
sudo -u proxy1000 php artisan config:clear
sudo -u proxy1000 php artisan route:clear
sudo -u proxy1000 php artisan view:clear
sudo -u proxy1000 php artisan event:clear
sudo -u proxy1000 php artisan queue:flush

# 8. Re-cache config, routes, and views as proxy1000
sudo -u proxy1000 php artisan optimize:clear
sudo -u proxy1000 php artisan config:cache
sudo -u proxy1000 php artisan route:cache
sudo -u proxy1000 php artisan view:cache
sudo -u proxy1000 php artisan event:cache
sudo -u proxy1000 php artisan filament:optimize


# 9. Publish Livewire and Filament vendor assets as proxy1000
sudo -u proxy1000 php artisan vendor:publish --tag=livewire:assets --force
sudo -u proxy1000 php artisan filament:assets

# 10. (Optional) Re-link storage if needed
sudo -u proxy1000 php artisan migrate:fresh --seed

# 11. (Optional) Re-link storage if needed
sudo -u proxy1000 php artisan storage:link