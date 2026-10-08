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

$logDir = '/var/www/html/scripts/log';
$files = glob($logDir . '/log-API_AURORA_*.log');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Logs Aurora</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #121212;
            color: #e0e0e0;
            text-align: center;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: #1e1e1e;
            padding: 30px;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.5);
            border-radius: 20px;
        }
        h1 {
            margin-bottom: 20px;
        }
        .log-list {
            list-style: none;
            padding: 0;
        }
        .log-list li {
            margin: 10px 0;
            background: #333;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0px 5px 15px rgba(0,0,0,0.3);
        }
        .log-list a {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: bold;
        }
        .log-list a:hover {
            color: #4cafef;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fichiers de Log Aurora</h1>
        <ul class="log-list">
            <?php if (!empty($files)): ?>
                <?php foreach ($files as $file): 
                    $filename = basename($file); ?>
                    <li>
                        <a href="scripts/log/<?= $filename ?>" download>
                            <?= $filename ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>Aucun fichier de log trouvé.</li>
            <?php endif; ?>
        </ul>
    </div>
</body>
</html>
