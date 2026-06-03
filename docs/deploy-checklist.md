# Deploy Checklist — ccgame-muh5 (Solo Dev)

## Trước deploy (local)

```bash
# 1. Kiểm tra syntax + style
composer run pint:dirty

# 2. Route sanity
php artisan route:list --path=admin | wc -l
# Kỳ vọng: ~23 routes (hoặc theo số resource hiện tại)

# 3. Migration preview (KHÔNG chạy thật)
php artisan migrate --pretend

# 4. Test suite
php artisan test --compact --parallel
# Nếu test redis liên quan fails: php artisan test --compact --without-tty
```

## Quy trình deploy

```bash
# Dùng script có sẵn — KHÔNG tự chạy migrate remote
bash deploy.sh          # Linux
./deploy.ps1            # Windows
```

## Các script deploy làm gì

- rsync code lên production server
- Chạy `composer install --no-dev --optimize-autoloader`
- Chạy `php artisan migrate` (production) — **script tự handle**
- `php artisan optimize` / `php artisan config:cache`
- Restart queue worker + octane (nếu dùng)

## Sau deploy

1. Check `php artisan route:list` trên production
2. Check log: `tail -f storage/logs/laravel.log`
3. Vào Filament admin, click qua từng resource để verify
4. Nếu có lỗi 500:
   - `php artisan optimize:clear` trên production
   - Kiểm tra file cached config (xoá bootstrap/cache/*.php nếu cần)

## Rollback

```bash
# Nếu deploy gần đây
git log --oneline -5
git revert HEAD --no-edit
git push

# Chạy lại deploy script
bash deploy.sh
```

## Safety (Never)

❌ `php artisan migrate:fresh` trên production
❌ `php artisan db:wipe`
❌ Đụng vào `.env`, Redis keys, database game
❌ Force push (`git push --force`)
