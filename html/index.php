<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/box.php';

$page_name = "OT Forum";

$db = get_db();

$stmt = $db->prepare("SELECT name, description FROM groups WHERE type = ? ORDER BY id");
$stmt->execute(['community']);
$communities = $stmt->fetchAll();

require __DIR__ . '/inc/header.php';
?>

<div class="layout">
    <div class="col-main">

        <?php box_start('Communities'); ?>
            <ul class="group-list">
                <?php foreach ($communities as $community): ?>
                    <li>
                        <span class="group-name"><?= htmlspecialchars($community['name']) ?></span><br>
                        <span class="group-desc"><?= htmlspecialchars($community['description']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php box_end(); ?>

    </div>

    <aside class="col-side">
    </aside>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
