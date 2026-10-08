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


// manage_db.php
$host = "mariadb";
$db   = "mydatabase";
$user = "root";
$pass = "root";
$charset = "utf8mb4";

$message = "";

try {
    // Connexion sans sélectionner de base
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'delete') {
                $pdo->exec("DROP DATABASE IF EXISTS `$db`");
                $message = "Base '$db' supprimée avec succès !";
            } elseif ($_POST['action'] === 'recreate') {
                // Recréer la base
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                $pdo->exec("USE `$db`");

                // Recréer la table vide
                $createTableSQL = "
                CREATE TABLE config (
            interface VARCHAR(20) PRIMARY KEY,
    dhcp TINYINT,
    ip VARCHAR(50),
    prefix VARCHAR(5),
    gateway VARCHAR(50),
    dns VARCHAR(255),
    hostname VARCHAR(100),
    ntp VARCHAR(255)
                )";
                $pdo->exec($createTableSQL);
                $message = "Base '$db' et table 'config' recréées avec succès !";
            }
        }
    }

} catch (\PDOException $e) {
    die("Erreur DB : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion de la base de données</title>
</head>
<body>
<h2>Gestion de la base et de la table "config"</h2>
<p><?= $message ?></p>

<form method="post" style="margin-bottom: 20px;">
    <button name="action" value="delete" style="padding:10px; background:red; color:white;">Supprimer la base</button>
</form>

<form method="post">
    <button name="action" value="recreate" style="padding:10px; background:green; color:white;">Recréer la base et la table vide</button>
</form>
</body>
</html>