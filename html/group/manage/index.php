<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/box.php';
require_once __DIR__ . '/../../inc/page.php';
require_once __DIR__ . '/../../inc/csrf.php';

$current_user = current_user();

if ($current_user === null) {
    show_message(403, 'Ingen åtkomst', 'Du måste vara inloggad.');
}

$group_id = (int) ($_GET['id'] ?? 0);

$db = get_db();
$stmt = $db->prepare("SELECT id, name, type FROM groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group || $group['type'] !== 'guild') {
    show_message(404, 'Hittades inte', 'Den guilden finns inte.');
}

if (role_level($current_user, membership_in($group_id, $current_user['id'])) < 3) {
    show_message(403, 'Ingen åtkomst', 'Bara General och uppåt kan hantera ansökningar i den här guilden.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $application_id = (int) ($_POST['application_id'] ?? 0);
    $decision       = $_POST['decision'] ?? '';

    if (!in_array($decision, ['approve', 'reject'], true)) {
        show_message(400, 'Ogiltigt beslut', 'Beslutet måste vara godkänn eller avslå.');
    }

    $stmt = $db->prepare("SELECT id, group_id, user_id, status FROM applications WHERE id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch();

    if (!$application || (int) $application['group_id'] !== $group_id) {
        show_message(404, 'Hittades inte', 'Den ansökan finns inte i den här guilden.');
    }

    if ($application['status'] !== 'pending') {
        show_message(409, 'Redan hanterad', 'Ansökan är redan hanterad.');
    }

    if ($decision === 'reject') {
        $stmt = $db->prepare(
            "UPDATE applications
             SET status = 'rejected', handled_by = ?, handled_at = NOW()
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->execute([$current_user['id'], $application_id]);
    } else {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "SELECT m.id
                 FROM group_members m
                 JOIN groups g ON g.id = m.group_id
                 WHERE m.user_id = ? AND g.type = 'guild'
                 FOR UPDATE"
            );
            $stmt->execute([$application['user_id']]);

            if ($stmt->fetch() !== false) {
                $db->rollBack();
                show_message(409, 'Går inte att godkänna', 'Sökanden har hunnit gå med i en annan guild.');
            }

            $stmt = $db->prepare(
                "UPDATE applications
                 SET status = 'approved', handled_by = ?, handled_at = NOW()
                 WHERE id = ? AND status = 'pending'"
            );
            $stmt->execute([$current_user['id'], $application_id]);

            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                show_message(409, 'Redan hanterad', 'Någon annan hann hantera ansökan först.');
            }

            $stmt = $db->prepare(
                "INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'grunt')"
            );
            $stmt->execute([$group_id, $application['user_id']]);

            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            if ($e->getCode() === '23000') {
                show_message(409, 'Går inte att godkänna', 'Sökanden är redan medlem i guilden.');
            }

            throw $e;
        }
    }

    header('Location: /group/manage/?id=' . $group_id);
    exit;
}

$stmt = $db->prepare(
    "SELECT a.id, a.created_at, u.first_name, u.last_name, u.character_name
     FROM applications a
     JOIN users u ON u.id = a.user_id
     WHERE a.group_id = ? AND a.status = 'pending'
     ORDER BY a.created_at"
);
$stmt->execute([$group_id]);
$applications = $stmt->fetchAll();

$page_name = 'Manage ' . $group['name'];

require __DIR__ . '/../../inc/header.php';
?>

<?php box_start('Väntande ansökningar — ' . $group['name']); ?>

    <?php if (empty($applications)): ?>
        <p class="muted">Inga väntande ansökningar.</p>
    <?php else: ?>
        <ul class="group-list">
            <?php foreach ($applications as $application): ?>
                <li class="guild-row">
                    <div>
                        <span class="group-name">
                            <?= htmlspecialchars($application['character_name'] ?? $application['first_name']) ?>
                        </span><br>
                        <span class="group-desc">
                            <?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?>
                            · ansökte <?= htmlspecialchars($application['created_at']) ?>
                        </span>
                    </div>
                    <form method="post" action="/group/manage/?id=<?= $group_id ?>">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                        <button type="submit" name="decision" value="approve">Godkänn</button>
                        <button type="submit" name="decision" value="reject">Avslå</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

<?php box_end(); ?>

<p class="muted"><a href="/group/?id=<?= $group_id ?>">Tillbaka till <?= htmlspecialchars($group['name']) ?></a></p>

<?php require __DIR__ . '/../../inc/footer.php'; ?>
