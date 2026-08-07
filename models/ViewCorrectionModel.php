<?php
declare(strict_types=1);

final class ViewCorrectionModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(string $token, int $personId, string $requesterName, string $requesterContact, string $note): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO view_correction_requests (token, person_id, requester_name, requester_contact, correction_note)
             VALUES (:token, :person_id, :name, :contact, :note)'
        );
        $stmt->execute([
            ':token'   => $token,
            ':person_id' => $personId,
            ':name'    => $requesterName !== '' ? $requesterName : null,
            ':contact' => $requesterContact !== '' ? $requesterContact : null,
            ':note'    => $note,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findPending(): array
    {
        $stmt = $this->db->prepare(
            "SELECT cr.*, p.full_name AS person_name
             FROM view_correction_requests cr
             LEFT JOIN persons p ON p.person_id = cr.person_id
             WHERE cr.status = 'pending'
             ORDER BY cr.created_at ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function countPending(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM view_correction_requests WHERE status = 'pending'"
        );
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT cr.*, p.full_name AS person_name
             FROM view_correction_requests cr
             LEFT JOIN persons p ON p.person_id = cr.person_id
             WHERE cr.request_id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markReviewed(int $id, int $reviewedBy, string $notes = ''): void
    {
        $stmt = $this->db->prepare(
            "UPDATE view_correction_requests
             SET status = 'reviewed', reviewed_by = :reviewed_by, reviewed_at = NOW(), admin_notes = :notes
             WHERE request_id = :id"
        );
        $stmt->execute([
            ':reviewed_by' => $reviewedBy,
            ':notes'       => $notes !== '' ? $notes : null,
            ':id'          => $id,
        ]);
    }
}
