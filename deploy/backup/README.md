# Skema Backup SIMPIL (hemat biaya)

## Apa yang di-backup

| Data | Lokasi | Perlindungan |
|---|---|---|
| Database MySQL | volume Docker `dbdata` di EC2 | **dump harian → S3** `backups/YYYY/MM/DD/db-*.sql.gz` |
| Sampul edisi & storage | volume `publicdisk`/`storage` di EC2 | **arsip harian → S3** `backups/YYYY/MM/DD/storage-*.tar.gz` |
| Naskah | S3 `simpil-docs-<akun>` | sudah aman: versioning S3 aktif |
| Seluruh disk EC2 (opsional) | EBS 20 GB | snapshot mingguan DLM, **tidak aktif** (lihat bawah) |

Jadwal: setiap hari 02:00 WIB (systemd timer, `Persistent=true`: kalau instance sedang mati,
backup jalan begitu instance menyala). Retensi 14 hari, diatur oleh lifecycle S3, jadi tidak ada skrip pembersih.

## Biaya

- Dump DB + sampul untuk data demo: beberapa MB/hari × 14 hari ≈ < 1 GB → **≈ $0,00–0,03/bulan**.
- Tidak ada AWS Backup, RDS, atau layanan berbayar baru. Akses S3 lewat IAM Role yang sudah ada (izinnya sudah cukup).
- Lifecycle juga **menghemat**: versi lama naskah dihapus setelah 30 hari (3 versi terbaru tetap disimpan).
- Snapshot EBS (opsional): ≈ $0,055/GB-bulan untuk blok terpakai, jadi ≈ $0,2–1/bulan. Karena itu dibiarkan mati.

## Berkas

- `simpil-backup.sh`: dump (`--single-transaction`, tanpa mengunci situs) + tar storage, cek integritas, unggah ke S3.
- `simpil-restore.sh`: tanpa argumen menampilkan daftar backup; `simpil-restore.sh 2026/11/18 [db]` memulihkan dan membuat backup darurat dulu.
- `simpil-backup.service` / `.timer`: jadwal harian.
- `s3-lifecycle.json`: retensi.
- `dlm-ebs-snapshot.json`: snapshot EBS mingguan (opsional).
- `install.sh`: memasang semuanya ke EC2 lewat SSM (tanpa SSH) dan memasang lifecycle.

## Cara pakai (dari laptop, Git Bash, di root repo; EC2 harus running)

```bash
bash deploy/backup/install.sh              # pasang + aktifkan backup harian
bash deploy/backup/install.sh --no-timer   # pasang skrip saja, backup hanya manual
```

Backup manual / pulihkan (lewat Session Manager: konsol EC2 → Connect → Session Manager):

```bash
sudo simpil-backup.sh
sudo simpil-restore.sh                 # daftar
sudo simpil-restore.sh 2026/11/18      # pulihkan DB + storage
```

Aktifkan snapshot EBS (opsional, butuh role default DLM):

```bash
aws dlm create-default-role --region ap-southeast-3
aws dlm create-lifecycle-policy --region ap-southeast-3 --state ENABLED \
  --description "simpil mingguan" \
  --execution-role-arn arn:aws:iam::940253214690:role/AWSDataLifecycleManagerDefaultRole \
  --policy-details file://deploy/backup/dlm-ebs-snapshot.json
```

## Catatan

- Saat teardown (`deploy/aws/TEARDOWN.md`), folder `backups/` ikut terhapus bersama bucket. Unduh dulu kalau masih perlu:
  `aws s3 sync s3://simpil-docs-940253214690/backups ./backups-arsip`
- Uji pemulihan minimal sekali, karena backup yang belum pernah diuji belum terbukti bisa dipakai.
