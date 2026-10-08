# Aurora
Gestion serveur Linux (API &amp; WebPage)

Nécessite d'avoir docker & docker compose sur le linux


wget https://github.com/AtlanteDuBroadcast/Aurora/archive/refs/heads/main.zip

apt install -y unzip

unzip main.zip

cd Aurora-main

mkdirr /opt/docker

cp -r Aurora-main /opt/docker/Aurora

cd /opt/docker/Aurora/

cd /opt/docker/Aurora/scripts/@Install

chmod +x *.sh

./0install.sh
