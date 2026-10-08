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


// ============================================================
// API
// ============================================================

$apiUrl = "http://host.docker.internal:55001";
$token  = "supersecrettoken123";

function callAPI($endpoint, $data = [])
{
    global $apiUrl, $token;

    $options = [
        "http" => [
            "header" =>
                "Content-Type: application/json\r\n" .
                "Authorization: Bearer $token\r\n",
            "method"  => "POST",
            "content" => json_encode($data),
        ],
    ];

    $context = stream_context_create($options);

    $result = @file_get_contents(
        "$apiUrl/$endpoint",
        false,
        $context
    );

    if ($result === false) {
        return [
            "error" => "Impossible de joindre l'API"
        ];
    }

    return json_decode($result, true);
}


// ============================================================
// ACTIONS
// ============================================================

$message = $_GET['msg'] ?? '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
) {

    $action = $_POST['action'];

    $allowed_actions = [
        'update',
        'upgrade',
        'reboot',
        'forcereboot',
        'shutdown'
    ];

    if (in_array($action, $allowed_actions, true)) {

        $response = callAPI($action);

        $msg =
            $response['status']
            ?? ($response['error'] ?? 'Erreur inconnue');

        header(
            "Location: ?msg=" . urlencode($msg)
        );

        exit;
    }
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

<meta charset="UTF-8">

<title>Configuration Système</title>

<style>

body {
    font-family:
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;

    background: #121212;
    color: #e0e0e0;
    padding: 20px;
}

.container {
    max-width: 800px;
    margin: auto;
    background: #1e1e1e;
    padding: 30px;
    box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.5);
    border-radius: 20px;
    position: relative;
}

h2 {
    text-align: center;
}

.message {
    color: #4caf50;
    font-weight: bold;
    text-align: center;
    margin-bottom: 20px;
}

button {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: none;
    background: #444;
    color: #e0e0e0;
    cursor: pointer;
    margin-top: 10px;
    font-weight: bold;
}

button:hover {
    background: #555;
}

</style>

</head>

<body>

<div class="container">

<h2>System Control</h2>

<?php if ($message): ?>

<div class="message">
    <?= htmlspecialchars($message) ?>
</div>

<?php endif; ?>


<!-- ========================================================
     UPDATE / UPGRADE
     ======================================================== -->

<form method="POST">

    <button
        type="submit"
        name="action"
        value="update"
    >
        Update
    </button>

    <button
        type="submit"
        name="action"
        value="upgrade"
    >
        Upgrade
    </button>

</form>


<!-- ========================================================
     REBOOT / SHUTDOWN
     ======================================================== -->

<form method="POST" style="margin-top:20px;">

    <button
        type="submit"
        name="action"
        value="reboot"
    >
        Redémarrer
    </button>

    <button
        type="submit"
        name="action"
        value="forcereboot"
    >
        Forcer Redémarrer
    </button>

    <button
        type="submit"
        name="action"
        value="shutdown"
    >
        Éteindre
    </button>

</form>

</div>

</body>

</html>
