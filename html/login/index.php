<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/box.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/invites.php';

$page_name = 'Logga in';

$errors = [];
$email = '';
$invite = invite_param();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        session_regenerate_id(true);

        header('Location: ' . invite_return_url($invite));
        exit;
    }

    $errors[] = 'Fel epost eller lösenord.';
}

require __DIR__ . '/../inc/header.php';
?>

<?php page_title('Logga in'); ?>

<?php box_start('Dina uppgifter'); ?>

    <?php foreach ($errors as $error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <?php if ($invite !== ''): ?>
        <p class="muted">Du har en inbjudan till en guild. Efter inloggningen
        skickas du tillbaka till den.</p>
    <?php endif; ?>

    <form method="post" action="/login/">
        <?php csrf_field(); ?>
        <?php invite_field($invite); ?>
        <label for="email">E-post</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label for="password">Lösenord</label>
        <input type="password" id="password" name="password" required>

        <button class="btn-primary" type="submit">Logga in</button>
    </form>
    <p class="muted">Har du inget konto?
    <a href="/register/<?= $invite !== '' ? '?invite=' . htmlspecialchars($invite) : '' ?>">Skapa ett här</a>.</p>

<?php box_end(); ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
