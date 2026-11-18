#!/bin/bash
# Pasang/aktifkan backup di EC2 simpil-demo lewat SSM (tanpa SSH), lalu pasang lifecycle S3.
#   bash deploy/backup/install.sh            -> pasang + aktifkan timer harian
#   bash deploy/backup/install.sh --no-timer -> pasang skrip saja (backup manual)
set -euo pipefail
export MSYS_NO_PATHCONV=1
R=ap-southeast-3
HERE=$(cd "$(dirname "$0")" && pwd)
ID=$(aws ec2 describe-instances --region $R --filters Name=tag:Name,Values=simpil-demo Name=instance-state-name,Values=running \
     --query "Reservations[].Instances[].InstanceId" --output text)
[ -n "$ID" ] || { echo "simpil-demo tidak sedang running"; exit 1; }
BUCKET=$(aws ssm get-parameter --name /simpil/env --with-decryption --region $R --query Parameter.Value --output text \
         | grep '^AWS_BUCKET=' | cut -d= -f2 | tr -d '\r')

b64() { base64 -w0 "$HERE/$1"; }
ENABLE="systemctl enable --now simpil-backup.timer"
[ "${1:-}" = "--no-timer" ] && ENABLE="systemctl disable --now simpil-backup.timer || true"

CMDS=$(cat <<C
set -e
echo $(b64 simpil-backup.sh)  | base64 -d > /usr/local/bin/simpil-backup.sh
echo $(b64 simpil-restore.sh) | base64 -d > /usr/local/bin/simpil-restore.sh
chmod 700 /usr/local/bin/simpil-backup.sh /usr/local/bin/simpil-restore.sh
echo $(b64 simpil-backup.service) | base64 -d > /etc/systemd/system/simpil-backup.service
echo $(b64 simpil-backup.timer)   | base64 -d > /etc/systemd/system/simpil-backup.timer
systemctl daemon-reload
$ENABLE
systemctl list-timers simpil-backup.timer --no-pager || true
C
)
PARAMS=$(python -c 'import json,sys; print(json.dumps({"commands": sys.stdin.read().splitlines()}))' <<<"$CMDS")
CID=$(aws ssm send-command --region $R --instance-ids "$ID" --document-name AWS-RunShellScript \
      --parameters "$PARAMS" --query Command.CommandId --output text)
aws ssm wait command-executed --region $R --command-id "$CID" --instance-id "$ID" || true
aws ssm get-command-invocation --region $R --command-id "$CID" --instance-id "$ID" \
    --query "[Status,StandardOutputContent,StandardErrorContent]" --output text

cd "$HERE"
aws s3api put-bucket-lifecycle-configuration --bucket "$BUCKET" --lifecycle-configuration file://s3-lifecycle.json
echo "lifecycle S3 terpasang di $BUCKET"
