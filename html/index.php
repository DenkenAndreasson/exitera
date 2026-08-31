<?php
$page_name = "OT Forum";
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_name ?></title>
</head>
<body>
    <h1><?= $page_name ?></h1>

    <p>Miljön är igång.</p>

    <ul>
        <li>PHP-version: <?= phpversion() ?></li>
        <li>pdo_mysql laddad: <?= extension_loaded('pdo_mysql') ? 'ja' : 'NEJ' ?></li>
        <li>DB_HOST: <?= getenv('DB_HOST') ?></li>
    </ul>
</body>
</html>
