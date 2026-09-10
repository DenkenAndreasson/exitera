<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/box.php';
require_once __DIR__ . '/../inc/page.php';
require_once __DIR__ . '/../inc/guilds.php';

$page_name = 'Alla grupper';

$current_user = current_user();
$communities  = community_list();
$guilds       = guild_list();

$my_guild    = $current_user !== null ? my_guild($current_user['id']) : null;
$pending_ids = $current_user !== null ? pending_group_ids($current_user['id']) : [];

require __DIR__ . '/../inc/header.php';
?>

<?php page_title('Alla grupper'); ?>

<?php box_start('Communities'); ?>
    <ul class="group-list">
        <?php foreach ($communities as $community): ?>
            <li>
                <a class="group-name" href="/group/?id=<?= (int) $community['id'] ?>">
                    <?= htmlspecialchars($community['name']) ?>
                </a><br>
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
                <?php guild_button(
                    (int) $guild['id'],
                    guild_button_state((int) $guild['id'], $current_user, $my_guild, $pending_ids),
                    '/groups/'
                ); ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php box_end(); ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
