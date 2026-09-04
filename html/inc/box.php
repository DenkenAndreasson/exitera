<?php

function box_start(string $title): void
{
    echo '<section class="box">';
    echo '<h2 class="box-title">' . htmlspecialchars($title) . '</h2>';
    echo '<div class="box-body">';
}

function box_end(): void
{
    echo '</div>';
    echo '</section>';
}
