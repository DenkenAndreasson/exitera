<?php
require_once __DIR__ . '/../inc/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION = [];
    session_destroy();
}

header('Location: /');
exit;
