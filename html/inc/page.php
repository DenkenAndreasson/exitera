<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/box.php';

function show_message(int $status, string $title, string $text): never
{
    http_response_code($status);

    $page_name = $title;
    require __DIR__ . '/header.php';

    box_start($title);
    echo '<p class="error">' . htmlspecialchars($text) . '</p>';
    echo '<p class="muted"><a href="/">Till startsidan</a></p>';
    box_end();

    require __DIR__ . '/footer.php';
    exit;
}
