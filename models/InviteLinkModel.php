<?php
declare(strict_types=1);

final class InviteLinkModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $createdBy, string $label, int $maxUses, ?string $expiresAt, array $defaultPermissions = []): int
    {
        $token = bin2hex(random_bytes(24));
        $stmt = $this->db->prepare(
            'INSERT INTO invite_links (token, created_by, label, max_uses, expires_at, default_permissions)
             VALUES (:token, :created_by, :label, :max_uses, :expires_at, :default_permissions)'
        );
        $stmt->execute([
            ':token'               => $token,
            ':created_by'          => $createdBy,
            ':label'               => $label !== '' ? $label : null,
            ':max_uses'            => $maxUses,
            ':expires_at'          => $expiresAt,
            ':default_permissions' => $defaultPermissions !== [] ? json_encode($defaultPermissions) : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM invite_links WHERE token = :token LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function isValid(array $link): bool
    {
        if (!(bool)$link['is_active']) {
            return false;
        }
        if ((int)$link['max_uses'] > 0 && (int)$link['used_count'] >= (int)$link['max_uses']) {
            return false;
        }
        if ($link['expires_at'] !== null && strtotime((string)$link['expires_at']) < time()) {
            return false;
        }
        return true;
    }

    public function incrementUsed(int $linkId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE invite_links SET used_count = used_count + 1 WHERE link_id = :id'
        );
        $stmt->execute([':id' => $linkId]);
    }

    public function list(int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            'SELECT il.*, u.name AS creator_name
             FROM invite_links il
             LEFT JOIN users u ON u.user_id = il.created_by
             ORDER BY il.link_id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function deactivate(int $linkId): void
    {
        $stmt = $this->db->prepare('UPDATE invite_links SET is_active = 0 WHERE link_id = :id');
        $stmt->execute([':id' => $linkId]);
    }

    public function getToken(int $linkId): string
    {
        $stmt = $this->db->prepare('SELECT token FROM invite_links WHERE link_id = :id LIMIT 1');
        $stmt->execute([':id' => $linkId]);
        return (string)($stmt->fetchColumn() ?: '');
    }
}
