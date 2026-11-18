#!/bin/bash
# SIMPIL — backup harian: dump MySQL + arsip storage/app (sampul edisi) ke S3.
# Naskah tidak ikut karena sudah di S3 dengan versioning. Tidak ada rahasia di skrip ini:
# kata sandi DB dibaca di DALAM container db, akses S3 lewat IAM Role instance.
set -euo pipefail
R=ap-southeast-3
DIR=/opt/simpil
BUCKET=$(grep '^AWS_BUCKET=' "$DIR/.env" | cut -d= -f2)
PREFIX="backups/$(date -u +%Y/%m/%d)"
STAMP=$(date -u +%Y%m%dT%H%M%SZ)
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
cd "$DIR"

# --single-transaction: snapshot konsisten InnoDB tanpa mengunci tabel (situs tetap jalan).
docker compose exec -T db sh -c \
  'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --quick --routines --triggers --no-tablespaces "$MYSQL_DATABASE"' \
  | gzip -9 > "$TMP/db-$STAMP.sql.gz"
# Dump kosong/terpotong = gagal; jangan unggah file rusak.
gzip -t "$TMP/db-$STAMP.sql.gz"
[ "$(zcat "$TMP/db-$STAMP.sql.gz" | tail -1 | grep -c 'Dump completed')" = 1 ]

docker compose exec -T app tar czf - -C /var/www/html/storage app > "$TMP/storage-$STAMP.tar.gz"

for f in "$TMP"/*.gz; do
  aws s3 cp "$f" "s3://$BUCKET/$PREFIX/$(basename "$f")" --region "$R" --only-show-errors
done
echo "backup OK: s3://$BUCKET/$PREFIX/ ($(du -ch "$TMP"/*.gz | tail -1 | cut -f1))"
