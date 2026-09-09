<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/page.php';

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    $expected = $_SESSION['csrf_token'] ?? '';
    $sent     = $_POST['csrf_token'] ?? '';

    if ($expected === '' || !hash_equals($expected, $sent)) {
        show_message(403, 'Ogiltig begäran', 'Formuläret var för gammalt. Ladda om sidan och försök igen.');
    }
}
