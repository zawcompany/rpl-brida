# SIMPIL — Deploy Docker ke AWS (ECS Fargate)

Rekomendasi: **ECS Fargate** (aplikasi sudah stateless: berkas di S3, DB di RDS, sesi/cache/antrean di Redis, log ke stderr).
Tanpa patching server, deploy rolling di belakang ALB, mudah di-scale. Alternatif tetap ada: EC2 tunggal memakai
`deploy/provision-ec2.sh` + `deploy/deploy.sh` (lebih murah untuk trafik kecil, tetapi OS Anda kelola sendiri).

## Arsitektur
```
Internet → ALB (HTTPS, ACM) → ECS Service "simpil-web" (Fargate, private subnet)
                                 task: [nginx :80] + [php-fpm :9000]  (localhost)
                               ECS Service "simpil-worker" (image app, queue:work)
        RDS MySQL (private)  ·  ElastiCache Redis  ·  S3 docs (privat)  ·  S3 covers → CloudFront  ·  SES
```

## 1. Satu kali: sumber daya
- **ECR**: dua repositori `simpil-app` dan `simpil-web`.
- **RDS MySQL** di private subnet; security group hanya menerima 3306 dari SG task ECS. Aktifkan Multi-AZ + automated backup.
- **ElastiCache Redis** (cache, sesi, antrean), SG hanya dari SG task.
- **S3**: `simpil-docs-prod` (privat, Block Public Access ON) dan `simpil-covers-prod` (dilayani CloudFront OAC; lihat `deploy/aws/covers-bucket-policy.json`).
- **Secrets Manager**: `simpil/app-key`, `simpil/db-password`, `simpil/redis-auth`. Nilai tidak pernah masuk image/repo.
- **CloudWatch Logs**: grup `/ecs/simpil`.
- **ALB**: listener 443 (sertifikat ACM) → target group tipe `ip`, port 80, health check `GET /up` (kode 200).

## 2. Peran IAM
**Task role** (dipakai aplikasi → S3 & SES) — trust policy:
```json
{ "Version": "2012-10-17", "Statement": [{ "Effect": "Allow",
  "Principal": { "Service": "ecs-tasks.amazonaws.com" }, "Action": "sts:AssumeRole" }] }
```
Izin: pakai `deploy/aws/ec2-role-policy.json` apa adanya (least-privilege S3 untuk dua bucket + SES).
Dengan role ini `AWS_ACCESS_KEY_ID/SECRET` **tidak diisi** — SDK mengambil kredensial otomatis.

**Execution role** (dipakai ECS untuk menarik image & secret): lampirkan `AmazonECSTaskExecutionRolePolicy` dan izin
`secretsmanager:GetSecretValue` pada tiga secret di atas.

## 3. Build & push image
```bash
REGION=ap-southeast-3; ACCT=<ACCOUNT_ID>; TAG=$(git rev-parse --short HEAD)
aws ecr get-login-password --region $REGION | docker login --username AWS --password-stdin $ACCT.dkr.ecr.$REGION.amazonaws.com

docker build --target app -t $ACCT.dkr.ecr.$REGION.amazonaws.com/simpil-app:$TAG .
docker build --target web -t $ACCT.dkr.ecr.$REGION.amazonaws.com/simpil-web:$TAG .
docker push $ACCT.dkr.ecr.$REGION.amazonaws.com/simpil-app:$TAG
docker push $ACCT.dkr.ecr.$REGION.amazonaws.com/simpil-web:$TAG
```

## 4. Deploy
1. Isi placeholder `<...>` di `deploy/aws/ecs-task-definition.json` lalu `aws ecs register-task-definition --cli-input-json file://deploy/aws/ecs-task-definition.json`.
2. **Migrasi** sebagai satu task sekali jalan (bukan di setiap container):
   ```bash
   aws ecs run-task --cluster simpil --launch-type FARGATE --task-definition simpil-web \
     --network-configuration "awsvpcConfiguration={subnets=[<private-subnet>],securityGroups=[<sg-task>]}" \
     --overrides '{"containerOverrides":[{"name":"app","command":["php","artisan","migrate","--force"]}]}'
   ```
3. Buat/ubah service `simpil-web` (desired count ≥ 2, load balancer ke container `web:80`) agar memakai revisi task terbaru.
4. Service `simpil-worker`: task definition kedua dari image `simpil-app` saja dengan command
   `php artisan queue:work redis --sleep=3 --tries=3 --backoff=10 --max-time=3600`, tanpa ALB.
5. Rollback: ubah service ke revisi task definition sebelumnya (tag image lama).

## 5. Env penting (rincian lengkap: `.env.production.example`)
| Variabel | Nilai |
|---|---|
| `MANUSCRIPT_DISK` / `AWS_BUCKET` | `s3` / `simpil-docs-prod` |
| `COVER_DISK` / `AWS_PUBLIC_BUCKET` / `AWS_PUBLIC_URL` | `s3_public` / bucket sampul / domain CloudFront |
| `DB_HOST` / `MYSQL_ATTR_SSL_CA` | endpoint RDS / `/etc/ssl/certs/rds-global-bundle.pem` (sudah ada di image) |
| `FORCE_HTTPS`, `TRUSTED_PROXIES`, `SESSION_SECURE_COOKIE` | `true`, `*`, `true` (di belakang ALB) |
| `APP_KEY`, `DB_PASSWORD`, `REDIS_PASSWORD` | dari Secrets Manager (`secrets`, bukan `environment`) |

`config/filesystems.php` dan `composer.json` (`league/flysystem-aws-s3-v3`) **sudah siap** untuk S3; tidak perlu diubah.

## 6. Cache Laravel di container
`docker/entrypoint.sh` menjalankan `config:cache`, `route:cache`, `view:cache`, `event:cache` saat start bila `APP_ENV=production`
(bukan saat build, karena env baru tersedia saat runtime). OPcache `validate_timestamps=0` karena kode tidak berubah dalam container.
