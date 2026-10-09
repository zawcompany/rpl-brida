#!/usr/bin/env bash
# =============================================================================
# SIMPIL — provisioning SEKALI JALAN untuk EC2 Ubuntu 24.04 LTS
# Pasang: Nginx, PHP 8.3-FPM (+ekstensi), Composer, Node 20, Supervisor, CA RDS.
#
# Pemakaian (di EC2, sebagai root):
#   sudo REPO=https://github.com/zawcompany/rpl-brida.git BRANCH=main bash provision-ec2.sh
# =============================================================================
set -euo pipefail

REPO="${REPO:?Isi REPO=<url git>}"
BRANCH="${BRANCH:-main}"
APP_DIR="${APP_DIR:-/var/www/simpil}"
DEPLOY_USER="${DEPLOY_USER:-ubuntu}"      # user SSH yang menjalankan deploy.sh
PHP_VER="8.3"

export DEBIAN_FRONTEND=noninteractive

echo "==> Paket dasar"
apt-get update -y
apt-get install -y nginx supervisor git unzip curl ca-certificates acl

echo "==> PHP ${PHP_VER}"
apt-get install -y \
  php${PHP_VER}-fpm php${PHP_VER}-cli php${PHP_VER}-mysql php${PHP_VER}-redis \
  php${PHP_VER}-mbstring php${PHP_VER}-xml php${PHP_VER}-curl php${PHP_VER}-zip \
  php${PHP_VER}-gd php${PHP_VER}-intl php${PHP_VER}-bcmath

echo "==> Composer"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "==> Node.js 20 (untuk build Vite)"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

echo "==> CA bundle RDS (koneksi MySQL ber-TLS)"
curl -fsSL -o /etc/ssl/certs/rds-global-bundle.pem https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem

echo "==> Konfigurasi PHP (unggahan 10 MB, OPcache produksi)"
cat > /etc/php/${PHP_VER}/fpm/conf.d/99-simpil.ini <<'INI'
upload_max_filesize = 12M
post_max_size = 14M
memory_limit = 256M
expose_php = Off
opcache.enable = 1
opcache.validate_timestamps = 0   ; wajib reload php-fpm saat deploy (deploy.sh melakukannya)
opcache.memory_consumption = 128
INI

echo "==> User deploy masuk grup www-data (berbagi izin tulis storage/ dengan PHP-FPM)"
usermod -aG www-data "$DEPLOY_USER"

echo "==> Kode aplikasi di ${APP_DIR}"
mkdir -p "$APP_DIR"
chown "$DEPLOY_USER":www-data "$APP_DIR"
sudo -u "$DEPLOY_USER" git clone --branch "$BRANCH" "$REPO" "$APP_DIR" || true

echo "==> Nginx"
cp "$APP_DIR/deploy/nginx-simpil.conf" /etc/nginx/sites-available/simpil
ln -sf /etc/nginx/sites-available/simpil /etc/nginx/sites-enabled/simpil
rm -f /etc/nginx/sites-enabled/default
nginx -t

echo "==> Supervisor (queue worker)"
cp "$APP_DIR/deploy/supervisor-simpil.conf" /etc/supervisor/conf.d/simpil.conf

echo "==> Izin sudo terbatas untuk deploy (reload layanan tanpa password)"
cat > /etc/sudoers.d/simpil-deploy <<EOF
${DEPLOY_USER} ALL=(root) NOPASSWD: /usr/bin/systemctl reload php${PHP_VER}-fpm, /usr/bin/systemctl reload nginx, /usr/bin/supervisorctl
EOF
chmod 440 /etc/sudoers.d/simpil-deploy

systemctl enable --now php${PHP_VER}-fpm nginx supervisor
systemctl restart php${PHP_VER}-fpm

cat <<MSG

Selesai. KELUAR dari SSH lalu masuk lagi (agar keanggotaan grup www-data aktif), kemudian sebagai ${DEPLOY_USER}:
  cd ${APP_DIR}
  cp .env.production.example .env && nano .env      # isi nilai rahasia
  php artisan key:generate
  bash deploy/deploy.sh
MSG
