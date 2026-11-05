# Teardown — hapus semua sumber daya demo SIMPIL (supaya tidak ada tagihan)

Region `ap-southeast-3`, akun `940253214690`. Jalankan di PowerShell. Urutan penting.

```powershell
$R = "ap-southeast-3"; $A = "940253214690"; $B = "simpil-docs-$A"

# 1. EC2: hentikan penagihan komputasi (terminate = hapus permanen beserta disk)
$ids = aws ec2 describe-instances --region $R --filters "Name=tag:Name,Values=simpil-demo" "Name=instance-state-name,Values=pending,running,stopping,stopped" --query "Reservations[].Instances[].InstanceId" --output text
aws ec2 terminate-instances --instance-ids $ids --region $R

# 2. Security group (setelah instance benar-benar terminated)
aws ec2 delete-security-group --group-name simpil-sg --region $R

# 3. CloudWatch alarm & SNS
aws cloudwatch delete-alarms --alarm-names simpil-cpu-tinggi simpil-status-check --region $R
aws sns delete-topic --topic-arn arn:aws:sns:${R}:${A}:simpil-alerts --region $R

# 4. SQS
aws sqs delete-queue --queue-url https://sqs.$R.amazonaws.com/$A/simpil-queue --region $R
aws sqs delete-queue --queue-url https://sqs.$R.amazonaws.com/$A/simpil-queue-dlq --region $R

# 5. ECR (--force menghapus image di dalamnya)
aws ecr delete-repository --repository-name simpil-app --force --region $R
aws ecr delete-repository --repository-name simpil-web --force --region $R

# 6. CloudWatch Logs & SSM
aws logs delete-log-group --log-group-name /simpil/ec2 --region $R
aws ssm delete-parameter --name /simpil/env --region $R
aws ssm delete-parameter --name /simpil/image-tag --region $R

# 7. SES identitas email
aws sesv2 delete-email-identity --email-identity wildaniza23d@student.unhas.ac.id --region $R

# 8. IAM
aws iam remove-role-from-instance-profile --instance-profile-name simpil-ec2-profile --role-name simpil-ec2-role
aws iam delete-instance-profile --instance-profile-name simpil-ec2-profile
aws iam detach-role-policy --role-name simpil-ec2-role --policy-arn arn:aws:iam::aws:policy/AmazonSSMManagedInstanceCore
aws iam delete-role-policy --role-name simpil-ec2-role --policy-name simpil-app
aws iam delete-role --role-name simpil-ec2-role

# 9. S3 (versioning aktif: hapus semua versi dulu)
aws s3api list-object-versions --bucket $B --output json --query "{Objects: Versions[].{Key:Key,VersionId:VersionId}}" > $env:TEMP\v.json
aws s3api delete-objects --bucket $B --delete file://$env:TEMP/v.json    # ulangi untuk DeleteMarkers bila ada
aws s3 rb s3://$B --force
```

## Verifikasi (harus kosong)
```powershell
aws ec2 describe-instances --region $R --query "Reservations[].Instances[?State.Name!='terminated'].InstanceId" --output text
aws ec2 describe-volumes --region $R --query "Volumes[].VolumeId" --output text
aws ec2 describe-addresses --region $R --query "Addresses[].PublicIp" --output text
aws s3 ls
```
Besok cek *Billing → Bills* di konsol: tidak boleh ada layanan yang masih berjalan.

Catatan: AWS Budgets `simpil-bulanan` boleh dibiarkan (gratis).
