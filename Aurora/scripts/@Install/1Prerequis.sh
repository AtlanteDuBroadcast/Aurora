#!/bin/bash
sudo apt update
#sudo apt install python3 python3-pip
sudo apt install python3-requests -y
#sudo pip3 install flask
sudo apt install python3-flask -y
sudo apt install python3-venv python3-full -y
sudo apt install netplan.io -y
sudo systemctl enable --now systemd-timesyncd
echo "Tous les scripts ont été exécutés."