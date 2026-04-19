<?php
declare(strict_types=1);

final class EditProposalModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $personId, int $proposedBy, array $proposedChanges, string $summary): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO person_edit_proposals (person_id, proposed_by, proposed_changes, change_summary)
             VALUES (:person_id, :proposed_by, :changes, :summary)'
        );
        $stmt->execute([
            ':person_id'   => $personId,
            ':proposed_by' => $proposedBy,
            ':changes'     => json_encode($proposedChanges, JSON_UNESCAPED_UNICODE),
            ':summary'     => $summary,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findPending(): array
    {
        $stmt = $this->db->prepare(
            'SELECT ep.*,
                    p.full_name AS person_name,
                    u.name AS proposer_name
             FROM person_edit_proposals ep
             INNER JOIN persons p ON p.person_id = ep.person_id
             INNER JOIN users   u ON u.user_id   = ep.proposed_by
             WHERE ep.status = \'pending\'
             ORDER BY ep.created_at ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function countPending(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM person_edit_proposals WHERE status = 'pending'");
        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ep.*,
                    p.full_name AS person_name,
                    u.name AS proposer_name
             FROM person_edit_proposals ep
             INNER JOIN persons p ON p.person_id = ep.person_id
             INNER JOIN users   u ON u.user_id   = ep.proposed_by
             WHERE ep.proposal_id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findPendingByPersonAndUser(int $personId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM person_edit_proposals
             WHERE person_id = :pid AND proposed_by = :uid AND status = \'pending\'
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([':pid' => $personId, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function approve(int $id, int $reviewedBy): void
    {
        $stmt = $this->db->prepare(
            'UPDATE person_edit_proposals
             SET status = \'approved\', reviewed_by = :reviewed_by, reviewed_at = NOW()
             WHERE proposal_id = :id'
        );
        $stmt->execute([':id' => $id, ':reviewed_by' => $reviewedBy]);
    }

    public function reject(int $id, int $reviewedBy, string $notes): void
    {
        $stmt = $this->db->prepare(
            'UPDATE person_edit_proposals
             SET status = \'rejected\', reviewed_by = :reviewed_by, reviewed_at = NOW(), admin_notes = :notes
             WHERE proposal_id = :id'
        );
        $stmt->execute([':id' => $id, ':reviewed_by' => $reviewedBy, ':notes' => $notes]);
    }
}
