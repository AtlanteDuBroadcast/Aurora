#!/bin/bash
chmod +x /opt/docker/Aurora/scripts/@Install/*.sh
chmod +x /opt/docker/Decoder/scripts/@Install/*.sh

# Liste des scripts à exécuter
scripts=(
	"/opt/docker/Aurora/scripts/@Install/1Prerequis.sh"
    #"/opt/docker/Aurora/scripts/@Install/2InstallDocker.sh"
    "/opt/docker/Aurora/scripts/@Install/3CreateService.sh"
    "/opt/docker/Aurora/scripts/@Install/4StartDocker.sh"

    
)

for script in "${scripts[@]}"; do
    if [[ -f "$script" ]]; then
        echo "Exécution de $script..."
        sudo bash "$script"
        # Vérification si le script a échoué (code de sortie différent de 0)
        if [[ $? -ne 0 ]]; then
            echo "Erreur avec $script, arrêt des scripts."
            exit 1  # Arrêt du script principal
        fi
    else
        echo "Le script $script n'existe pas."
        exit 1  # Arrêt du script principal si le fichier n'existe pas
    fi
done

echo "Tous les scripts ont été exécutés."