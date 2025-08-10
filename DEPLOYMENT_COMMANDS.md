## Hardened Deployment Command Sequence (Production Safe)

The following steps assume:
- App root: /var/www/1000proxy
- Runtime user: proxy1000 (web group www-data)
- Zero (or low) downtime goal using atomic symlink release OR direct in-place update.

All destructive operations (like removing vendor/node_modules) are OPTIONAL and only needed if you suspect cache or dependency corruption.

### 0. Preconditions (manual checks)
```
php -v
node -v
npm -v
composer -V
```
Ensure correct .env present (never commit secrets). Back up current public/build if you need rollback of static assets.

### 1. (Optional) Put application in maintenance mode (skip for rolling / blue-green)
```
sudo -u proxy1000 php artisan down --render="errors::maintenance" || true
```

### 2. Refresh ownership & minimal writable permissions (idempotent)
```
sudo chown -R proxy1000:www-data /var/www/1000proxy
sudo find /var/www/1000proxy -type f -exec chmod 644 {} +
sudo find /var/www/1000proxy -type d -exec chmod 755 {} +
sudo chmod -R 775 /var/www/1000proxy/storage /var/www/1000proxy/bootstrap/cache
```

### 3. (Optional, heavy) Clean previous build artifacts ONLY if needed
```
sudo -u proxy1000 rm -rf node_modules vendor public/build || true
```

### 4. Install / update PHP dependencies (prefer locked)
```
sudo -u proxy1000 composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader
```
If using build servers for assets, you can still omit --no-dev there and prune later.

### 5. Install JS dependencies
```
sudo -u proxy1000 npm ci --no-audit --no-fund
```
Use npm ci for reproducible builds (requires package-lock.json). Fallback: npm install.

### 6. Build frontend assets
```
sudo -u proxy1000 npm run build
```
Add --force only when you intentionally need to overwrite stale caches.

### 7. Cache & optimize application
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
Note: queue:flush was removed (dangerous—discards pending jobs). Use with caution only for dead-letter purges.

### 8. Database migrations (safe forward-only)
```
sudo -u proxy1000 php artisan migrate --force
```
NEVER use migrate:fresh in production; it drops all tables. Reserve migrate:fresh --seed for staging or local resets.

### 9. (Optional) Seed additional data (idempotent seeders only)
```
sudo -u proxy1000 php artisan db:seed --force
```

### 10. Publish/update front-end/vendor assets when versions changed
```
sudo -u proxy1000 php artisan vendor:publish --tag=livewire:assets --force
sudo -u proxy1000 php artisan filament:assets
```

### 11. Ensure storage symlink exists
```
sudo -u proxy1000 php artisan storage:link || true
```

### 12. Horizon / Queue / Cache warmup (if applicable)
```
sudo -u proxy1000 php artisan horizon:terminate || true
sudo -u proxy1000 php artisan cache:clear || true
```
Horizon will restart via supervisor. Pre-warm caches as needed (e.g., config:cache already done).

### 13. Bring application back online (if taken down)
```
sudo -u proxy1000 php artisan up || true
```

### 14. Post-deploy health checks
```
curl -f https://your-domain.com/health || curl -f http://127.0.0.1/health
sudo -u proxy1000 php artisan schedule:run --verbose --no-interaction || true
```

### 15. Log & queue sanity review
```
tail -n 100 storage/logs/laravel.log
sudo -u proxy1000 php artisan queue:stats || true
```

### Rollback Guidance (simplified)
1. Keep a tarball of previous release (code + built assets + vendor) before step 3.
2. If deploy fails after migrations that are backward compatible: revert symlink or restore tarball.
3. If a migration is breaking, run down migration or deploy hotfix forward migration.

### Security / Safety Notes
- Avoid chmod -R 777; least privilege principle.
- Do not run composer/npm as root; use application user.
- Replace migrate:fresh in production with standard migrate.
- queue:flush removed to prevent accidental job loss.
- Ensure backups before schema changes.

### Minimal Fast-Path (no dependency updates, just cache refresh & assets)
```
sudo -u proxy1000 git pull --rebase
sudo -u proxy1000 npm run build
sudo -u proxy1000 php artisan optimize:clear && sudo -u proxy1000 php artisan config:cache route:cache view:cache
sudo -u proxy1000 php artisan migrate --force
```

sudo -u proxy1000 php artisan optimize:clear
sudo -u proxy1000 php artisan config:clear
sudo -u proxy1000 php artisan route:clear
sudo -u proxy1000 php artisan view:clear
sudo -u proxy1000 php artisan event:clear
sudo -u proxy1000 php artisan queue:flush
sudo -u proxy1000 php artisan optimize
sudo -u proxy1000 php artisan config:cache
sudo -u proxy1000 php artisan route:cache
sudo -u proxy1000 php artisan view:cache
sudo -u proxy1000 php artisan event:cache
sudo -u proxy1000 php artisan filament:optimize