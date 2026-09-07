<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/box.php';

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

$error = null;
$membership = null;

if (!$group) {
    http_response_code(404);
    $error = 'Den gruppen finns inte.';
} elseif (!can_read($group, $current_user)) {
    http_response_code(403);
    $error = $current_user === null
        ? 'Du måste vara inloggad för att se den här guilden.'
        : 'Du har inte tillgång till den här guilden.';
} elseif ($current_user !== null) {
    $membership = membership_in($group['id'], $current_user['id']);
}

$page_name = $error === null ? $group['name'] : 'Ingen åtkomst';

require __DIR__ . '/../inc/header.php';
?>

<?php if ($error !== null): ?>

    <?php box_start($page_name); ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
        <p class="muted"><a href="/groups/">Tillbaka till alla grupper</a></p>
    <?php box_end(); ?>

<?php else: ?>

    <?php box_start($group['name']); ?>
        <p><?= htmlspecialchars($group['description']) ?></p>
        <p class="muted">
            <?= $group['type'] === 'guild' ? 'Guild' : 'Community' ?>
            · <?= (int) $group['member_count'] ?>
            <?= $group['member_count'] == 1 ? 'medlem' : 'medlemmar' ?>
            <?php if ($membership !== null): ?>
                · Din roll:
                <strong><?= htmlspecialchars(role_name($membership['role'])) ?></strong>
            <?php elseif ($current_user !== null && $current_user['is_admin']): ?>
                · Du ser den här sidan som <strong>admin</strong>
            <?php endif; ?>
        </p>
    <?php box_end(); ?>

<?php endif; ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
