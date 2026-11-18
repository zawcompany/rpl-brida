#!/bin/bash
# SIMPIL — rilis kode terbaru ke EC2 (docker-compose.ec2.yml).
#   bash deploy/aws/release.sh            -> tag otomatis demo-<tanggal-jam>
#   bash deploy/aws/release.sh demo-6     -> tag sendiri
# Butuh: Docker Desktop menyala, AWS CLI login, EC2 simpil-demo running.
# Langkah: build image app+web -> push ke ECR -> set /simpil/image-tag -> server pull & up lewat SSM.
# Rollback: jalankan lagi dengan tag lama, mis. `bash deploy/aws/release.sh demo-4` tanpa build
#           (SKIP_BUILD=1 bash deploy/aws/release.sh demo-4).
set -euo pipefail
export MSYS_NO_PATHCONV=1 PYTHONUTF8=1
R=ap-southeast-3
ACCT=$(aws sts get-caller-identity --query Account --output text | tr -d '\r')
REG="$ACCT.dkr.ecr.$R.amazonaws.com"
TAG=${1:-demo-$(date +%Y%m%d-%H%M)}
cd "$(dirname "$0")/../.."

ID=$(aws ec2 describe-instances --region $R --filters Name=tag:Name,Values=simpil-demo Name=instance-state-name,Values=running \
     --query "Reservations[].Instances[].InstanceId" --output text | tr -d '\r')
[ -n "$ID" ] || { echo "EC2 simpil-demo tidak running. Nyalakan dulu."; exit 1; }

if [ "${SKIP_BUILD:-0}" != 1 ]; then
  docker info >/dev/null 2>&1 || { echo "Docker belum menyala. Buka Docker Desktop dulu."; exit 1; }
  echo "==> Build image $TAG"
  docker build --target app -t "$REG/simpil-app:$TAG" .
  docker build --target web -t "$REG/simpil-web:$TAG" .
  echo "==> Push ke ECR"
  # Token ECR terlalu panjang untuk Windows Credential Manager ("The stub received bad data"),
  # jadi login memakai config Docker sementara tanpa credsStore. Config Docker Anda tidak disentuh.
  # Token ditulis langsung ke config (tanpa `docker login`), lalu dihapus setelah push.
  PUSH_CFG=$(mktemp -d)
  trap 'rm -rf "$PUSH_CFG"' EXIT
  AUTH=$(printf 'AWS:%s' "$(aws ecr get-login-password --region $R | tr -d '\r\n')" | base64 -w0)
  printf '{"auths":{"%s":{"auth":"%s"}}}' "$REG" "$AUTH" > "$PUSH_CFG/config.json"
  CFG=$PUSH_CFG; command -v cygpath >/dev/null && CFG=$(cygpath -w "$PUSH_CFG")
  DOCKER_CONFIG="$CFG" docker push "$REG/simpil-app:$TAG"
  DOCKER_CONFIG="$CFG" docker push "$REG/simpil-web:$TAG"
fi

echo "==> Set /simpil/image-tag = $TAG"
aws ssm put-parameter --name /simpil/image-tag --value "$TAG" --type String --overwrite --region $R >/dev/null

echo "==> Server tarik image baru & restart (SSM)"
CID=$(aws ssm send-command --region $R --instance-ids "$ID" --document-name AWS-RunShellScript \
      --parameters '{"commands":["/usr/local/bin/simpil-up.sh 2>&1 | tail -15","cd /opt/simpil && docker compose ps --format \"{{.Service}} {{.Status}}\""]}' \
      --query Command.CommandId --output text)
aws ssm wait command-executed --region $R --command-id "$CID" --instance-id "$ID" || true
aws ssm get-command-invocation --region $R --command-id "$CID" --instance-id "$ID" \
    --query "[Status,StandardOutputContent]" --output text

IP=$(aws ec2 describe-instances --instance-ids "$ID" --region $R --query "Reservations[].Instances[].PublicIpAddress" --output text | tr -d '\r')
sleep 5
echo "==> Cek web: http://$IP -> $(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "http://$IP/")"
