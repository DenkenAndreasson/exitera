<?php
require_once __DIR__ . '/inc/db.php';

$page_name = "OT Forum";

$db = get_db();

$stmt = $db->prepare("SELECT name, description FROM groups WHERE type = ? ORDER BY id");
$stmt->execute(['community']);
$communities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_name) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($page_name) ?></h1>

    <h2>Communities</h2>

    <?php if (empty($communities)): ?>
        <p>Inga communities hittades.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($communities as $community): ?>
                <li>
                    <strong><?= htmlspecialchars($community['name']) ?></strong><br>
                    <?= htmlspecialchars($community['description']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
