<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/box.php';

function page_title(string $text): void
{
    echo '<h1 class="page-title">' . htmlspecialchars($text) . '</h1>';
}

function set_flash(string $text): void
{
    $_SESSION['flash'] = $text;
}

function show_flash(): void
{
    if (!isset($_SESSION['flash'])) {
        return;
    }

    echo '<p class="flash">' . htmlspecialchars($_SESSION['flash']) . '</p>';

    unset($_SESSION['flash']);
}

function format_time(?string $sql_time): string
{
    if ($sql_time === null) {
        return '';
    }

    $months = ['jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

    $time = strtotime($sql_time);
    $year = date('Y', $time) === date('Y') ? '' : ' ' . date('Y', $time);

    return (int) date('j', $time)
        . ' ' . $months[(int) date('n', $time) - 1]
        . $year
        . ' ' . date('H:i', $time);
}

function show_message(int $status, string $title, string $text): never
{
    http_response_code($status);

    $page_name = $title;
    require __DIR__ . '/header.php';

    page_title($title);
    echo '<p class="error">' . htmlspecialchars($text) . '</p>';
    echo '<p class="muted"><a href="/">Till startsidan</a></p>';

    require __DIR__ . '/footer.php';
    exit;
}
