<?php
declare(strict_types=1);

final class ViewTokenModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function generate(int $personId, int $createdBy, string $label = '', ?string $expiresAt = null): string
    {
        $token = bin2hex(random_bytes(24));
        $stmt = $this->db->prepare(
            'INSERT INTO person_view_tokens (token, person_id, created_by, label, expires_at)
             VALUES (:token, :person_id, :created_by, :label, :expires_at)'
        );
        $stmt->execute([
            ':token'      => $token,
            ':person_id'  => $personId,
            ':created_by' => $createdBy,
            ':label'      => $label !== '' ? $label : null,
            ':expires_at' => $expiresAt,
        ]);
        return $token;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM person_view_tokens WHERE token = :token LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function isValid(array $tokenRow): bool
    {
        if ($tokenRow['expires_at'] !== null && strtotime((string)$tokenRow['expires_at']) < time()) {
            return false;
        }
        return true;
    }

    public function listByPerson(int $personId): array
    {
        $stmt = $this->db->prepare(
            'SELECT vt.*, u.name AS creator_name
             FROM person_view_tokens vt
             LEFT JOIN users u ON u.user_id = vt.created_by
             WHERE vt.person_id = :pid
             ORDER BY vt.created_at DESC'
        );
        $stmt->execute([':pid' => $personId]);
        return $stmt->fetchAll() ?: [];
    }

    public function delete(int $tokenId): void
    {
        $stmt = $this->db->prepare('DELETE FROM person_view_tokens WHERE token_id = :id');
        $stmt->execute([':id' => $tokenId]);
    }
}
