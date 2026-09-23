<?php
declare(strict_types=1);

function find_agreement_for_user(int $agreementId, int $userId): ?array
{
    $stmt = db()->prepare('SELECT a.* FROM agreements a WHERE a.id = ? AND a.creator_id = ? LIMIT 1');
    $stmt->execute([$agreementId, $userId]);
    return $stmt->fetch() ?: null;
}

function find_agreement_by_token(string $token): ?array
{
    $stmt = db()->prepare('SELECT a.*, u.name AS creator_name FROM agreements a JOIN users u ON u.id = a.creator_id WHERE a.invite_token = ? LIMIT 1');
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

function current_version(int $agreementId): ?array
{
    $stmt = db()->prepare('SELECT * FROM agreement_versions WHERE agreement_id = ? ORDER BY version_number DESC LIMIT 1');
    $stmt->execute([$agreementId]);
    return $stmt->fetch() ?: null;
}

function version_clauses(int $versionId): array
{
    $stmt = db()->prepare('SELECT * FROM clauses WHERE version_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->execute([$versionId]);
    return $stmt->fetchAll();
}

function agreement_parties(int $agreementId): array
{
    $stmt = db()->prepare('SELECT * FROM agreement_parties WHERE agreement_id = ? ORDER BY id ASC');
    $stmt->execute([$agreementId]);
    return $stmt->fetchAll();
}

function guest_party(int $agreementId): ?array
{
    $stmt = db()->prepare("SELECT * FROM agreement_parties WHERE agreement_id = ? AND role = 'guest' ORDER BY id ASC LIMIT 1");
    $stmt->execute([$agreementId]);
    return $stmt->fetch() ?: null;
}

function log_event(int $agreementId, string $type, ?int $actorUserId = null, ?string $actorLabel = null, array $old = [], array $new = []): void
{
    $stmt = db()->prepare('INSERT INTO event_logs (agreement_id, actor_user_id, actor_label, event_type, old_value_json, new_value_json, ip_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $agreementId,
        $actorUserId,
        $actorLabel,
        $type,
        $old === [] ? null : json_encode($old, JSON_UNESCAPED_UNICODE),
        $new === [] ? null : json_encode($new, JSON_UNESCAPED_UNICODE),
        request_ip_hash(),
    ]);
}

function create_version(int $agreementId, array $content, ?int $creatorUserId, string $creatorLabel): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(version_number), 0) + 1 AS next_version FROM agreement_versions WHERE agreement_id = ?');
    $stmt->execute([$agreementId]);
    $versionNumber = (int) ($stmt->fetch()['next_version'] ?? 1);
    $json = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $hash = hash('sha256', (string) $json);

    $stmt = $pdo->prepare('INSERT INTO agreement_versions (agreement_id, version_number, content_json, hash, created_by_user_id, created_by_label, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$agreementId, $versionNumber, $json, $hash, $creatorUserId, $creatorLabel]);
    $id = (int) $pdo->lastInsertId();

    $sort = 10;
    $clauseStmt = $pdo->prepare('INSERT INTO clauses (version_id, clause_key, title, content, sort_order, source, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    foreach (($content['clauses'] ?? []) as $clause) {
        if (trim((string) ($clause['content'] ?? '')) === '') {
            continue;
        }
        $clauseStmt->execute([
            $id,
            (string) ($clause['key'] ?? 'note'),
            (string) ($clause['title'] ?? 'Detay'),
            trim((string) $clause['content']),
            $sort,
            (string) ($clause['source'] ?? 'user'),
        ]);
        $sort += 10;
    }

    return ['id' => $id, 'version_number' => $versionNumber, 'hash' => $hash, 'content_json' => $json];
}

function agreement_share_url(array $agreement): string
{
    return page_url('invite', ['token' => $agreement['invite_token']]);
}
