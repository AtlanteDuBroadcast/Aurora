#!/usr/bin/env python3
#---------------------------------------------------------------------------------------------------------------
#-- Version : 2026-03-18 - Ajout d'une fonction restart service
#-- Version : 2026-09-03 - Ajout d'une fonction force reboot service
#---------------------------------------------------------------------------------------------------------------
import os
import subprocess
import logging
from flask import Flask, request, jsonify
import yaml

# 🔹 Configuration du logging
from datetime import datetime

app = Flask(__name__)

TOKEN = "supersecrettoken123"  # 🔑 Sécurité simple via token


# 🔹 Générer le nom du fichier avec la date du jour
date_str = datetime.now().strftime("%Y-%m-%d")
LOG_FILE = f"/opt/docker/Aurora/scripts/log/log-API_AURORA_{date_str}.log"
logging.basicConfig(
    filename=LOG_FILE,
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)

# 🔹 Lire le fichier NTP
try:
    with open("/etc/systemd/timesyncd.conf", "r") as f:
        lines = f.readlines()
    ntp_lines = [line.strip() for line in lines if line.startswith("NTP=")]
    if ntp_lines:
        logging.info(f"NTP servers at boot: {ntp_lines[0][4:]}")
    else:
        logging.info("No NTP servers configured at boot")
except Exception as e:
    logging.error(f"Error reading NTP config at boot: {e}")

# 🔹 Fonction pour générer le chemin du fichier Netplan dynamiquement
def get_netplan_file(interface):
    return f"/etc/netplan/01-{interface}.yaml"

# 🔹 Vérification du token
def check_token(req):
    auth = req.headers.get("Authorization", "")
    return auth == f"Bearer {TOKEN}"

# 🔹 Recharger netplan
def apply_netplan():
    subprocess.run(["netplan", "apply"], check=True)
    logging.info("Netplan reloaded")

# 🔹 Modifier configuration réseau (statique ou DHCP) avec DNS
def write_netplan_config(interface, use_dhcp=True, ip=None, prefix=None, gateway=None, dns=None):
    netplan_file = get_netplan_file(interface)

    config = {
        'network': {
            'version': 2,
            'ethernets': {
                interface: {}
            }
        }
    }

    if use_dhcp:
        config['network']['ethernets'][interface]['dhcp4'] = True
        logging.info(f"Set {interface} to DHCP")
    else:
        config['network']['ethernets'][interface]['dhcp4'] = False
        config['network']['ethernets'][interface]['addresses'] = [f"{ip}/{prefix}"]
        if gateway:
            config['network']['ethernets'][interface]['routes'] = [{'to': '0.0.0.0/0', 'via': gateway}]
        dns = [d for d in dns if d] 
        if dns:
            config['network']['ethernets'][interface]['nameservers'] = {'addresses': dns}
        logging.info(f"Set {interface} static IP {ip}/{prefix}, gateway {gateway}, dns {dns}")

    with open(netplan_file, "w") as f:
        yaml.dump(config, f, default_flow_style=False)

    apply_netplan()

# 🔹 API : Changer IP / DHCP
@app.route("/set-network", methods=["POST"])
def set_network():
    if not check_token(request):
        logging.warning("Unauthorized attempt on /set-network")
        return jsonify({"error": "Unauthorized"}), 403

    data = request.json
    interface = data.get("interface", "eth0")
    use_dhcp = data.get("dhcp", False)

    try:
        if use_dhcp:
            write_netplan_config(interface, use_dhcp=True)
        else:
            ip = data["ip"]
            prefix = data["prefix"]
            gateway = data["gateway"]
            dns = data.get("dns", [])
            write_netplan_config(interface, use_dhcp=False, ip=ip, prefix=prefix, gateway=gateway, dns=dns)

        return jsonify({"status": "ok", "mode": "dhcp" if use_dhcp else "static"})
    except Exception as e:
        logging.error(f"Error setting network on {interface}: {e}")
        return jsonify({"error": str(e)}), 500

# 🔹 API : Changer le hostname
@app.route("/set-hostname", methods=["POST"])
def set_hostname():
    if not check_token(request):
        logging.warning("Unauthorized attempt on /set-hostname")
        return jsonify({"error": "Unauthorized"}), 403

    data = request.json
    hostname = data["hostname"]

    try:
        subprocess.run(["hostnamectl", "set-hostname", hostname], check=True)
        logging.info(f"Hostname changed to {hostname}")
        return jsonify({"status": "ok", "hostname": hostname})
    except Exception as e:
        logging.error(f"Error setting hostname: {e}")
        return jsonify({"error": str(e)}), 500

# 🔹 API : Configurer DNS uniquement
@app.route("/set-dns", methods=["POST"])
def set_dns():
    if not check_token(request):
        logging.warning("Unauthorized attempt on /set-dns")
        return jsonify({"error": "Unauthorized"}), 403

    data = request.json
    interface = data.get("interface", "eth0")
    dns = data.get("dns", [])

    try:
        netplan_file = get_netplan_file(interface)
        if os.path.exists(netplan_file):
            with open(netplan_file, "r") as f:
                config = yaml.safe_load(f)
        else:
            config = {'network': {'version': 2, 'ethernets': {}}}

        if 'network' not in config:
            config['network'] = {'version': 2, 'ethernets': {}}
        if 'ethernets' not in config['network']:
            config['network']['ethernets'] = {}
        if interface not in config['network']['ethernets']:
            config['network']['ethernets'][interface] = {}

        config['network']['ethernets'][interface]['nameservers'] = {'addresses': dns}

        with open(netplan_file, "w") as f:
            yaml.dump(config, f, default_flow_style=False)

        apply_netplan()
        logging.info(f"DNS set on {interface}: {dns}")
        return jsonify({"status": "ok", "dns": dns})
    except Exception as e:
        logging.error(f"Error setting DNS on {interface}: {e}")
        return jsonify({"error": str(e)}), 500

# 🔹 API : Configurer NTP
@app.route("/set-ntp", methods=["POST"])
def set_ntp():
    if not check_token(request):
        logging.warning("Unauthorized attempt on /set-ntp")
        return jsonify({"error": "Unauthorized"}), 403

    data = request.json
    servers = data.get("servers", [])

    try:
        # Désactiver d'abord
        subprocess.run(["timedatectl", "set-ntp", "false"], check=True)

        # Construire la config
        config = "[Time]\nNTP=" + " ".join(servers) + "\n"
        with open("/etc/systemd/timesyncd.conf", "w") as f:
            f.write(config)

        # Activer et démarrer systemd-timesyncd
        subprocess.run(["systemctl", "enable", "--now", "systemd-timesyncd"], check=True)

        # Redémarrer pour recharger la conf
        subprocess.run(["systemctl", "restart", "systemd-timesyncd"], check=True)

        logging.info(f"NTP servers set: {servers}")
        return jsonify({"status": "ok", "ntp": servers})

    except Exception as e:
        logging.error(f"Error setting NTP: {e}")
        return jsonify({"error": str(e)}), 500



# 🔹 API : Reboot
@app.route("/reboot", methods=["POST"])
def reboot():
    if not check_token(request):
        logging.warning("Unauthorized attempt on /reboot")
        return jsonify({"error": "Unauthorized"}), 403

    try:
        subprocess.Popen(["reboot"])
        logging.info("System reboot initiated")
        return jsonify({"status": "rebooting"})
    except Exception as e:
        logging.error(f"Error rebooting: {e}")
        return jsonify({"error": str(e)}), 500

        
# 🔹 API : Reboot forcé
@app.route("/forcereboot", methods=["POST"])
def forcereboot():
    if not check_token(request):
        logging.warning("Unauthorized attempt on /forcereboot")
        return jsonify({"error": "Unauthorized"}), 403

    try:
        subprocess.Popen([
            "sudo",
            "systemctl",
            "reboot",
            "--force",
            "--force"
        ])

        logging.info("Forced system reboot initiated")
        return jsonify({"status": "rebooting"})

    except Exception as e:
        logging.error(f"Error rebooting: {e}")
        return jsonify({"error": str(e)}), 500


# 🔹 API : Shutdown
@app.route("/shutdown", methods=["POST"])
def shutdown():
    if not check_token(request):
        logging.warning("Unauthorized attempt on /shutdown")
        return jsonify({"error": "Unauthorized"}), 403

    try:
        subprocess.Popen(["shutdown", "now"])
        logging.info("System shutdown initiated")
        return jsonify({"status": "shutting down"})
    except Exception as e:
        logging.error(f"Error shutting down: {e}")
        return jsonify({"error": str(e)}), 500
        
        
# 🔹 API : Update
@app.route("/update", methods=["POST"])
def update():
    if not check_token(request):
        logging.warning("Unauthorized attempt on /update")
        return jsonify({"error": "Unauthorized"}), 403

    try:
        subprocess.Popen(
             ["apt", "upgrade", "--fix-missing", "-y"],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL
        )

        logging.info("APT update initiated")
        return jsonify({"status": "update started"})

    except Exception as e:
        logging.error(f"Error during apt update: {e}")
        return jsonify({"error": str(e)}), 500


# 🔹 API : Upgrade
@app.route("/upgrade", methods=["POST"])
def upgrade():
    if not check_token(request):
        logging.warning("Unauthorized attempt on /upgrade")
        return jsonify({"error": "Unauthorized"}), 403

    try:
        subprocess.Popen(
            ["apt", "--fix-missing", "-y"],
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL
        )

        logging.info("APT upgrade initiated")
        return jsonify({"status": "upgrade started"})

    except Exception as e:
        logging.error(f"Error during apt upgrade: {e}")
        return jsonify({"error": str(e)}), 500

# 🔹 API : Configurer /etc/hosts
@app.route("/set-hosts", methods=["POST"])
def set_hosts():
    if not check_token(request):
        logging.warning("Unauthorized attempt on /set-hosts")
        return jsonify({"error": "Unauthorized"}), 403

    data = request.json
    entries = data.get("entries", [])

    try:
        base_content = """127.0.0.1   localhost
::1         localhost ip6-localhost ip6-loopback
ff02::1     ip6-allnodes
ff02::2     ip6-allrouters
"""

        custom_content = ""
        for e in entries:
            ip = e.get("ip")
            hostname = e.get("hostname")
            if ip and hostname:
                custom_content += f"{ip}    {hostname}\n"

        with open("/etc/hosts", "w") as f:
            f.write(base_content + custom_content)

        logging.info(f"/etc/hosts updated with: {entries}")
        return jsonify({"status": "ok", "entries": entries})
    except Exception as e:
        logging.error(f"Error updating /etc/hosts: {e}")
        return jsonify({"error": str(e)}), 500



@app.route("/restart-service", methods=["POST"])
def restart_service():
    if not check_token(request):
        return jsonify({"error": "Unauthorized"}), 403

    service = request.json.get("service")
    if not service:
        return jsonify({"error": "Missing 'service' parameter"}), 400

    try:
        subprocess.run(["service", service, "restart"], check=True)  # service plutôt que systemctl
        return jsonify({"status": "ok", "service": service})
    except Exception as e:
        return jsonify({"error": str(e)}), 500      
      
if __name__ == "__main__":
    logging.info("Starting Network API service...")
    app.run(host="0.0.0.0", port=55001)