<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/box.php';
require_once __DIR__ . '/../inc/page.php';
require_once __DIR__ . '/../inc/topics.php';

$current_user = current_user();
$topic_id = (int) ($_GET['id'] ?? 0);

$found = topic_with_group($topic_id);

if ($found === null || $found['topic']['deleted_at'] !== null) {
    show_message(404, 'Hittades inte', 'Den tråden finns inte.');
}

$topic = $found['topic'];
$group = $found['group'];

if (!can_read($group, $current_user)) {
    show_message(403, 'Ingen åtkomst', $current_user === null
        ? 'Du måste vara inloggad för att se den här tråden.'
        : 'Du har inte tillgång till den här tråden.');
}

$may_post = can_post($group, $current_user);

$may_moderate = $current_user !== null
    && role_level($current_user, membership_in($group['id'], $current_user['id'])) >= 3;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $db = get_db();

    if ($action === 'reply') {
        if (!$may_post) {
            show_message(403, 'Ingen åtkomst', 'Du har inte tillgång till den här tråden.');
        }

        $body = trim($_POST['body'] ?? '');

        if ($body === '') {
            show_message(400, 'Tomt inlägg', 'Ett svar kan inte vara tomt.');
        }

        if (mb_strlen($body) > 10000) {
            show_message(400, 'För långt inlägg', 'Ett svar får vara högst 10 000 tecken.');
        }

        $stmt = $db->prepare(
            "INSERT INTO posts (topic_id, user_id, body) VALUES (?, ?, ?)"
        );
        $stmt->execute([$topic['id'], $current_user['id'], $body]);

        header('Location: /topic/?id=' . $topic['id']);
        exit;
    }

    if ($action === 'delete_post' || $action === 'delete_topic') {
        if (!$may_moderate) {
            show_message(403, 'Ingen åtkomst', 'Bara General och uppåt kan ta bort innehåll här.');
        }

        if ($action === 'delete_topic') {
            $stmt = $db->prepare(
                "UPDATE topics SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL"
            );
            $stmt->execute([$topic['id']]);

            header('Location: /group/?id=' . $group['id']);
            exit;
        }

        $post_id = (int) ($_POST['post_id'] ?? 0);

        $stmt = $db->prepare(
            "UPDATE posts SET deleted_at = NOW()
             WHERE id = ? AND topic_id = ? AND deleted_at IS NULL"
        );
        $stmt->execute([$post_id, $topic['id']]);

        header('Location: /topic/?id=' . $topic['id']);
        exit;
    }

    show_message(400, 'Ogiltig begäran', 'Okänd åtgärd.');
}

$posts = post_list($topic['id']);

$page_name = $topic['title'];

require __DIR__ . '/../inc/header.php';
?>

<p class="muted">
    <a href="/group/?id=<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></a>
</p>

<?php box_start($topic['title']); ?>

    <?php if (empty($posts)): ?>
        <p class="muted">Inga inlägg i den här tråden.</p>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post">
                <?php if ($post['deleted_at'] !== null): ?>
                    <p class="post-body muted">[Inlägget är borttaget]</p>
                <?php else: ?>
                    <p class="post-meta">
                        <span class="post-author"><?= htmlspecialchars(author_name($post)) ?></span>
                        · <?= htmlspecialchars($post['created_at']) ?>
                    </p>
                    <p class="post-body"><?= nl2br(htmlspecialchars($post['body'])) ?></p>
                    <?php if ($may_moderate): ?>
                        <form class="post-actions" method="post" action="/topic/?id=<?= $topic['id'] ?>">
                            <input type="hidden" name="action" value="delete_post">
                            <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                            <button type="submit">Ta bort inlägg</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php box_end(); ?>

<?php if ($may_post): ?>

    <?php box_start('Svara'); ?>
        <form method="post" action="/topic/?id=<?= $topic['id'] ?>">
            <input type="hidden" name="action" value="reply">
            <label for="body">Ditt svar</label>
            <textarea id="body" name="body" rows="5" maxlength="10000" required></textarea>
            <button type="submit">Skicka svar</button>
        </form>
    <?php box_end(); ?>

<?php else: ?>

    <p class="muted"><a href="/login/">Logga in</a> för att svara.</p>

<?php endif; ?>

<?php if ($may_moderate): ?>
    <form class="post-actions" method="post" action="/topic/?id=<?= $topic['id'] ?>">
        <input type="hidden" name="action" value="delete_topic">
        <button type="submit">Ta bort hela tråden</button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
