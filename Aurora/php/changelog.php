<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Changelog</title>
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
        .portal-link:hover {
            background: #444;
        }
        pre {
            text-align: left;
            white-space: pre-wrap; /* pour gérer les retours à la ligne */
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Changelog</h1>
        <pre>
<?php
// Lire le fichier changelog.txt
$changelogFile = 'changelog.txt';
if (file_exists($changelogFile)) {
    echo htmlspecialchars(file_get_contents($changelogFile));
} else {
    echo "Le fichier changelog.txt est introuvable.";
}
?>
        </pre>
    </div>
</body>
</html>
