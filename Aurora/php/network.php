<?php

// --- Uptime système ---
$uptimeSeconds = (int) floatval(file_get_contents('/proc/uptime'));

$days = floor($uptimeSeconds / 86400);
$hours = floor(($uptimeSeconds % 86400) / 3600);
$minutes = floor(($uptimeSeconds % 3600) / 60);

$uptime = "";

if ($days > 0) {
    $uptime .= $days . " jour" . ($days > 1 ? "s " : " ");
}

if ($hours > 0) {
    $uptime .= $hours . " h ";
}

$uptime .= $minutes . " min";

// config.php
$host = "mariadb";
$db   = "Aurora_MyDataBase";
$user = "Aurora";
$pass = "Aurora_password";
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Création de la table si elle n'existe pas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS config (
            interface VARCHAR(20) PRIMARY KEY,
            dhcp TINYINT,
            ip VARCHAR(50),
            prefix VARCHAR(5),
            gateway VARCHAR(50),
            dns VARCHAR(255),
            hostname VARCHAR(100),
            ntp VARCHAR(255)
        )
    ");

    // Valeurs globales par défaut (hostname + NTP)
    $stmt = $pdo->prepare("
        INSERT INTO config (interface, hostname, ntp)
        VALUES ('global', :hostname, :ntp)
        ON DUPLICATE KEY UPDATE hostname=hostname, ntp=ntp
    ");
    $stmt->execute([
        ':hostname' => 'MARIE-DECODER',
        ':ntp'      => '0.fr.pool.ntp.org'
    ]);

} catch (\PDOException $e) {
    die("Erreur DB : " . $e->getMessage());
}

// API settings
$apiUrl = "http://host.docker.internal:55001";
$token  = "supersecrettoken123";

function callAPI($endpoint, $data = []) {
    global $apiUrl, $token;
    $options = [
        "http" => [
            "header"  => "Content-Type: application/json\r\nAuthorization: Bearer $token\r\n",
            "method"  => "POST",
            "content" => json_encode($data),
        ],
    ];
    $context  = stream_context_create($options);
    $result = @file_get_contents("$apiUrl/$endpoint", false, $context);
    if ($result === false) {
        return ["error" => "Impossible de joindre l'API"];
    }
    return json_decode($result, true);
}

// Liste interfaces physiques
$all_ifaces = array_diff(scandir('/sys/class/net'), ['.', '..']);
$all_ifaces = array_filter($all_ifaces, function($iface) {
    return is_dir("/sys/class/net/$iface/device") && !preg_match('/^(wlan|wl[p,s,x])/i', $iface);
});
$all_ifaces = array_values($all_ifaces);

// Interface sélectionnée via GET
$selected_iface = isset($_GET['interface']) && in_array($_GET['interface'], $all_ifaces)
    ? $_GET['interface']
    : ($all_ifaces[0] ?? null);

if (!$selected_iface) die("Aucune interface réseau détectée.");

$message = $_GET['msg'] ?? "";

// Sauvegarde configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    $interface = $_POST['interface'] ?? $selected_iface;
    $dhcp      = isset($_POST['dhcp']) ? 1 : 0;
    $ip        = $_POST['ip'] ?? '';
    $prefix    = $_POST['prefix'] ?? '';
    $gateway   = $_POST['gateway'] ?? '';
    $dns       = $_POST['dns'] ?? '';
    $hostname  = $_POST['hostname'] ?? '';
    $ntp       = $_POST['ntp'] ?? '';

    // Sauvegarde interface
    $stmt = $pdo->prepare("REPLACE INTO config 
        (interface, dhcp, ip, prefix, gateway, dns) 
        VALUES (:interface, :dhcp, :ip, :prefix, :gateway, :dns)");
    $stmt->execute([
        ':interface' => $interface,
        ':dhcp'      => $dhcp,
        ':ip'        => $ip,
        ':prefix'    => $prefix,
        ':gateway'   => $gateway,
        ':dns'       => $dns
    ]);

    // Sauvegarde hostname et NTP global
    $stmt = $pdo->prepare("REPLACE INTO config 
        (interface, hostname, ntp) 
        VALUES ('global', :hostname, :ntp)");
    $stmt->execute([
        ':hostname' => $hostname,
        ':ntp' => $ntp
    ]);

    // Appels API
    callAPI("set-network", [
        "interface" => $interface,
        "dhcp"      => (bool)$dhcp,
        "ip"        => $ip,
        "prefix"    => $prefix,
        "gateway"   => $gateway,
        "dns"       => explode(',', $dns)
    ]);
    if ($hostname) callAPI("set-hostname", ["hostname" => $hostname]);
    if (!empty($dns)) callAPI("set-dns", ["interface" => $interface, "dns" => explode(',', $dns)]);
    if (!empty($ntp)) callAPI("set-ntp", ["servers" => explode(',', $ntp)]);

    header("Location: ?interface=" . urlencode($interface) . "&msg=" . urlencode("Configuration sauvegardée et appliquée !"));
    exit;
}

// Actions reboot/shutdown
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = callAPI($action);
    $msg = $response['status'] ?? ($response['error'] ?? 'Erreur inconnue');
    header("Location: ?interface=" . urlencode($selected_iface) . "&msg=" . urlencode($msg));
    exit;
}

// Charger config interface
$stmt = $pdo->prepare("SELECT * FROM config WHERE interface = :iface");
$stmt->execute([':iface' => $selected_iface]);
$config = $stmt->fetch() ?: ['dhcp'=>1,'ip'=>'','prefix'=>'','gateway'=>'','dns'=>''];

// Charger config global
$stmt2 = $pdo->prepare("SELECT * FROM config WHERE interface = 'global'");
$stmt2->execute();
$global = $stmt2->fetch();

// Ajouter hostname et ntp globaux
$config['hostname'] = $global['hostname'] ?? '';
$config['ntp'] = $global['ntp'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Configuration Système</title>
<style>
body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif; background:#121212; color:#e0e0e0; padding:20px;}
.container {
    max-width: 800px;
    margin: auto;
    background: #1e1e1e;
    padding: 30px;
    box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.5);
    border-radius: 20px;
    position: relative;
}
h2 { text-align:center;}
.message { color:#4caf50; font-weight:bold; text-align:center; margin-bottom:20px;}
.form-group { margin-bottom:20px;}
label { display:block; margin-bottom:5px; font-weight:bold;}
input[type="text"], input[type="number"], select { width:100%; padding:10px; border-radius:10px; border:none; background:#333; color:#e0e0e0;}
button { width:100%; padding:12px; border-radius:10px; border:none; background:#444; color:#e0e0e0; cursor:pointer; margin-top:10px; font-weight:bold;}
button:hover { background:#555;}
</style>
</head>
<body>
<div class="container">
<h2>Configuration Réseau & Système</h2>
<?php if ($message): ?><div class="message"><?= htmlspecialchars($message ?? '') ?></div><?php endif; ?>

<form method="GET">
    <label>Interface :</label>
    <select name="interface" onchange="this.form.submit()">
        <?php foreach($all_ifaces as $iface): ?>
            <option value="<?= htmlspecialchars($iface ?? '') ?>" <?= $iface === $selected_iface ? 'selected' : '' ?>><?= htmlspecialchars($iface ?? '') ?></option>
        <?php endforeach; ?>
    </select>
</form>

<form method="POST">
    <input type="hidden" name="save_config" value="1">
    <input type="hidden" name="interface" value="<?= htmlspecialchars($selected_iface ?? '') ?>">
    <div class="form-group"><label><input type="checkbox" name="dhcp" <?= $config['dhcp'] ? 'checked' : '' ?>> DHCP</label></div>
    <div class="form-group"><label>IP</label><input type="text" name="ip" value="<?= htmlspecialchars($config['ip'] ?? '') ?>"></div>
    <div class="form-group"><label>Préfixe</label><input type="number" name="prefix" value="<?= htmlspecialchars($config['prefix'] ?? '') ?>"></div>
    <div class="form-group"><label>Gateway</label><input type="text" name="gateway" value="<?= htmlspecialchars($config['gateway'] ?? '') ?>"></div>
    <div class="form-group"><label>DNS (séparés par des virgules)</label><input type="text" name="dns" value="<?= htmlspecialchars($config['dns'] ?? '') ?>"></div>
    <div class="form-group"><label>Hostname</label><input type="text" name="hostname" value="<?= htmlspecialchars($config['hostname'] ?? '') ?>"></div>
    <div class="form-group"><label>NTP (séparés par des virgules)</label><input type="text" name="ntp" value="<?= htmlspecialchars($config['ntp'] ?? '') ?>"></div>
    <button type="submit">Sauvegarder</button>
</form>

<form method="POST" style="margin-top:20px;">
    <button type="submit" name="action" value="reboot">Redémarrer</button>
    <button type="submit" name="action" value="forcereboot">Forcer Redémarrer</button>
    <button type="submit" name="action" value="shutdown">Éteindre</button>
</form>
</div>
</body>
</html>