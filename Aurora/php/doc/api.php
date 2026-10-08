<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Documentation API Aurora</title>
<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        background-color: #121212;
        color: #e0e0e0;
        margin: 0;
        padding: 20px;
    }
    h1, h2, h3 {
        color: #4caf50;
    }
    h1 { text-align: center; }
    a { color: #81c784; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .container { max-width: 1000px; margin: auto; }
    .endpoint { background: #1e1e1e; padding: 20px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
    pre { background: #333; padding: 15px; border-radius: 10px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    th, td { border: 1px solid #444; padding: 10px; text-align: left; }
    th { background-color: #222; }
    code { color: #81c784; }
    .note { background: #222; padding: 10px; border-left: 4px solid #4caf50; margin: 10px 0; border-radius: 5px; }
</style>
</head>
<body>
<div class="container">
<h1>Aurora System Control API</h1>

<p><strong>Base URL :</strong> <code>http://&lt;host&gt;:55001/</code></p>
<p><strong>Authentification :</strong> Token via header HTTP <code>Authorization: Bearer supersecrettoken123</code></p>

<p>Tous les endpoints renvoient <code>403 Unauthorized</code> si le token est absent ou incorrect.</p>

<hr>

<div class="endpoint">
<h2>1. POST /set-network</h2>
<p>Configure une interface réseau en DHCP ou IP statique avec DNS.</p>

<h3>Headers</h3>
<pre>
Authorization: Bearer supersecrettoken123
Content-Type: application/json
</pre>

<h3>Body JSON</h3>
<table>
<tr><th>Paramètre</th><th>Type</th><th>Obligatoire</th><th>Description</th></tr>
<tr><td>interface</td><td>string</td><td>Non</td><td>Nom de l’interface réseau (ex: "eth0"). Défaut : "eth0".</td></tr>
<tr><td>dhcp</td><td>boolean</td><td>Non</td><td>Si true, configure en DHCP.</td></tr>
<tr><td>ip</td><td>string</td><td>Oui si dhcp=false</td><td>Adresse IP statique.</td></tr>
<tr><td>prefix</td><td>int</td><td>Oui si dhcp=false</td><td>Masque réseau (ex: 24).</td></tr>
<tr><td>gateway</td><td>string</td><td>Non</td><td>Passerelle par défaut.</td></tr>
<tr><td>dns</td><td>list</td><td>Non</td><td>Liste de serveurs DNS (ex: ["8.8.8.8","8.8.4.4"]).</td></tr>
</table>

<h3>Réponse JSON</h3>
<pre>{
  "status": "ok",
  "mode": "dhcp" | "static"
}</pre>

<h3>Exemple curl</h3>
<pre>
curl -X POST http://localhost:55001/set-network \
  -H "Authorization: Bearer supersecrettoken123" \
  -H "Content-Type: application/json" \
  -d '{"interface":"eth0","dhcp":false,"ip":"192.168.1.100","prefix":24,"gateway":"192.168.1.1","dns":["8.8.8.8","8.8.4.4"]}'
</pre>
</div>

<div class="endpoint">
<h2>2. POST /set-hostname</h2>
<p>Change le hostname du système.</p>

<h3>Body JSON</h3>
<pre>{
  "hostname": "nouveau-hostname"
}</pre>

<h3>Réponse JSON</h3>
<pre>{
  "status": "ok",
  "hostname": "nouveau-hostname"
}</pre>

<h3>Exemple curl</h3>
<pre>
curl -X POST http://localhost:55001/set-hostname \
  -H "Authorization: Bearer supersecrettoken123" \
  -H "Content-Type: application/json" \
  -d '{"hostname":"Aurora-Server"}'
</pre>
</div>

<div class="endpoint">
<h2>3. POST /set-dns</h2>
<p>Configure uniquement les serveurs DNS d’une interface.</p>

<h3>Body JSON</h3>
<pre>{
  "interface": "eth0",
  "dns": ["8.8.8.8", "8.8.4.4"]
}</pre>

<h3>Réponse JSON</h3>
<pre>{
  "status": "ok",
  "dns": ["8.8.8.8", "8.8.4.4"]
}</pre>

<h3>Exemple curl</h3>
<pre>
curl -X POST http://localhost:55001/set-dns \
  -H "Authorization: Bearer supersecrettoken123" \
  -H "Content-Type: application/json" \
  -d '{"interface":"eth0","dns":["8.8.8.8","8.8.4.4"]}'
</pre>
</div>

<div class="endpoint">
<h2>4. POST /set-ntp</h2>
<p>Configure les serveurs NTP et redémarre systemd-timesyncd.</p>

<h3>Body JSON</h3>
<pre>{
  "servers": ["0.pool.ntp.org","1.pool.ntp.org"]
}</pre>

<h3>Réponse JSON</h3>
<pre>{
  "status": "ok",
  "ntp": ["0.pool.ntp.org","1.pool.ntp.org"]
}</pre>

<h3>Exemple curl</h3>
<pre>
curl -X POST http://localhost:55001/set-ntp \
  -H "Authorization: Bearer supersecrettoken123" \
  -H "Content-Type: application/json" \
  -d '{"servers":["0.pool.ntp.org","1.pool.ntp.org"]}'
</pre>
</div>

<div class="endpoint">
<h2>5. POST /reboot</h2>
<p>Redémarre le système.</p>

<h3>Réponse JSON</h3>
<pre>{
  "status": "rebooting"
}</pre>

<h3>Exemple curl</h3>
<pre>
curl -X POST http://localhost:55001/reboot \
  -H "Authorization: Bearer supersecrettoken123"
</pre>
</div>

<div class="endpoint">
<h2>6. POST /shutdown</h2>
<p>Éteint le système.</p>

<h3>Réponse JSON</h3>
<pre>{
  "status": "shutting down"
}</pre>

<h3>Exemple curl</h3>
<pre>
curl -X POST http://localhost:55001/shutdown \
  -H "Authorization: Bearer supersecrettoken123"
</pre>
</div>

<div class="endpoint">
<h2>7. POST /set-hosts</h2>
<p>Remplace /etc/hosts avec des entrées personnalisées.</p>

<h3>Body JSON</h3>
<pre>{
  "entries": [
    {"ip": "192.168.1.10", "hostname": "server1"},
    {"ip": "192.168.1.11", "hostname": "server2"}
  ]
}</pre>

<h3>Réponse JSON</h3>
<pre>{
  "status": "ok",
  "entries": [
    {"ip": "192.168.1.10", "hostname": "server1"},
    {"ip": "192.168.1.11", "hostname": "server2"}
  ]
}</pre>

<h3>Exemple curl</h3>
<pre>
curl -X POST http://localhost:55001/set-hosts \
  -H "Authorization: Bearer supersecrettoken123" \
  -H "Content-Type: application/json" \
  -d '{"entries":[{"ip":"192.168.1.10","hostname":"server1"},{"ip":"192.168.1.11","hostname":"server2"}]}'
</pre>
</div>

<div class="note">
<p>💡 Tous les changements réseau et DNS via /set-network ou /set-dns utilisent <code>netplan apply</code> immédiatement. L’API doit être exécutée en <strong>root</strong> pour appliquer les changements système.</p>
<p>💡 Les logs sont disponibles dans <code>/opt/docker/Aurora/scripts/log/log-API_AURORA_YYYY-MM-DD.log</code>.</p>
</div>

</div>
</body>
</html>
