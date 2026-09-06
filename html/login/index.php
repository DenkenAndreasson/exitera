<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/box.php';

$page_name = 'Logga in';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        session_regenerate_id(true);

        header('Location: /');
        exit;
    }

    $errors[] = 'Fel epost eller lösenord.';
}

require __DIR__ . '/../inc/header.php';
?>

<?php box_start('Logga in'); ?>

    <?php foreach ($errors as $error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="post" action="/login/">
        <label for="email">E-post</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label for="password">Lösenord</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Logga in</button>
    </form>
    <p class="muted">Har du inget konto? <a href="/register/">Skapa ett här</a>.</p>

<?php box_end(); ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
