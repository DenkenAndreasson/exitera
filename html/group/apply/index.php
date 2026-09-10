<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/page.php';
require_once __DIR__ . '/../../inc/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    show_message(405, 'Fel metod', 'Ansökningar skickas via formuläret på startsidan.');
}

csrf_check();

$current_user = current_user();

if ($current_user === null) {
    show_message(403, 'Ingen åtkomst', 'Du måste vara inloggad för att ansöka till en guild.');
}

if ($current_user['is_admin']) {
    show_message(403, 'Ingen åtkomst', 'Sajt-admin går inte med i guilds.');
}

if (my_guild($current_user['id']) !== null) {
    show_message(403, 'Ingen åtkomst', 'Du är redan med i en guild. Du kan bara vara med i en åt gången.');
}

$group_id = (int) ($_POST['group_id'] ?? 0);

$db = get_db();
$stmt = $db->prepare("SELECT id, type FROM groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group || $group['type'] !== 'guild') {
    show_message(404, 'Hittades inte', 'Den guilden finns inte.');
}

$stmt = $db->prepare("SELECT status FROM applications WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $current_user['id']]);
$existing = $stmt->fetch();

if ($existing !== false) {
    $text = match ($existing['status']) {
        'pending'  => 'Du har redan en väntande ansökan till den guilden.',
        'rejected' => 'Din ansökan till den guilden har avslagits.',
        'approved' => 'Din ansökan till den guilden är redan godkänd.',
    };

    show_message(403, 'Redan ansökt', $text);
}

try {
    $stmt = $db->prepare(
        "INSERT INTO applications (group_id, user_id, status) VALUES (?, ?, 'pending')"
    );
    $stmt->execute([$group_id, $current_user['id']]);
} catch (PDOException $e) {
    if ($e->getCode() !== '23000') {
        throw $e;
    }
}

set_flash('Ansökan är skickad. Guildens ledning får svara.');

$from = $_POST['from'] ?? '/';
header('Location: ' . (in_array($from, ['/', '/groups/'], true) ? $from : '/'));
exit;
