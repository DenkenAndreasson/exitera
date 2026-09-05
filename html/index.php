<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/box.php';

$page_name = 'Start';

$db = get_db();

$stmt = $db->prepare("SELECT name, description FROM groups WHERE type = ? ORDER BY id");
$stmt->execute(['community']);
$communities = $stmt->fetchAll();

$stmt = $db->prepare(
    "SELECT g.id, g.name, g.description, COUNT(m.id) AS member_count
     FROM groups g
     LEFT JOIN group_members m ON m.group_id = g.id
     WHERE g.type = ?
     GROUP BY g.id, g.name, g.description
     ORDER BY g.id"
);
$stmt->execute(['guild']);
$guilds = $stmt->fetchAll();

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

        <?php box_start('Guilds'); ?>
            <ul class="group-list">
                <?php foreach ($guilds as $guild): ?>
                    <li class="guild-row">
                        <div>
                            <span class="group-name"><?= htmlspecialchars($guild['name']) ?></span>
                            <span class="member-count"><?= (int) $guild['member_count'] ?> <?= $guild['member_count'] == 1 ? 'medlem' : 'medlemmar' ?></span><br>
                            <span class="group-desc"><?= htmlspecialchars($guild['description']) ?></span>
                        </div>
                        <button type="button" disabled>Ansök</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php box_end(); ?>

    </div>

    <aside class="col-side">

        <?php box_start('Logga in'); ?>
            <form method="post" action="/login/">
                <label for="email">E-post</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Lösenord</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Logga in</button>
            </form>
            <p class="muted">Har du inget konto? <a href="/register/">Skapa ett här</a>.</p>
        <?php box_end(); ?>

        <?php box_start('Min guild'); ?>
            <p>Currently no Guild, join one now!</p>
            <p class="muted">or <a href="/group/create/">Create your own here!</a></p>
        <?php box_end(); ?>

    </aside>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
