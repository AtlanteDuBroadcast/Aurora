#!/bin/bash

# ============================================================

# Installation Docker - Debian 13 (Trixie)

# ============================================================

# Vérification root

if [ "$EUID" -ne 0 ]; then
echo "Veuillez exécuter ce script avec sudo :"
echo "sudo ./InstallDocker.sh"
exit 1
fi

echo "=========================================="
echo " Installation Docker - Debian 13"
echo "=========================================="

# ------------------------------------------------------------

# 1. Mise à jour du système

# ------------------------------------------------------------

echo ""
echo "[1/6] Mise à jour du système..."

apt update
apt upgrade -y

# ------------------------------------------------------------

# 2. Installation des dépendances

# ------------------------------------------------------------

echo ""
echo "[2/6] Installation des dépendances..."

apt install -y ca-certificates curl gnupg dos2unix

# ------------------------------------------------------------

# 3. Ajout de la clé GPG Docker

# ------------------------------------------------------------

echo ""
echo "[3/6] Ajout de la clé GPG Docker..."

install -m 0755 -d /etc/apt/keyrings

curl -fsSL https://download.docker.com/linux/debian/gpg 
-o /etc/apt/keyrings/docker.asc

chmod a+r /etc/apt/keyrings/docker.asc

# ------------------------------------------------------------

# 4. Ajout du dépôt Docker officiel

# ------------------------------------------------------------

echo ""
echo "[4/6] Ajout du dépôt Docker officiel..."

echo 
"deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian 
$(. /etc/os-release && echo "$VERSION_CODENAME") stable" | 
tee /etc/apt/sources.list.d/docker.list > /dev/null

apt update

# ------------------------------------------------------------

# 5. Installation de Docker

# ------------------------------------------------------------

echo ""
echo "[5/6] Installation de Docker..."

apt install -y 
docker-ce 
docker-ce-cli 
containerd.io 
docker-buildx-plugin 
docker-compose-plugin

# ------------------------------------------------------------

# 6. Activation et démarrage de Docker

# ------------------------------------------------------------

echo ""
echo "[6/6] Activation de Docker..."

systemctl enable docker
systemctl start docker

# ------------------------------------------------------------

# Vérification

# ------------------------------------------------------------

echo ""
echo "=========================================="
echo " Vérification de l'installation"
echo "=========================================="

echo ""
echo "Version Docker :"
docker --version

echo ""
echo "Version Docker Compose :"
docker compose version

echo ""
echo "État du service Docker :"
systemctl --no-pager status docker

echo ""
echo "=========================================="
echo " Installation terminée !"
echo "=========================================="

