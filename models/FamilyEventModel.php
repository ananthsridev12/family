<?php

class FamilyEventModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(
        int $createdBy,
        string $title,
        string $type,
        ?string $date,
        ?string $desc,
        ?string $driveLink,
        array $personIds
    ): int {
        $sql = '
            INSERT INTO family_events
                (title, event_type, event_date, description, drive_link, person_ids, created_by)
            VALUES
                (:title, :event_type, :event_date, :description, :drive_link, :person_ids, :created_by)
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title'       => $title,
            ':event_type'  => $type,
            ':event_date'  => $date,
            ':description' => $desc,
            ':drive_link'  => $driveLink,
            ':person_ids'  => json_encode($personIds),
            ':created_by'  => $createdBy,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function list(int $limit = 200): array
    {
        $sql = '
            SELECT fe.*,
                   u.name AS creator_name
            FROM family_events fe
            LEFT JOIN users u ON u.user_id = fe.created_by
            ORDER BY ISNULL(fe.event_date) ASC, fe.event_date DESC
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
            SELECT fe.*,
                   u.name AS creator_name
            FROM family_events fe
            LEFT JOIN users u ON u.user_id = fe.created_by
            WHERE fe.event_id = :id
            LIMIT 1
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function update(
        int $id,
        string $title,
        string $type,
        ?string $date,
        ?string $desc,
        ?string $driveLink,
        array $personIds
    ): void {
        $sql = '
            UPDATE family_events
            SET title       = :title,
                event_type  = :event_type,
                event_date  = :event_date,
                description = :description,
                drive_link  = :drive_link,
                person_ids  = :person_ids
            WHERE event_id = :id
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title'       => $title,
            ':event_type'  => $type,
            ':event_date'  => $date,
            ':description' => $desc,
            ':drive_link'  => $driveLink,
            ':person_ids'  => json_encode($personIds),
            ':id'          => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM family_events WHERE event_id = :id');
        $stmt->execute([':id' => $id]);
    }
}
