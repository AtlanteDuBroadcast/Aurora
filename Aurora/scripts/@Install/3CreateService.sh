#!/bin/bash

# ============================================================

# Création des services Aurora

# ============================================================

# Rendre les scripts exécutables

chmod +x /opt/docker/Aurora/scripts/system_control_api.py
chmod +x /opt/docker/Aurora/scripts/usb-commande.sh

# Installation des dépendances
sudo apt install python3-pip -y

# ============================================================

# Service System Control API

# ============================================================

cat > /etc/systemd/system/system_control_api.service <<'EOF'
[Unit]
Description=System Control API Service
After=network.target

[Service]
Type=simple
ExecStart=/usr/bin/python3 /opt/docker/Aurora/scripts/system_control_api.py
Restart=on-failure
User=root
WorkingDirectory=/opt/docker/Aurora/scripts

[Install]
WantedBy=multi-user.target
EOF

chmod 744 /opt/docker/Aurora/scripts/system_control_api.py
chmod 644 /etc/systemd/system/system_control_api.service

# ============================================================

# Service Control USB

# ============================================================

cat > /etc/systemd/system/control-usb.service <<'EOF'
[Unit]
Description=System Control USB Commande Service
After=network.target

[Service]
Type=simple
ExecStart=/opt/docker/Aurora/scripts/usb-commande.sh
Restart=on-failure
User=root
WorkingDirectory=/opt/docker/Aurora/scripts

[Install]
WantedBy=multi-user.target
EOF

chmod 744 /opt/docker/Aurora/scripts/usb-commande.sh
chmod 644 /etc/systemd/system/control-usb.service

# ============================================================

# Rechargement de systemd

# ============================================================

systemctl daemon-reload

# ============================================================

# Activation au démarrage

# ============================================================

systemctl enable system_control_api.service
systemctl enable control-usb.service

# ============================================================

# Démarrage

# ============================================================

systemctl restart system_control_api.service
systemctl restart control-usb.service

# ============================================================

# Vérification

# ============================================================

echo ""
echo "=========================================="
echo " État des services"
echo "=========================================="

systemctl --no-pager status system_control_api.service
echo ""
systemctl --no-pager status control-usb.service

echo ""
echo "=========================================="
echo " Installation des services terminée"
echo "=========================================="
