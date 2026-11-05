# Membuat .env produksi (rahasia acak baru) dan menyimpannya sebagai SecureString di SSM Parameter Store.
# Jalankan SENDIRI di PowerShell (nilai rahasia tidak dicetak dan tidak disimpan ke file proyek):
#   powershell -ExecutionPolicy Bypass -File deploy\aws\put-env-ssm.ps1
$ErrorActionPreference = "Stop"
$R = "ap-southeast-3"; $A = "940253214690"; $mail = "wildaniza23d@student.unhas.ac.id"

function Hex($n) { $b = New-Object byte[] $n; [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($b); ($b | ForEach-Object { $_.ToString('x2') }) -join '' }
$k = New-Object byte[] 32; [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($k)
$appkey = "base64:" + [Convert]::ToBase64String($k)
$dbp = Hex 16; $dbr = Hex 16

$envText = @"
APP_NAME=SIMPIL
APP_ENV=production
APP_KEY=$appkey
APP_DEBUG=false
LOG_CHANNEL=stderr
ECR_REGISTRY=$A.dkr.ecr.$R.amazonaws.com
IMAGE_TAG=demo-1
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=simpil
DB_USERNAME=simpil
DB_PASSWORD=$dbp
DB_ROOT_PASSWORD=$dbr
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sqs
SQS_PREFIX=https://sqs.$R.amazonaws.com/$A
SQS_QUEUE=simpil-queue
AWS_DEFAULT_REGION=$R
FILESYSTEM_DISK=local
MANUSCRIPT_DISK=s3
AWS_BUCKET=simpil-docs-$A
COVER_DISK=public
MAIL_MAILER=ses
MAIL_FROM_ADDRESS="$mail"
MAIL_FROM_NAME="SIMPIL"
"@ -replace "`r`n", "`n"

$tmp = Join-Path $env:TEMP "simpil.env"
[IO.File]::WriteAllText($tmp, $envText)
try {
    aws ssm put-parameter --name /simpil/env --type SecureString --overwrite --value "file://$tmp" --region $R | Out-Null
    "Tersimpan: /simpil/env (versi baru). Nilai rahasia tidak ditampilkan."
} finally {
    Remove-Item $tmp -Force -ErrorAction SilentlyContinue
}
