<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/box.php';

$page_name = 'Skapa konto';

$errors = [];
$first_name = '';
$last_name = '';
$email = '';
$character_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name     = trim($_POST['first_name'] ?? '');
    $last_name      = trim($_POST['last_name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $password       = $_POST['password'] ?? '';
    $character_name = trim($_POST['character_name'] ?? '');

    if ($first_name === '' || $last_name === '' || $email === '' || $password === '') {
        $errors[] = 'Förnamn, efternamn, epost och lösenord måste fyllas i.';
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

            header('Location: /');
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

<?php box_start('Skapa konto'); ?>

    <?php foreach ($errors as $error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="post" action="/register/">
        <label for="first_name">Förnamn</label>
        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name) ?>" required>

        <label for="last_name">Efternamn</label>
        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name) ?>" required>

        <label for="email">E-post</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label for="password">Lösenord</label>
        <input type="password" id="password" name="password" required>

        <label for="character_name">Karaktärsnamn (valfritt)</label>
        <input type="text" id="character_name" name="character_name" value="<?= htmlspecialchars($character_name) ?>">

        <button type="submit">Skapa konto</button>
    </form>

<?php box_end(); ?>

<?php require __DIR__ . '/../inc/footer.php'; ?>
