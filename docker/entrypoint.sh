#!/bin/sh
# SIMPIL — entrypoint container PHP (app, queue, dev).
# Cache Laravel dibuat SAAT START, bukan saat build: config:cache membekukan nilai env,
# sedangkan env (DB, S3, APP_KEY) baru tersedia saat container berjalan.
set -e

# Dev (bind-mount tanpa vendor): pasang dependensi sekali.
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
else
    # cache:clear memakai tabel `cache` (CACHE_STORE=database) yang belum ada di DB baru sebelum migrasi,
    # jadi kegagalannya tidak boleh menghentikan start.
    php artisan optimize:clear || true
fi

# Hanya untuk dev. Produksi: jalankan migrasi sebagai satu task terpisah (lihat deploy/aws/ECS-DEPLOY.md),
# agar beberapa container tidak bermigrasi bersamaan.
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
