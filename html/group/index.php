<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/box.php';
require_once __DIR__ . '/../inc/page.php';
require_once __DIR__ . '/../inc/topics.php';

$current_user = current_user();
$group_id = (int) ($_GET['id'] ?? 0);

$db = get_db();
$stmt = $db->prepare(
    "SELECT g.id, g.name, g.description, g.type, COUNT(m.id) AS member_count
     FROM groups g
     LEFT JOIN group_members m ON m.group_id = g.id
     WHERE g.id = ?
     GROUP BY g.id, g.name, g.description, g.type"
);
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    show_message(404, 'Hittades inte', 'Den gruppen finns inte.');
}

if (!can_read($group, $current_user)) {
    show_message(403, 'Ingen åtkomst', $current_user === null
        ? 'Du måste vara inloggad för att se den här guilden.'
        : 'Du har inte tillgång till den här guilden.');
}

$membership = $current_user !== null
    ? membership_in($group['id'], $current_user['id'])
    : null;

$topics   = topic_list((int) $group['id']);
$may_post = can_post($group, $current_user);

$page_name = $group['name'];

require __DIR__ . '/../inc/header.php';
?>

<?php page_title($group['name']); ?>

<?php box_start($group['type'] === 'guild' ? 'Guild' : 'Community'); ?>
    <p><?= htmlspecialchars($group['description']) ?></p>
    <p class="muted">
        <?= (int) $group['member_count'] ?>
        <?= $group['member_count'] == 1 ? 'medlem' : 'medlemmar' ?>
        <?php if ($membership !== null): ?>
            · Din roll:
            <strong><?= htmlspecialchars(role_name($membership['role'])) ?></strong>
        <?php elseif ($current_user !== null && $current_user['is_admin']): ?>
            · Du ser den här sidan som <strong>admin</strong>
        <?php endif; ?>
    </p>
<?php box_end(); ?>

<?php box_start('Trådar'); ?>

    <div class="topic-actions">
        <?php if ($may_post): ?>
            <a class="btn" href="/topic/create/?group=<?= (int) $group['id'] ?>">Nytt ämne</a>
        <?php else: ?>
            <button type="button" disabled>Nytt ämne</button>
            <span class="muted">
                Du måste vara <a href="/login/">inloggad</a> för att skapa en tråd.
            </span>
        <?php endif; ?>
    </div>

    <?php if (empty($topics)): ?>
        <p class="muted">Inga trådar än.</p>
    <?php else: ?>
        <ul class="group-list">
            <?php foreach ($topics as $topic): ?>
                <li class="guild-row">
                    <div>
                        <a class="group-name" href="/topic/?id=<?= (int) $topic['id'] ?>">
                            <?= htmlspecialchars($topic['title']) ?>
                        </a><br>
                        <span class="group-desc">
                            av <?= htmlspecialchars(author_name($topic)) ?>
                            · <?= (int) $topic['post_count'] ?> inlägg
                        </span>
                    </div>
                    <span class="member-count"><?= htmlspecialchars(format_time($topic['last_activity'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

<?php box_end(); ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
