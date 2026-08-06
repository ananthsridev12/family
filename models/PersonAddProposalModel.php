<?php
declare(strict_types=1);

final class PersonAddProposalModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $proposedBy, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO person_add_proposals (proposed_by, proposed_data)
             VALUES (:proposed_by, :proposed_data)'
        );
        $stmt->execute([
            ':proposed_by'   => $proposedBy,
            ':proposed_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findPending(int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, u.name AS proposer_name
             FROM person_add_proposals p
             LEFT JOIN users u ON u.user_id = p.proposed_by
             WHERE p.status = "pending"
             ORDER BY p.created_at ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function countPending(): int
    {
        return (int)$this->db->query(
            'SELECT COUNT(*) FROM person_add_proposals WHERE status = "pending"'
        )->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, u.name AS proposer_name, r.name AS reviewer_name
             FROM person_add_proposals p
             LEFT JOIN users u ON u.user_id = p.proposed_by
             LEFT JOIN users r ON r.user_id = p.reviewed_by
             WHERE p.proposal_id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findPendingByUser(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM person_add_proposals
             WHERE proposed_by = :uid AND status = "pending"
             ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function approve(int $proposalId, int $reviewedBy, string $notes): void
    {
        $stmt = $this->db->prepare(
            'UPDATE person_add_proposals
             SET status = "approved", reviewed_by = :rb, reviewed_at = NOW(), admin_notes = :notes
             WHERE proposal_id = :id'
        );
        $stmt->execute([':rb' => $reviewedBy, ':notes' => $notes, ':id' => $proposalId]);
    }

    public function reject(int $proposalId, int $reviewedBy, string $notes): void
    {
        $stmt = $this->db->prepare(
            'UPDATE person_add_proposals
             SET status = "rejected", reviewed_by = :rb, reviewed_at = NOW(), admin_notes = :notes
             WHERE proposal_id = :id'
        );
        $stmt->execute([':rb' => $reviewedBy, ':notes' => $notes, ':id' => $proposalId]);
    }
}
