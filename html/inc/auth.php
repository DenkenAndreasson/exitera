<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function current_user(): ?array
{
    static $user = null;
    static $loaded = false;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    return $user;
}

function require_login(): void
{
    if (current_user() === null) {
        header('Location: /login/');
        exit;
    }
}

function role_level(array $user, ?array $membership): int
{
    if ($user['is_admin']) {
        return 5;
    }

    if ($membership === null) {
        return 1;
    }

    return match ($membership['role']) {
        'grunt'   => 2,
        'general' => 3,
        'leader'  => 4,
    };
}

function membership_in(int $group_id, int $user_id): ?array
{
    $db = get_db();
    $stmt = $db->prepare(
        "SELECT id, group_id, user_id, role, joined_at
         FROM group_members
         WHERE group_id = ? AND user_id = ?"
    );
    $stmt->execute([$group_id, $user_id]);

    return $stmt->fetch() ?: null;
}

function my_guild(int $user_id): ?array
{
    $db = get_db();
    $stmt = $db->prepare(
        "SELECT g.id, g.name, g.description, m.role
         FROM group_members m
         JOIN groups g ON g.id = m.group_id
         WHERE m.user_id = ? AND g.type = 'guild'
         LIMIT 1"
    );
    $stmt->execute([$user_id]);

    return $stmt->fetch() ?: null;
}

function can_read(array $group, ?array $user): bool
{
    if ($group['type'] === 'community') {
        return true;
    }

    if ($user === null) {
        return false;
    }

    if ($user['is_admin']) {
        return true;
    }

    return membership_in($group['id'], $user['id']) !== null;
}

function can_post(array $group, ?array $user): bool
{
    return $user !== null && can_read($group, $user);
}

function role_name(string $role): string
{
    return match ($role) {
        'grunt'   => 'Grunt',
        'general' => 'General',
        'leader'  => 'Guild leader',
    };
}
