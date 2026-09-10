<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/box.php';
require_once __DIR__ . '/../../inc/page.php';
require_once __DIR__ . '/../../inc/topics.php';
require_once __DIR__ . '/../../inc/csrf.php';

$current_user = current_user();
$group_id = (int) ($_GET['group'] ?? 0);

$db = get_db();
$stmt = $db->prepare("SELECT id, name, type FROM groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    show_message(404, 'Hittades inte', 'Den gruppen finns inte.');
}

if (!can_post($group, $current_user)) {
    show_message(403, 'Ingen åtkomst', $current_user === null
        ? 'Du måste vara inloggad för att starta en tråd.'
        : 'Du har inte tillgång till den här gruppen.');
}

$page_name = 'Nytt ämne';

$errors = [];
$title  = '';
$body   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');

    if ($title === '') {
        $errors[] = 'Tråden måste ha ett ämne.';
    } elseif (mb_strlen($title) > 200) {
        $errors[] = 'Ämnet får vara högst 200 tecken.';
    }

    if ($body === '') {
        $errors[] = 'Skriv ett första inlägg.';
    } elseif (mb_strlen($body) > 10000) {
        $errors[] = 'Inlägget får vara högst 10 000 tecken.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "INSERT INTO topics (group_id, user_id, title) VALUES (?, ?, ?)"
            );
            $stmt->execute([$group_id, $current_user['id'], $title]);

            $topic_id = (int) $db->lastInsertId();

            $stmt = $db->prepare(
                "INSERT INTO posts (topic_id, user_id, body) VALUES (?, ?, ?)"
            );
            $stmt->execute([$topic_id, $current_user['id'], $body]);

            $db->commit();

            header('Location: /topic/?id=' . $topic_id);
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }
}

require __DIR__ . '/../../inc/header.php';
?>

<?php page_title('Nytt ämne i ' . $group['name']); ?>

<?php box_start('Ämne och första inlägget'); ?>

    <?php foreach ($errors as $error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="post" action="/topic/create/?group=<?= (int) $group['id'] ?>">
        <?php csrf_field(); ?>
        <label for="title">Ämne</label>
        <input type="text" id="title" name="title" maxlength="200"
               value="<?= htmlspecialchars($title) ?>" required>

        <label for="body">Första inlägget</label>
        <textarea id="body" name="body" rows="6" maxlength="10000" required><?= htmlspecialchars($body) ?></textarea>

        <button class="btn-primary" type="submit">Starta tråd</button>
    </form>

<?php box_end(); ?>

<p class="muted">
    <a href="/group/?id=<?= (int) $group['id'] ?>">Tillbaka till <?= htmlspecialchars($group['name']) ?></a>
</p>

<?php require __DIR__ . '/../../inc/footer.php'; ?>
