<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/box.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/invites.php';

$page_name = 'Skapa konto';

$errors = [];
$first_name = '';
$last_name = '';
$email = '';
$character_name = '';
$invite = invite_param();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $first_name     = trim($_POST['first_name'] ?? '');
    $last_name      = trim($_POST['last_name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $password       = $_POST['password'] ?? '';
    $character_name = trim($_POST['character_name'] ?? '');

    if ($first_name === '' || $last_name === '' || $email === '' || $password === '') {
        $errors[] = 'Förnamn, efternamn, epost och lösenord måste fyllas i.';
    }

    if (mb_strlen($first_name) > 50 || mb_strlen($last_name) > 50) {
        $errors[] = 'Förnamn och efternamn får vara högst 50 tecken.';
    }

    if (mb_strlen($character_name) > 50) {
        $errors[] = 'Karaktärsnamnet får vara högst 50 tecken.';
    }

    if (mb_strlen($email) > 255) {
        $errors[] = 'Epostadressen får vara högst 255 tecken.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Skriv en giltig epostadress.';
    }

    if ($password !== '' && mb_strlen($password) < 8) {
        $errors[] = 'Lösenordet måste vara minst 8 tecken.';
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $db = get_db();

        try {
            $stmt = $db->prepare(
                "INSERT INTO users (first_name, last_name, email, password_hash, character_name)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $first_name,
                $last_name,
                $email,
                $password_hash,
                $character_name !== '' ? $character_name : null,
            ]);

            $_SESSION['user_id'] = (int) $db->lastInsertId();
            session_regenerate_id(true);

            header('Location: ' . invite_return_url($invite));
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Den epostadressen är redan registrerad.';
            } else {
                throw $e;
            }
        }
    }
}

require __DIR__ . '/../inc/header.php';
?>

<?php page_title('Skapa konto'); ?>

<?php box_start('Dina uppgifter'); ?>

    <?php foreach ($errors as $error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <?php if ($invite !== ''): ?>
        <p class="muted">Du har en inbjudan till en guild. Efter registreringen
        skickas du tillbaka till den.</p>
    <?php endif; ?>

    <form method="post" action="/register/">
        <?php csrf_field(); ?>
        <?php invite_field($invite); ?>
        <label for="first_name">Förnamn</label>
        <input type="text" id="first_name" name="first_name" maxlength="50" value="<?= htmlspecialchars($first_name) ?>" required>

        <label for="last_name">Efternamn</label>
        <input type="text" id="last_name" name="last_name" maxlength="50" value="<?= htmlspecialchars($last_name) ?>" required>

        <label for="email">E-post</label>
        <input type="email" id="email" name="email" maxlength="255" value="<?= htmlspecialchars($email) ?>" required>

        <label for="password">Lösenord</label>
        <input type="password" id="password" name="password" minlength="8" required>

        <label for="character_name">Karaktärsnamn (valfritt)</label>
        <input type="text" id="character_name" name="character_name" maxlength="50" value="<?= htmlspecialchars($character_name) ?>">

        <button class="btn-primary" type="submit">Skapa konto</button>
    </form>

<?php box_end(); ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
