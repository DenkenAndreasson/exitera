<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

if (!isset($page_name)) {
    $page_name = 'Start';
}

$current_user = current_user();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_name) ?> - Exitera</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<header class="site-header">
    <p class="site-title"><a href="/">Exitera</a></p>
</header>

<nav class="site-nav">
    <div class="nav-inner">
        <a href="/">Start</a>
        <a href="/groups/">Alla grupper</a>
        <?php if ($current_user === null): ?>
            <a href="/login/">Logga in</a>
            <a href="/register/">Skapa konto</a>
        <?php else: ?>
            <form method="post" action="/logout/" class="nav-logout">
                <?php csrf_field(); ?>
                <button type="submit">Logga ut</button>
            </form>
        <?php endif; ?>
    </div>
</nav>

<main>

<?php show_flash(); ?>
