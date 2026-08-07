<?php

class AnnouncementModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $createdBy, string $title, string $body, bool $pinned): int
    {
        $sql = '
            INSERT INTO announcements
                (title, body, is_pinned, created_by)
            VALUES
                (:title, :body, :is_pinned, :created_by)
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title'      => $title,
            ':body'       => $body,
            ':is_pinned'  => $pinned ? 1 : 0,
            ':created_by' => $createdBy,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function list(int $limit = 50): array
    {
        $sql = '
            SELECT a.*,
                   u.name AS creator_name
            FROM announcements a
            LEFT JOIN users u ON u.user_id = a.created_by
            ORDER BY a.is_pinned DESC, a.created_at DESC
            LIMIT :lim
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = '
            SELECT a.*,
                   u.name AS creator_name
            FROM announcements a
            LEFT JOIN users u ON u.user_id = a.created_by
            WHERE a.announcement_id = :id
            LIMIT 1
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM announcements WHERE announcement_id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function togglePin(int $id): void
    {
        $sql = '
            UPDATE announcements
            SET is_pinned = IF(is_pinned = 1, 0, 1)
            WHERE announcement_id = :id
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}
