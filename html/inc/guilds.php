<?php

require_once __DIR__ . '/auth.php';

function community_list(): array
{
    return get_db()->query(
        "SELECT id, name, description
         FROM groups
         WHERE type = 'community'
         ORDER BY id"
    )->fetchAll();
}

function guild_list(): array
{
    return get_db()->query(
        "SELECT g.id, g.name, g.description, COUNT(m.id) AS member_count
         FROM groups g
         LEFT JOIN group_members m ON m.group_id = g.id
         WHERE g.type = 'guild'
         GROUP BY g.id, g.name, g.description
         ORDER BY g.name"
    )->fetchAll();
}

function pending_group_ids(int $user_id): array
{
    $stmt = get_db()->prepare(
        "SELECT group_id FROM applications WHERE user_id = ? AND status = 'pending'"
    );
    $stmt->execute([$user_id]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function pending_count(int $group_id): int
{
    $stmt = get_db()->prepare(
        "SELECT COUNT(*) FROM applications WHERE group_id = ? AND status = 'pending'"
    );
    $stmt->execute([$group_id]);

    return (int) $stmt->fetchColumn();
}

function guild_button_state(int $guild_id, ?array $user, ?array $my_guild, array $pending_ids): string
{
    if ($my_guild !== null && (int) $my_guild['id'] === $guild_id) {
        return 'member';
    }

    if (in_array($guild_id, $pending_ids, true)) {
        return 'pending';
    }

    if ($user === null || $user['is_admin'] || $my_guild !== null) {
        return 'blocked';
    }

    return 'apply';
}

function guild_button(int $guild_id, string $state, string $from): void
{
    switch ($state) {
        case 'member':
            echo '<a href="/group/?id=' . $guild_id . '">Öppna &rarr;</a>';
            break;

        case 'pending':
            echo '<button type="button" disabled>Väntar på svar</button>';
            break;

        case 'apply':
            echo '<form method="post" action="/group/apply/">';
            echo '<input type="hidden" name="group_id" value="' . $guild_id . '">';
            echo '<input type="hidden" name="from" value="' . htmlspecialchars($from) . '">';
            echo '<button type="submit">Ansök</button>';
            echo '</form>';
            break;

        default:
            echo '<button type="button" disabled>Ansök</button>';
    }
}
