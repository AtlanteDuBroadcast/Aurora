<?php
ob_start(); // tampon de sortie pour pouvoir utiliser header() plus tard
// ============================================================================
// INFORMATIONS SYSTÈME
// ============================================================================

// --- Hostname ---
$hostname = gethostname();

// --- OS ---
$os = "Inconnu";
if (file_exists('/etc/os-release')) {
    $osInfo = parse_ini_file('/etc/os-release');
    $os = $osInfo['PRETTY_NAME'] ?? 'Inconnu';
}

// --- Kernel ---
$kernel = php_uname('r');

// --- Architecture ---
$architecture = php_uname('m');

// --- Date / heure ---
$serverDate = date('d/m/Y H:i:s');

// --- Timezone ---
$timezone = date_default_timezone_get();


// ============================================================================
// UPTIME
// ============================================================================

$uptimeSeconds = 0;

if (file_exists('/proc/uptime')) {
    $uptimeSeconds = (int) floatval(file_get_contents('/proc/uptime'));
}

$uptimeDays = floor($uptimeSeconds / 86400);
$uptimeHours = floor(($uptimeSeconds % 86400) / 3600);
$uptimeMinutes = floor(($uptimeSeconds % 3600) / 60);

$uptime = "";

if ($uptimeDays > 0) {
    $uptime .= $uptimeDays . " jour" . ($uptimeDays > 1 ? "s " : " ");
}

$uptime .= sprintf("%02d h %02d min", $uptimeHours, $uptimeMinutes);


// ============================================================================
// CPU
// ============================================================================

$cpuCores = (int) trim(shell_exec('nproc'));

if ($cpuCores <= 0) {
    $cpuCores = 1;
}

$load = sys_getloadavg();

$load1 = round($load[0], 2);
$load5 = round($load[1], 2);
$load15 = round($load[2], 2);

// Estimation de la charge CPU
$cpuUsage = round(($load1 / $cpuCores) * 100);
$cpuUsage = min(100, $cpuUsage);


// ============================================================================
// RAM
// ============================================================================

$memTotal = 0;
$memAvailable = 0;

if (file_exists('/proc/meminfo')) {

    $memInfo = file('/proc/meminfo');

    foreach ($memInfo as $line) {

        if (strpos($line, 'MemTotal:') === 0) {
            $memTotal = (int) filter_var(
                $line,
                FILTER_SANITIZE_NUMBER_INT
            );
        }

        if (strpos($line, 'MemAvailable:') === 0) {
            $memAvailable = (int) filter_var(
                $line,
                FILTER_SANITIZE_NUMBER_INT
            );
        }
    }
}

$memUsed = $memTotal - $memAvailable;

$ramPercent = $memTotal > 0
    ? round(($memUsed / $memTotal) * 100)
    : 0;

$ramUsedGB = round($memUsed / 1024 / 1024, 1);
$ramTotalGB = round($memTotal / 1024 / 1024, 1);


// ============================================================================
// SWAP
// ============================================================================

$swapTotal = 0;
$swapFree = 0;

if (file_exists('/proc/meminfo')) {

    foreach ($memInfo as $line) {

        if (strpos($line, 'SwapTotal:') === 0) {
            $swapTotal = (int) filter_var(
                $line,
                FILTER_SANITIZE_NUMBER_INT
            );
        }

        if (strpos($line, 'SwapFree:') === 0) {
            $swapFree = (int) filter_var(
                $line,
                FILTER_SANITIZE_NUMBER_INT
            );
        }
    }
}

$swapUsed = $swapTotal - $swapFree;

$swapPercent = $swapTotal > 0
    ? round(($swapUsed / $swapTotal) * 100)
    : 0;

$swapUsedGB = round($swapUsed / 1024 / 1024, 1);
$swapTotalGB = round($swapTotal / 1024 / 1024, 1);


// ============================================================================
// DISQUE
// ============================================================================

$diskTotal = disk_total_space('/');
$diskFree = disk_free_space('/');
$diskUsed = $diskTotal - $diskFree;

$diskPercent = $diskTotal > 0
    ? round(($diskUsed / $diskTotal) * 100)
    : 0;

$diskUsedGB = round($diskUsed / 1024 / 1024 / 1024, 1);
$diskTotalGB = round($diskTotal / 1024 / 1024 / 1024, 1);







// ============================================================================
// TEMPÉRATURE CPU
// ============================================================================

$cpuTemperature = null;

$tempFiles = glob('/sys/class/thermal/thermal_zone*/temp');

foreach ($tempFiles as $tempFile) {

    $value = @file_get_contents($tempFile);

    if ($value !== false && is_numeric(trim($value))) {

        $temp = (float) trim($value) / 1000;

        if ($temp > 0 && $temp < 150) {
            $cpuTemperature = round($temp, 1);
            break;
        }
    }
}

// --- Nettoyage des logs ---
$logDir = '/var/www/html/scripts/log';
$days = 7; // supprimer les fichiers plus vieux que X jours
$message = "";

if (isset($_POST['clean_logs'])) {
    $cmd = sprintf('find %s -name "*.log" -type f -mtime +%d -delete', escapeshellarg($logDir), $days);
    exec($cmd, $output, $return_var);
    $message = $return_var === 0
        ? "Les fichiers .log plus vieux que $days jours ont été supprimés."
        : "Erreur lors de la suppression des fichiers .log.";
}

// --- Gestion de la page à inclure ---
$page = $_GET['page'] ?? null;
$pageFile = null;


switch ($page) {
	
    case 'reboot':
        $pageFile = 'reboot.php';
        break;
		
    case 'update':
        $pageFile = 'update.php';
        break;

    case 'network':
        $pageFile = 'network.php';
        break;

    case 'hosts':
        $pageFile = 'hosts.php';
        break;

    case 'changelog':
        $pageFile = 'changelog.php';
        break;

    case 'log':
        $pageFile = 'log.php';
        break;


}

// --- Inclusion sécurisée ---
if ($pageFile && file_exists($pageFile)) {
    ob_start(); // tamponner la page incluse pour ne rien envoyer immédiatement
    include $pageFile;
    $pageContent = ob_get_clean();
} else {
    $pageContent = "<p>Choisissez une option ci-dessus pour afficher son contenu.</p>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portail d'accès</title>
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
    position: relative;
}
.portal-links {
    display: flex;
    justify-content: space-evenly;
    flex-wrap: wrap;
    margin-top: 30px;
}
.portal-link {
    background: #333;
    padding: 20px;
    margin: 10px;
    border-radius: 10px;
    width: 200px;
    text-align: center;
    box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.5);
}
.portal-link a {
    color: #e0e0e0;
    text-decoration: none;
    font-size: 18px;
    font-weight: bold;
}
.portal-link:hover { background: #444; }
footer {
    max-width: 800px;
    margin: auto;
    position: relative;
    background: #1e1e1e;
    color: #e0e0e0;
    padding: 30px;
    margin-top: 30px;
    text-align: center;
    border-radius: 20px;
    box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.5);
}
.message { color:#4caf50; font-weight:bold; text-align:center; margin-bottom:20px;}

/* ============================================================
   SYSTEM DASHBOARD
   ============================================================ */

.system-dashboard {
    text-align: left;
}

.system-dashboard > h1,
.system-dashboard > h4 {
    text-align: center;
}

.system-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 25px;
}

.system-card {
    background: #333;
    padding: 18px;
    border-radius: 12px;
    text-align: center;
}

.system-card span {
    display: block;
    color: #aaa;
    font-size: 14px;
    margin-bottom: 8px;
}

.system-card strong {
    display: block;
    font-size: 24px;
}

.system-card small {
    display: block;
    color: #aaa;
    margin-top: 5px;
}

.system-section {
    background: #252525;
    padding: 20px;
    margin-top: 20px;
    border-radius: 12px;
}

.system-section h3 {
    margin-top: 0;
    text-align: center;
}

.info-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 5px;
    border-bottom: 1px solid #3a3a3a;
}

.info-line:last-child {
    border-bottom: none;
}

.status-ok {
    color: #4caf50;
}

.status-error {
    color: #f44336;
}

.network-interface {
    background: #333;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
}

.network-interface > div:first-child {
    display: flex;
    justify-content: space-between;
}

.network-details {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    color: #aaa;
}

.docker-grid {
    margin-top: 0;
}

</style>
</head>
<body>

<div class="container system-dashboard">

    <h1>Bienvenue sur le portail d'Aurora</h1>
    <h4>Gestion du système</h4>

    <!-- Ressources -->
    <div class="system-grid">

        <div class="system-card">
            <span>⏱️ Uptime</span>
            <strong><?= htmlspecialchars($uptime) ?></strong>
        </div>

        <div class="system-card">
            <span>⚙️ CPU</span>
            <strong><?= $cpuUsage ?> %</strong>
            <small><?= $cpuCores ?> cœurs</small>
        </div>

        <div class="system-card">
            <span>🧠 RAM</span>
            <strong><?= $ramPercent ?> %</strong>
            <small><?= $ramUsedGB ?> / <?= $ramTotalGB ?> Go</small>
        </div>

        <div class="system-card">
            <span>💿 Disque /</span>
            <strong><?= $diskPercent ?> %</strong>
            <small><?= $diskUsedGB ?> / <?= $diskTotalGB ?> Go</small>
        </div>

        <div class="system-card">
            <span>💾 Swap</span>
            <strong><?= $swapPercent ?> %</strong>
            <small><?= $swapUsedGB ?> / <?= $swapTotalGB ?> Go</small>
        </div>

        <?php if ($cpuTemperature !== null): ?>
        <div class="system-card">
            <span>🌡️ CPU</span>
            <strong><?= $cpuTemperature ?> °C</strong>
        </div>
        <?php endif; ?>

    </div>


    <!-- Load Average -->
    <div class="system-section">

        <h3>📊 Charge système</h3>

        <div class="info-line">
            <span>Load average 1 min</span>
            <strong><?= $load1 ?></strong>
        </div>

        <div class="info-line">
            <span>Load average 5 min</span>
            <strong><?= $load5 ?></strong>
        </div>

        <div class="info-line">
            <span>Load average 15 min</span>
            <strong><?= $load15 ?></strong>
        </div>

    </div>


    <!-- Système -->
    <div class="system-section">

        <h3>🖥️ Système</h3>
<!--
        <div class="info-line">
            <span>Hostname</span>
            <strong><?= htmlspecialchars($hostname) ?></strong>
        </div>-->

        <div class="info-line">
            <span>Système</span>
            <strong><?= htmlspecialchars($os) ?></strong>
        </div>

        <div class="info-line">
            <span>Kernel</span>
            <strong><?= htmlspecialchars($kernel) ?></strong>
        </div>

        <div class="info-line">
            <span>Architecture</span>
            <strong><?= htmlspecialchars($architecture) ?></strong>
        </div>

        <div class="info-line">
            <span>Date / Heure</span>
            <strong><?= htmlspecialchars($serverDate) ?></strong>
        </div>

        <div class="info-line">
            <span>Timezone</span>
            <strong><?= htmlspecialchars($timezone) ?></strong>
        </div>

    </div>


   


   

</div>
<br>

<div class="container">
    <h2>Administration système</h2>
    <div class="portal-links">
        <div class="portal-link"><a href="?page=reboot">Reboot & Shutdown</a></div>
        <div class="portal-link"><a href="?page=update">Update & Upgrade</a></div>
        <div class="portal-link"><a href="?page=network">Configuration réseau</a></div>
        <div class="portal-link"><a href="?page=hosts">Fichier hosts</a></div>
        <div class="portal-link"><a href="?page=changelog">Changelog</a></div>
        <div class="portal-link"><a href="?page=log">Log système</a></div>
    </div>
</div>
<br>

<?php if ($message): ?>
<div class="container message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="container">
    <?= $pageContent ?>
</div>

<footer>
    <p>&copy; Saillant Alexi - 2025 Tous droits réservés.<br>
    Version alpha 2025-09-16</p>
</footer>

</body>
</html>
<?php
ob_end_flush(); // envoie tout le contenu tamponné
