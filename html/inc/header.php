<?php
if (!isset($page_name)) {
    $page_name = 'Start';
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_name) ?> - OT Forum</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<header class="site-header">
    <h1><a href="/">OT Forum</a></h1>
    <nav>
        <a href="/">Start</a>
        <a href="/groups/">Alla grupper</a>
        <a href="/login/">Logga in</a>
        <a href="/register/">Skapa konto</a>
    </nav>
</header>

<main>
