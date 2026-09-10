<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/box.php';
require_once __DIR__ . '/../../inc/page.php';
require_once __DIR__ . '/../../inc/csrf.php';
require_once __DIR__ . '/../../inc/guilds.php';
require_once __DIR__ . '/../../inc/invites.php';

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

$my_level = role_level($current_user, membership_in($group_id, $current_user['id']));

if ($my_level < 3) {
    show_message(403, 'Ingen åtkomst', 'Bara General och uppåt kan hantera ansökningar i den här guilden.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action = $_POST['action'] ?? '';

    if ($action === 'decide') {
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

            set_flash('Ansökan är avslagen.');
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

                set_flash('Ansökan är godkänd. Personen är nu Grunt i guilden.');
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
    } elseif ($action === 'set_role' || $action === 'kick') {
        if ($my_level < 4) {
            show_message(403, 'Ingen åtkomst', 'Bara Guild leader kan ändra roller och ta bort medlemmar.');
        }

        $target_id = (int) ($_POST['user_id'] ?? 0);
        $new_role  = $_POST['role'] ?? '';

        if ($action === 'set_role' && !in_array($new_role, ['grunt', 'general', 'leader'], true)) {
            show_message(400, 'Ogiltig roll', 'Rollen måste vara Grunt, General eller Guild leader.');
        }

        if ($action === 'kick' && $target_id === (int) $current_user['id']) {
            show_message(409, 'Går inte', 'Du kan inte ta bort dig själv ur guilden.');
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "SELECT user_id, role FROM group_members WHERE group_id = ? FOR UPDATE"
            );
            $stmt->execute([$group_id]);

            $target  = null;
            $leaders = 0;

            foreach ($stmt->fetchAll() as $member) {
                if ((int) $member['user_id'] === $target_id) {
                    $target = $member;
                }

                if ($member['role'] === 'leader') {
                    $leaders++;
                }
            }

            if ($target === null) {
                $db->rollBack();
                show_message(404, 'Hittades inte', 'Den användaren är inte medlem i den här guilden.');
            }

            $loses_leader = $target['role'] === 'leader'
                && ($action === 'kick' || $new_role !== 'leader');

            if ($loses_leader && $leaders === 1) {
                $db->rollBack();
                show_message(409, 'Går inte', 'Guilden måste ha minst en Guild leader kvar.');
            }

            if ($action === 'set_role') {
                $stmt = $db->prepare(
                    "UPDATE group_members SET role = ? WHERE group_id = ? AND user_id = ?"
                );
                $stmt->execute([$new_role, $group_id, $target_id]);

                set_flash('Rollen är uppdaterad till ' . role_name($new_role) . '.');
            } else {
                $stmt = $db->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
                $stmt->execute([$group_id, $target_id]);

                $stmt = $db->prepare("DELETE FROM applications WHERE group_id = ? AND user_id = ?");
                $stmt->execute([$group_id, $target_id]);

                set_flash('Medlemmen är borttagen ur guilden.');
            }

            $db->commit();
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    } elseif ($action === 'create_invite') {
        if ($my_level < 4) {
            show_message(403, 'Ingen åtkomst', 'Bara Guild leader kan skapa inbjudningslänkar.');
        }

        create_invite($group_id, (int) $current_user['id']);

        set_flash('Inbjudningslänken är skapad och gäller i 24 timmar.');
    } else {
        show_message(400, 'Ogiltig begäran', 'Okänd åtgärd.');
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

$members = member_list($group_id);
$invites = $my_level >= 4 ? active_invites($group_id) : [];

$page_name = 'Manage ' . $group['name'];

require __DIR__ . '/../../inc/header.php';
?>

<?php page_title('Manage ' . $group['name']); ?>

<?php box_start('Väntande ansökningar'); ?>

    <?php if (empty($applications)): ?>
        <p class="muted">Inga väntande ansökningar.</p>
    <?php else: ?>
        <ul class="group-list">
            <?php foreach ($applications as $application): ?>
                <li class="guild-row">
                    <div>
                        <span class="group-name">
                            <?= htmlspecialchars(author_name($application)) ?>
                        </span><br>
                        <span class="group-desc">
                            <?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?>
                            · ansökte <?= htmlspecialchars(format_time($application['created_at'])) ?>
                        </span>
                    </div>
                    <form method="post" action="/group/manage/?id=<?= $group_id ?>">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="decide">
                        <input type="hidden" name="application_id" value="<?= (int) $application['id'] ?>">
                        <button class="btn-primary" type="submit" name="decision" value="approve">Godkänn</button>
                        <button class="btn-danger" type="submit" name="decision" value="reject">Avslå</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

<?php box_end(); ?>

<?php box_start('Medlemmar'); ?>

    <?php if ($my_level < 4): ?>
        <p class="muted">Bara Guild leader kan ändra roller.</p>
    <?php endif; ?>

    <ul class="group-list">
        <?php foreach ($members as $member): ?>
            <li class="guild-row">
                <div>
                    <span class="group-name"><?= htmlspecialchars(author_name($member)) ?></span><br>
                    <span class="group-desc">
                        <?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>
                        · <?= htmlspecialchars(role_name($member['role'])) ?>
                        · medlem sedan <?= htmlspecialchars(format_time($member['joined_at'])) ?>
                    </span>
                </div>

                <?php if ($my_level >= 4): ?>
                    <form method="post" action="/group/manage/?id=<?= $group_id ?>">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="user_id" value="<?= (int) $member['user_id'] ?>">
                        <select name="role">
                            <?php foreach (['grunt', 'general', 'leader'] as $role): ?>
                                <option value="<?= $role ?>" <?= $member['role'] === $role ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(role_name($role)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="action" value="set_role">Spara</button>
                        <?php if ((int) $member['user_id'] !== (int) $current_user['id']): ?>
                            <button class="btn-danger" type="submit" name="action" value="kick">Ta bort</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

<?php box_end(); ?>

<?php if ($my_level >= 4): ?>

    <?php box_start('Inbjudningslänkar'); ?>

        <p class="muted">
            En inbjudningslänk går att använda <strong>en gång</strong> och är giltig i
            <strong>24 timmar</strong>. Den som använder den blir medlem direkt, utan
            att ansöka.
        </p>

        <?php if (empty($invites)): ?>
            <p class="muted">Inga aktiva länkar just nu.</p>
        <?php else: ?>
            <ul class="group-list">
                <?php foreach ($invites as $invite): ?>
                    <li>
                        <code class="invite-url"><?= htmlspecialchars(invite_url($invite['token'])) ?></code><br>
                        <span class="group-desc">
                            Giltig till <?= htmlspecialchars(format_time($invite['expires_at'])) ?>
                            · skapad av <?= htmlspecialchars(author_name($invite)) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" action="/group/manage/?id=<?= $group_id ?>">
            <?php csrf_field(); ?>
            <button type="submit" name="action" value="create_invite">Skapa inbjudningslänk</button>
        </form>

    <?php box_end(); ?>

<?php endif; ?>

<p class="muted"><a href="/group/?id=<?= $group_id ?>">Tillbaka till <?= htmlspecialchars($group['name']) ?></a></p>

<?php require __DIR__ . '/../../inc/footer.php'; ?>
