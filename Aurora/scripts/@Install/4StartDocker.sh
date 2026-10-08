#!/bin/bash

#Update docker

cat > /etc/apt/sources.list.d/docker.sources <<'EOF'
Types: deb
URIs: https://download.docker.com/linux/debian
Suites: trixie
Components: stable
Architectures: amd64
Signed-By: /etc/apt/keyrings/docker.asc
EOF

apt update

apt remove -y docker-buildx

apt install -y docker-buildx-plugin

cd /opt/docker/Aurora

sudo docker compose down -v
docker compose build
docker compose up -d

sleep 60

# --- CONFIG ---
DB_HOST="127.0.0.1"
DB_PORT="53306"
DB_ROOT_PASS="root_P@ssW0rds"
DB_NAME="Aurora_MyDataBase"
APP_USER="Aurora"
APP_PASS="Aurora_password"

echo "==> Installation du client MariaDB"
sudo apt install -y mariadb-client

echo "==> Test de connexion avec root..."

mysql --skip-ssl -h "$DB_HOST" -P "$DB_PORT" -u root -p"$DB_ROOT_PASS" -e "SELECT VERSION();" || {
  echo "❌ Connexion root impossible."
  exit 1
}

echo "==> Application des modifications pour l’utilisateur $APP_USER"

mysql --skip-ssl -h "$DB_HOST" -P "$DB_PORT" -u root -p"$DB_ROOT_PASS" <<EOF
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS '$APP_USER'@'%' IDENTIFIED BY '$APP_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$APP_USER'@'%';
FLUSH PRIVILEGES;
EOF

if [ $? -eq 0 ]; then
  echo "✅ Utilisateur '$APP_USER' créé et droits appliqués sur '$DB_NAME'"
else
  echo "❌ Erreur lors de l’application des modifications"
fi