<?php

require_once __DIR__ . '/auth.php';

function topic_list(int $group_id): array
{
    $stmt = get_db()->prepare(
        "SELECT t.id, t.title, t.created_at,
                u.character_name, u.first_name,
                COUNT(p.id) AS post_count,
                COALESCE(MAX(p.created_at), t.created_at) AS last_activity
         FROM topics t
         LEFT JOIN users u ON u.id = t.user_id
         LEFT JOIN posts p ON p.topic_id = t.id AND p.deleted_at IS NULL
         WHERE t.group_id = ? AND t.deleted_at IS NULL
         GROUP BY t.id, t.title, t.created_at, u.character_name, u.first_name
         ORDER BY last_activity DESC"
    );
    $stmt->execute([$group_id]);

    return $stmt->fetchAll();
}

function topic_with_group(int $topic_id): ?array
{
    $stmt = get_db()->prepare(
        "SELECT t.id, t.title, t.created_at, t.deleted_at, t.user_id,
                g.id AS group_id, g.name AS group_name, g.type AS group_type
         FROM topics t
         JOIN groups g ON g.id = t.group_id
         WHERE t.id = ?"
    );
    $stmt->execute([$topic_id]);
    $row = $stmt->fetch();

    if ($row === false) {
        return null;
    }

    return [
        'topic' => [
            'id'         => (int) $row['id'],
            'title'      => $row['title'],
            'created_at' => $row['created_at'],
            'deleted_at' => $row['deleted_at'],
            'user_id'    => $row['user_id'],
        ],
        'group' => [
            'id'   => (int) $row['group_id'],
            'name' => $row['group_name'],
            'type' => $row['group_type'],
        ],
    ];
}

function post_list(int $topic_id): array
{
    $stmt = get_db()->prepare(
        "SELECT p.id, p.body, p.created_at, p.deleted_at,
                u.character_name, u.first_name
         FROM posts p
         LEFT JOIN users u ON u.id = p.user_id
         WHERE p.topic_id = ?
         ORDER BY p.created_at, p.id"
    );
    $stmt->execute([$topic_id]);

    return $stmt->fetchAll();
}
