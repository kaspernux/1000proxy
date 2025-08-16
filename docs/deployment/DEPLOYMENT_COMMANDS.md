# Hardened Deployment Command Sequence (Production Safe)

The following steps assume:
- App root: /var/www/1000proxy
- Runtime user: proxy1000 (web group www-data)
- Zero (or low) downtime goal using atomic symlink release OR direct in-place update.

All destructive operations (like removing vendor/node_modules) are OPTIONAL and only needed if you suspect cache or dependency corruption.

## 0. Preconditions (manual checks)
```
php -v
node -v
npm -v
composer -V
```
Ensure correct .env present (never commit secrets). Back up current public/build if you need rollback of static assets.

## 1. (Optional) Maintenance mode (skip for rolling / blue-green)
```
sudo -u proxy1000 php artisan down --render="errors::maintenance" || true
```

## 2. Ownership & permissions (idempotent)
```
sudo chown -R proxy1000:www-data /var/www/1000proxy
sudo find /var/www/1000proxy -type f -exec chmod 644 {} +
sudo find /var/www/1000proxy -type d -exec chmod 755 {} +
sudo chmod -R 775 /var/www/1000proxy/storage /var/www/1000proxy/bootstrap/cache
```

## 3. (Optional) Clean previous build artifacts
```
sudo -u proxy1000 rm -rf node_modules vendor public/build || true
```

## 4. PHP dependencies (prefer locked)
```
sudo -u proxy1000 composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
```

## 5. JS dependencies
```
sudo -u proxy1000 npm ci --no-audit --no-fund
```

## 6. Build assets
```
sudo -u proxy1000 npm run build
```

## 7. Cache & optimize
```
sudo -u proxy1000 php artisan optimize:clear
sudo -u proxy1000 php artisan queue:prune-batches --hours=48 || true
sudo -u proxy1000 php artisan event:clear || true
sudo -u proxy1000 php artisan config:cache
sudo -u proxy1000 php artisan route:cache
sudo -u proxy1000 php artisan view:cache
sudo -u proxy1000 php artisan event:cache || true
sudo -u proxy1000 php artisan filament:optimize || true
```

## 8. Migrations
```
sudo -u proxy1000 php artisan migrate --force
```

## 9. (Optional) Seed data (idempotent only)
```
sudo -u proxy1000 php artisan db:seed --force
```

## 10. Vendor assets
```
sudo -u proxy1000 php artisan vendor:publish --tag=livewire:assets --force
sudo -u proxy1000 php artisan filament:assets
```

## 11. Storage symlink
```
sudo -u proxy1000 php artisan storage:link || true
```

## 12. Horizon / caches
```
sudo -u proxy1000 php artisan horizon:terminate || true
sudo -u proxy1000 php artisan cache:clear || true
```

## 13. Bring app online
```
sudo -u proxy1000 php artisan up || true
```

## 14. Health checks
```
curl -f https://your-domain.com/health || curl -f http://127.0.0.1/health
sudo -u proxy1000 php artisan schedule:run --verbose --no-interaction || true
```

## 15. Post-deploy review
```
tail -n 100 storage/logs/laravel.log
sudo -u proxy1000 php artisan queue:stats || true
```

### Minimal fast-path
```
sudo -u proxy1000 git pull --rebase
sudo -u proxy1000 npm run build
sudo -u proxy1000 php artisan optimize:clear && sudo -u proxy1000 php artisan config:cache route:cache view:cache
sudo -u proxy1000 php artisan migrate --force
```
