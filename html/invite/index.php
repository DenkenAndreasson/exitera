<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/box.php';
require_once __DIR__ . '/../inc/page.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/invites.php';

$token = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['token'] ?? '')
    : ($_GET['token'] ?? '');

$invite = invite_by_token($token);

if ($invite === null || $invite['group_type'] !== 'guild') {
    show_message(404, 'Inbjudan finns inte', 'Länken är felaktig eller har aldrig funnits.');
}

if ($invite['used_at'] !== null) {
    show_message(410, 'Inbjudan är använd', 'Den här länken har redan använts en gång och går inte att använda igen.');
}

if (!$invite['still_valid']) {
    show_message(410, 'Inbjudan har gått ut', 'Länken var giltig i 24 timmar och har passerat sin utgångstid.');
}

$current_user = current_user();
$group_id     = (int) $invite['group_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($current_user === null) {
        show_message(403, 'Ingen åtkomst', 'Du måste vara inloggad för att använda en inbjudan.');
    }

    if ($current_user['is_admin']) {
        show_message(403, 'Ingen åtkomst', 'Sajt-admin går inte med i guilds.');
    }

    $db = get_db();

    try {
        $db->beginTransaction();

        $stmt = $db->prepare(
            "SELECT m.id
             FROM group_members m
             JOIN groups g ON g.id = m.group_id
             WHERE m.user_id = ? AND g.type = 'guild'
             FOR UPDATE"
        );
        $stmt->execute([$current_user['id']]);

        if ($stmt->fetch() !== false) {
            $db->rollBack();
            show_message(403, 'Ingen åtkomst', 'Du är redan med i en guild. Du kan bara vara med i en åt gången.');
        }

        $stmt = $db->prepare(
            "UPDATE invites
             SET used_at = NOW(), used_by = ?
             WHERE id = ? AND used_at IS NULL AND expires_at > NOW()"
        );
        $stmt->execute([$current_user['id'], $invite['id']]);

        if ($stmt->rowCount() === 0) {
            $db->rollBack();
            show_message(410, 'Inbjudan är använd', 'Någon annan hann använda länken först.');
        }

        $stmt = $db->prepare(
            "INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'grunt')"
        );
        $stmt->execute([$group_id, $current_user['id']]);

        $stmt = $db->prepare(
            "DELETE FROM applications WHERE group_id = ? AND user_id = ? AND status = 'pending'"
        );
        $stmt->execute([$group_id, $current_user['id']]);

        $db->commit();
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        if ($e->getCode() === '23000') {
            show_message(409, 'Går inte att använda', 'Du är redan medlem i guilden.');
        }

        throw $e;
    }

    header('Location: /group/?id=' . $group_id);
    exit;
}

$my_membership = $current_user !== null
    ? membership_in($group_id, $current_user['id'])
    : null;

$my_other_guild = $current_user !== null
    ? my_guild($current_user['id'])
    : null;

$page_name = 'Inbjudan till ' . $invite['group_name'];

require __DIR__ . '/../inc/header.php';
?>

<?php box_start('Inbjudan till ' . $invite['group_name']); ?>

    <p>
        Du har blivit inbjuden till guilden
        <strong><?= htmlspecialchars($invite['group_name']) ?></strong>.
        Med den här länken slipper du ansöka och vänta på godkännande.
    </p>

    <p class="muted">
        Länken går att använda <strong>en gång</strong> och slutar gälla
        <?= htmlspecialchars($invite['expires_at']) ?>.
    </p>

    <?php if ($current_user === null): ?>

        <p><a href="/login/">Logga in</a> eller <a href="/register/">skapa ett konto</a>,
        och öppna sedan länken igen.</p>

    <?php elseif ($current_user['is_admin']): ?>

        <p class="error">Sajt-admin går inte med i guilds.</p>

    <?php elseif ($my_membership !== null): ?>

        <p>Du är redan medlem här.
        <a href="/group/?id=<?= $group_id ?>">Öppna <?= htmlspecialchars($invite['group_name']) ?></a></p>

    <?php elseif ($my_other_guild !== null): ?>

        <p class="error">
            Du är redan med i <?= htmlspecialchars($my_other_guild['name']) ?>.
            Du kan bara vara med i en guild åt gången.
        </p>

    <?php else: ?>

        <form method="post" action="/invite/">
            <?php csrf_field(); ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <button type="submit">Gå med i <?= htmlspecialchars($invite['group_name']) ?></button>
        </form>

    <?php endif; ?>

<?php box_end(); ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
