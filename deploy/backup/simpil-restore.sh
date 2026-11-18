#!/bin/bash
# SIMPIL — pulihkan dari backup S3.
#   simpil-restore.sh                 -> daftar backup yang ada
#   simpil-restore.sh 2026/11/18      -> pulihkan backup TERAKHIR pada tanggal itu (DB + storage)
#   simpil-restore.sh 2026/11/18 db   -> hanya database
# MENIMPA data saat ini. Sebelum menimpa, skrip membuat backup darurat dulu.
set -euo pipefail
R=ap-southeast-3
DIR=/opt/simpil
BUCKET=$(grep '^AWS_BUCKET=' "$DIR/.env" | cut -d= -f2)
cd "$DIR"

if [ $# -eq 0 ]; then
  aws s3 ls "s3://$BUCKET/backups/" --recursive --region "$R" | awk '{print $1, $2, $3, $4}'
  exit 0
fi
DAY=$1; WHAT=${2:-all}
DB_KEY=$(aws s3 ls "s3://$BUCKET/backups/$DAY/" --region "$R" | awk '{print $4}' | grep '^db-' | sort | tail -1)
ST_KEY=$(aws s3 ls "s3://$BUCKET/backups/$DAY/" --region "$R" | awk '{print $4}' | grep '^storage-' | sort | tail -1)
[ -n "$DB_KEY" ] || { echo "Tidak ada backup pada $DAY"; exit 1; }

read -r -p "Timpa data sekarang dengan backup $DAY ($DB_KEY)? ketik YA: " ok
[ "$ok" = "YA" ] || exit 1
/usr/local/bin/simpil-backup.sh   # backup darurat kondisi sekarang

TMP=$(mktemp -d); trap 'rm -rf "$TMP"' EXIT
aws s3 cp "s3://$BUCKET/backups/$DAY/$DB_KEY" "$TMP/db.sql.gz" --region "$R" --only-show-errors
docker compose stop queue app
zcat "$TMP/db.sql.gz" | docker compose exec -T db sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'

if [ "$WHAT" = all ] && [ -n "$ST_KEY" ]; then
  aws s3 cp "s3://$BUCKET/backups/$DAY/$ST_KEY" "$TMP/st.tar.gz" --region "$R" --only-show-errors
  docker compose start app
  docker compose exec -T -u root app tar xzf - -C /var/www/html/storage < "$TMP/st.tar.gz"
  docker compose exec -T -u root app chown -R www-data:www-data /var/www/html/storage
fi
docker compose start app queue
echo "restore selesai dari $DAY"
