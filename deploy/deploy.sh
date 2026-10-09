#!/usr/bin/env bash
# =============================================================================
# SIMPIL — deploy ke EC2 (jalankan sebagai user deploy, bukan root)
#   bash deploy/deploy.sh            # deploy branch main
#   BRANCH=release bash deploy/deploy.sh
#   REF=<sha|tag> bash deploy/deploy.sh   # rollback ke commit/tag tertentu
#
# Urutan: down -> tarik kode -> dependensi -> build aset -> migrasi -> cache
#         -> reload PHP-FPM (OPcache) -> restart worker -> up.
# Jika langkah apa pun gagal, aplikasi otomatis dinaikkan kembali (trap).
# =============================================================================
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/simpil}"
BRANCH="${BRANCH:-main}"
PHP_VER="${PHP_VER:-8.3}"

cd "$APP_DIR"
umask 002   # berkas baru dapat ditulis grup www-data (PHP-FPM)

[ -f .env ] || { echo "ERROR: .env belum ada (salin dari .env.production.example)"; exit 1; }

on_exit() {
  code=$?
  php artisan up >/dev/null 2>&1 || true
  [ $code -eq 0 ] && echo "==> Deploy SELESAI" || echo "==> Deploy GAGAL (kode $code); aplikasi dinaikkan kembali"
}
trap on_exit EXIT

echo "==> Mode pemeliharaan"
php artisan down --retry=15 --refresh=15 || true

echo "==> Tarik kode (${REF:-$BRANCH})"
git fetch --prune --tags origin
git reset --hard "${REF:-origin/${BRANCH}}"

echo "==> Dependensi PHP (tanpa dev)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> Build aset (Vite)"
npm ci --no-audit --no-fund
npm run build

echo "==> Migrasi database"
php artisan migrate --force

echo "==> Cache konfigurasi, rute, view, event"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Izin direktori tulis (user deploy anggota grup www-data; lihat provision-ec2.sh)"
chgrp -R www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod g+s {} +

echo "==> Reload PHP-FPM (menyegarkan OPcache) & restart worker antrean"
sudo systemctl reload "php${PHP_VER}-fpm"
php artisan queue:restart            # worker lama selesai job-nya lalu dimulai ulang Supervisor
sudo supervisorctl reread >/dev/null
sudo supervisorctl update  >/dev/null

echo "==> Naikkan aplikasi & cek kesehatan"
php artisan up
curl -fsS -o /dev/null http://127.0.0.1/up && echo "OK: /up merespons 200"
