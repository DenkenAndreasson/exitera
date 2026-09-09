<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/box.php';
require_once __DIR__ . '/../../inc/page.php';
require_once __DIR__ . '/../../inc/csrf.php';

$current_user = current_user();

if ($current_user === null) {
    show_message(403, 'Ingen åtkomst', 'Du måste vara inloggad för att skapa en guild.');
}

if ($current_user['is_admin']) {
    show_message(403, 'Ingen åtkomst', 'Sajt-admin går inte med i guilds.');
}

if (my_guild($current_user['id']) !== null) {
    show_message(403, 'Ingen åtkomst', 'Du är redan med i en guild. Du kan bara vara med i en åt gången.');
}

$page_name = 'Skapa guild';

$errors      = [];
$name        = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = 'Guilden måste ha ett namn.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = 'Namnet får vara högst 100 tecken.';
    }

    if ($description === '') {
        $errors[] = 'Skriv en kort beskrivning av guilden.';
    }

    if (empty($errors)) {
        $db = get_db();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "INSERT INTO groups (name, description, type, created_by)
                 VALUES (?, ?, 'guild', ?)"
            );
            $stmt->execute([$name, $description, $current_user['id']]);

            $group_id = (int) $db->lastInsertId();

            $stmt = $db->prepare(
                "INSERT INTO group_members (group_id, user_id, role)
                 VALUES (?, ?, 'leader')"
            );
            $stmt->execute([$group_id, $current_user['id']]);

            $db->commit();

            header('Location: /group/?id=' . $group_id);
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            if ($e->getCode() === '23000') {
                $errors[] = 'Det finns redan en guild med det namnet.';
            } else {
                throw $e;
            }
        }
    }
}

require __DIR__ . '/../../inc/header.php';
?>

<?php box_start('Skapa guild'); ?>

    <?php foreach ($errors as $error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="post" action="/group/create/">
        <?php csrf_field(); ?>
        <label for="name">Namn</label>
        <input type="text" id="name" name="name" maxlength="100"
               value="<?= htmlspecialchars($name) ?>" required>

        <label for="description">Beskrivning</label>
        <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($description) ?></textarea>

        <button type="submit">Skapa guild</button>
    </form>

    <p class="muted">Du blir Guild leader i guilden du skapar.</p>

<?php box_end(); ?>

<?php require __DIR__ . '/../../inc/footer.php'; ?>
