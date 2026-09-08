<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/box.php';
require_once __DIR__ . '/inc/guilds.php';

$page_name = 'Start';

$current_user = current_user();
$communities  = community_list();
$guilds       = guild_list();

$my_guild    = $current_user !== null ? my_guild($current_user['id']) : null;
$pending_ids = $current_user !== null ? pending_group_ids($current_user['id']) : [];
$my_level    = $current_user !== null ? role_level($current_user, $my_guild) : 0;

$waiting = ($my_guild !== null && $my_level >= 3) ? pending_count((int) $my_guild['id']) : 0;

require __DIR__ . '/inc/header.php';
?>

<div class="layout">
    <div class="col-main">

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
                            '/'
                        ); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php box_end(); ?>

    </div>

    <aside class="col-side">

        <?php if ($current_user === null): ?>
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
        <?php else: ?>
            <?php box_start('Inloggad'); ?>
                <p>Inloggad som <strong><?= htmlspecialchars($current_user['character_name'] ?? $current_user['first_name']) ?></strong>.</p>
                <form method="post" action="/logout/">
                    <button type="submit">Logga ut</button>
                </form>
            <?php box_end(); ?>
        <?php endif; ?>

        <?php if ($current_user !== null && $current_user['is_admin']): ?>

            <?php box_start('Sajt-admin'); ?>
                <p>Du är inloggad som sajt-admin och kan läsa alla guilds.</p>
                <p class="muted">Admin går inte med i guilds.</p>
            <?php box_end(); ?>

        <?php elseif ($my_guild !== null): ?>

            <?php box_start('Min guild'); ?>
                <p>
                    <a class="group-name" href="/group/?id=<?= (int) $my_guild['id'] ?>">
                        <?= htmlspecialchars($my_guild['name']) ?>
                    </a><br>
                    <span class="muted"><?= htmlspecialchars(role_name($my_guild['role'])) ?></span>
                </p>
                <?php if ($my_level >= 3): ?>
                    <p>
                        <a href="/group/manage/?id=<?= (int) $my_guild['id'] ?>">Manage Guild</a>
                        <?php if ($waiting > 0): ?>
                            <span class="badge"><?= $waiting ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            <?php box_end(); ?>

        <?php elseif ($current_user !== null): ?>

            <?php box_start('Min guild'); ?>
                <p>Currently no Guild, join one now!</p>
                <p class="muted">or <a href="/group/create/">Create your own here!</a></p>
            <?php box_end(); ?>

        <?php endif; ?>

    </aside>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
