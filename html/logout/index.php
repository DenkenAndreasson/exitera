<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $_SESSION = [];
    setcookie(session_name(), '', time() - 3600, '/');
    session_destroy();
}

header('Location: /');
exit;
