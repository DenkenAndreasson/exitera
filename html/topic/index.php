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
                <p class="post-meta">
                    <span class="post-author"><?= htmlspecialchars(author_name($post)) ?></span>
                    · <?= htmlspecialchars($post['created_at']) ?>
                </p>
                <p class="post-body"><?= nl2br(htmlspecialchars($post['body'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php box_end(); ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
