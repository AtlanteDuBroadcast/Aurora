#!/usr/bin/env bash
set -euo pipefail

SEARCH_DIRS=(/run/media /media /mnt)
MARK_SUFFIX=".processed"

MATRIX=(
  # Le séparateur est |
  #"fichier.txt|commande1|commande2 ....."
  "default-ip.txt|sudo rm -f /etc/netplan/*.yaml|sudo netplan apply"
  "delete-user.txt|bash/opt/docker/Decoder/scripts/reset_login.sh"
)

log(){ printf '%s %s\n' "$(date -Iseconds)" "$*"; }

while true; do
  for base in "${SEARCH_DIRS[@]}"; do
    [[ -d $base ]] || continue

    for entry in "${MATRIX[@]}"; do
      IFS='|' read -r filename cmds <<< "$entry"
      IFS='|' read -ra CMD_ARRAY <<< "$entry"
      unset CMD_ARRAY[0]

      while IFS= read -r -d '' f; do
        fcanon="$(readlink -f -- "$f" || printf '%s' "$f")"
        lock="${fcanon}${MARK_SUFFIX}"
        [[ -e "$lock" ]] && continue

        log "Fichier détecté : $fcanon — exécution des commandes..."
        for cmd in "${CMD_ARRAY[@]}"; do
          log "-> Exécution : $cmd"
          if bash -c "$cmd"; then
            log "OK : $cmd"
          else
            log "ERREUR sur : $cmd"
          fi
        done

        : > "$lock"
        log "Traitement terminé pour $fcanon"
      done < <(find "$base" -maxdepth 3 -type f -name "$filename" -print0 2>/dev/null)
    done
  done

  # pause avant le prochain scan (en secondes)
  sleep 10
done