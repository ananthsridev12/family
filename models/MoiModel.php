<?php
declare(strict_types=1);

class MoiModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO moi_entries
                (event_id, event_label, event_date, giver_name, giver_father_name,
                 giver_location, amount, gift_type, notes, recorded_by)
            VALUES
                (:event_id, :event_label, :event_date, :giver_name, :giver_father_name,
                 :giver_location, :amount, :gift_type, :notes, :recorded_by)
        ');
        $stmt->execute([
            ':event_id'          => $data['event_id'] ?? null,
            ':event_label'       => $data['event_label'],
            ':event_date'        => $data['event_date'] ?? null,
            ':giver_name'        => $data['giver_name'],
            ':giver_father_name' => $data['giver_father_name'] ?? null,
            ':giver_location'    => $data['giver_location'] ?? null,
            ':amount'            => $data['amount'],
            ':gift_type'         => $data['gift_type'] ?? 'cash',
            ':notes'             => $data['notes'] ?? null,
            ':recorded_by'       => $data['recorded_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function byEvent(int $eventId): array
    {
        $stmt = $this->db->prepare('
            SELECT me.*, u.name AS recorder_name
            FROM moi_entries me
            LEFT JOIN users u ON u.user_id = me.recorded_by
            WHERE me.event_id = :event_id
            ORDER BY me.created_at DESC
        ');
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function totalByEvent(int $eventId): float
    {
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(amount), 0) AS total
            FROM moi_entries
            WHERE event_id = :event_id
        ');
        $stmt->execute([':event_id' => $eventId]);
        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT me.*, u.name AS recorder_name,
                   fe.title AS event_title
            FROM moi_entries me
            LEFT JOIN users u ON u.user_id = me.recorded_by
            LEFT JOIN family_events fe ON fe.event_id = me.event_id
            WHERE me.moi_id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM moi_entries WHERE moi_id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function summaryByEvent(): array
    {
        $stmt = $this->db->query('
            SELECT
                me.event_id,
                me.event_label,
                me.event_date,
                COUNT(me.moi_id)    AS entry_count,
                SUM(me.amount)      AS total_amount
            FROM moi_entries me
            GROUP BY me.event_id, me.event_label, me.event_date
            ORDER BY me.event_date DESC, me.event_label ASC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchLocations(string $q, int $limit = 15): array
    {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT giver_location FROM moi_entries
             WHERE giver_location IS NOT NULL AND giver_location LIKE :q
             ORDER BY giver_location ASC LIMIT :lim'
        );
        $stmt->bindValue(':q', '%' . $q . '%');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function distinctGiverNames(int $limit = 300): array
    {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT giver_name FROM moi_entries ORDER BY giver_name ASC LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function listAll(int $limit = 500): array
    {
        $stmt = $this->db->prepare('
            SELECT me.*, u.name AS recorder_name, fe.title AS event_title
            FROM moi_entries me
            LEFT JOIN users u ON u.user_id = me.recorded_by
            LEFT JOIN family_events fe ON fe.event_id = me.event_id
            ORDER BY me.event_date DESC, me.created_at DESC
            LIMIT :lim
        ');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
