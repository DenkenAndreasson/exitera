<?php

require_once __DIR__ . '/auth.php';

function create_invite(int $group_id, int $user_id): string
{
    $token = bin2hex(random_bytes(32));

    $stmt = get_db()->prepare(
        "INSERT INTO invites (group_id, token, created_by, expires_at)
         VALUES (?, ?, ?, NOW() + INTERVAL 24 HOUR)"
    );
    $stmt->execute([$group_id, $token, $user_id]);

    return $token;
}

function active_invites(int $group_id): array
{
    $stmt = get_db()->prepare(
        "SELECT i.token, i.expires_at,
                u.character_name, u.first_name
         FROM invites i
         LEFT JOIN users u ON u.id = i.created_by
         WHERE i.group_id = ?
           AND i.used_at IS NULL
           AND i.expires_at > NOW()
         ORDER BY i.expires_at DESC"
    );
    $stmt->execute([$group_id]);

    return $stmt->fetchAll();
}

function invite_by_token(string $token): ?array
{
    $stmt = get_db()->prepare(
        "SELECT i.id, i.group_id, i.expires_at, i.used_at,
                i.expires_at > NOW() AS still_valid,
                g.name AS group_name, g.type AS group_type
         FROM invites i
         JOIN groups g ON g.id = i.group_id
         WHERE i.token = ?"
    );
    $stmt->execute([$token]);

    return $stmt->fetch() ?: null;
}

function invite_url(string $token): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return 'http://' . $host . '/invite/?token=' . $token;
}
