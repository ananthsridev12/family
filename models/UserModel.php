<?php
declare(strict_types=1);

final class UserModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT user_id, username, name, email, password_hash, role, is_active, person_id
             FROM users
             WHERE email = :login_email OR username = :login_username
             LIMIT 1'
        );
        $stmt->execute([
            ':login_email' => $login,
            ':login_username' => $login,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function list(int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            'SELECT user_id, username, name, email, role, is_active, created_at
             FROM users
             ORDER BY user_id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, name, email, password_hash, role, is_active, person_id)
             VALUES (:username, :name, :email, :password_hash, :role, :is_active, :person_id)'
        );
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function updateRoleStatus(int $userId, string $role, bool $isActive): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET role = :role, is_active = :is_active WHERE user_id = :id'
        );
        $stmt->execute([
            ':role' => $role,
            ':is_active' => $isActive ? 1 : 0,
            ':id' => $userId,
        ]);
    }

    public function updatePassword(int $userId, string $hash): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :id');
        $stmt->execute([':hash' => $hash, ':id' => $userId]);
    }
}
