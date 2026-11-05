#!/bin/bash
# SIMPIL — skrip boot EC2 (user-data). Penanda isi compose (dua tanda @ + COMPOSE + dua tanda @) di bawah
# diganti isi docker-compose.ec2.yml saat peluncuran. Jangan menulis penanda itu di komentar mana pun.
# Tidak berisi rahasia: .env diambil dari SSM Parameter Store (/simpil/env, SecureString) lewat IAM Role.
# Dijalankan ulang setiap boot (systemd) agar APP_URL mengikuti IP publik yang baru setelah stop/start.
set -eux
R=ap-southeast-3

dnf install -y docker
systemctl enable --now docker
mkdir -p /usr/local/lib/docker/cli-plugins
curl -fsSL https://github.com/docker/compose/releases/download/v2.29.7/docker-compose-linux-x86_64 \
  -o /usr/local/lib/docker/cli-plugins/docker-compose
chmod +x /usr/local/lib/docker/cli-plugins/docker-compose

mkdir -p /opt/simpil
cat > /opt/simpil/docker-compose.yml <<'COMPOSE_EOF'
@@COMPOSE@@
COMPOSE_EOF

cat > /usr/local/bin/simpil-up.sh <<'UP_EOF'
#!/bin/bash
set -eux
R=ap-southeast-3
cd /opt/simpil
TOKEN=$(curl -sX PUT http://169.254.169.254/latest/api/token -H "X-aws-ec2-metadata-token-ttl-seconds: 300")
IP=$(curl -s -H "X-aws-ec2-metadata-token: $TOKEN" http://169.254.169.254/latest/meta-data/public-ipv4)
aws ssm get-parameter --name /simpil/env --with-decryption --region "$R" --query Parameter.Value --output text > .env
chmod 600 .env
# Tag image bukan rahasia: dibaca dari parameter terpisah, jadi rilis baru tidak menyentuh .env rahasia.
TAG=$(aws ssm get-parameter --name /simpil/image-tag --region "$R" --query Parameter.Value --output text 2>/dev/null || true)
if [ -n "$TAG" ]; then sed -i "s/^IMAGE_TAG=.*/IMAGE_TAG=$TAG/" .env; fi
echo "APP_URL=http://$IP" >> .env
REG=$(grep '^ECR_REGISTRY=' .env | cut -d= -f2)
aws ecr get-login-password --region "$R" | docker login --username AWS --password-stdin "$REG"
docker compose pull
docker compose up -d
UP_EOF
chmod +x /usr/local/bin/simpil-up.sh

cat > /etc/systemd/system/simpil.service <<'UNIT_EOF'
[Unit]
Description=SIMPIL (docker compose)
After=docker.service network-online.target
Requires=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
ExecStart=/usr/local/bin/simpil-up.sh
ExecStop=/usr/bin/docker compose -f /opt/simpil/docker-compose.yml down

[Install]
WantedBy=multi-user.target
UNIT_EOF
systemctl daemon-reload
systemctl enable --now simpil.service
