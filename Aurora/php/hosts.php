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

// config.php (inclus ici pour la DB + API)
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

    // Création table hosts si inexistante
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hosts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(50) NOT NULL,
            hostname VARCHAR(100) NOT NULL
        )
    ");

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

$message = $_GET['msg'] ?? "";

// Ajout ou modification d’une entrée
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_host'])) {
    $id       = $_POST['id'] ?? null;
    $ip       = $_POST['ip'] ?? '';
    $hostname = $_POST['hostname'] ?? '';

    if ($id) {
        $stmt = $pdo->prepare("UPDATE hosts SET ip=:ip, hostname=:hostname WHERE id=:id");
        $stmt->execute([':ip'=>$ip, ':hostname'=>$hostname, ':id'=>$id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO hosts (ip, hostname) VALUES (:ip, :hostname)");
        $stmt->execute([':ip'=>$ip, ':hostname'=>$hostname]);
    }

    // Réenvoi complet du fichier hosts à l’API
    $all = $pdo->query("SELECT ip, hostname FROM hosts")->fetchAll();
    callAPI("set-hosts", ["entries" => $all]);

    header("Location: ?msg=" . urlencode("Entrée sauvegardée et appliquée !"));
    exit;
}

// Suppression d’une entrée
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_host'])) {
    $id = $_POST['id'];
    $pdo->prepare("DELETE FROM hosts WHERE id=:id")->execute([':id'=>$id]);

    // Réenvoi complet à l’API
    $all = $pdo->query("SELECT ip, hostname FROM hosts")->fetchAll();
    callAPI("set-hosts", ["entries" => $all]);

    header("Location: ?msg=" . urlencode("Entrée supprimée et appliquée !"));
    exit;
}

// Charger toutes les entrées
$hosts = $pdo->query("SELECT * FROM hosts ORDER BY id ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Configuration Hosts</title>
<style>
body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif; background:#121212; color:#e0e0e0; padding:20px;}
.container { max-width:800px; margin:auto; background:#1e1e1e; padding:30px; border-radius:20px;}
h2 { text-align:center;}
.message { color:#4caf50; font-weight:bold; text-align:center; margin-bottom:20px;}
table { width:100%; border-collapse:collapse; margin-bottom:20px;}
th, td { padding:10px; border-bottom:1px solid #333; text-align:left;}
input[type="text"] { width:100%; padding:8px; border-radius:8px; border:none; background:#333; color:#e0e0e0;}
button { padding:8px 15px; border-radius:8px; border:none; background:#444; color:#e0e0e0; cursor:pointer;}
button:hover { background:#555;}
</style>
</head>
<body>
<div class="container">
<h2>Configuration du fichier HOSTS</h2>
<?php if ($message): ?><div class="message"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<table>
<tr><th>IP</th><th>Hostname</th><th>Actions</th></tr>
<?php foreach($hosts as $h): ?>
<tr>
    <td><?= htmlspecialchars($h['ip']) ?></td>
    <td><?= htmlspecialchars($h['hostname']) ?></td>
    <td>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="id" value="<?= $h['id'] ?>">
            <button type="submit" name="delete_host">Supprimer</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>

<h3>Nouvelle entrée / Modifier</h3>
<form method="POST">
    <input type="hidden" name="save_host" value="1">
    <div class="form-group">
        <label>IP</label>
        <input type="text" name="ip" required>
    </div>
    <div class="form-group">
        <label>Hostname</label>
        <input type="text" name="hostname" required>
    </div>
    <button type="submit">Sauvegarder</button>
</form>

</div>
</body>
</html>