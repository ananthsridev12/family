<?php
declare(strict_types=1);

final class PersonModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT person_id, full_name, gender, birth_year, father_id, mother_id, spouse_id, branch_id FROM persons WHERE person_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function searchByName(string $q, int $limit = 10): array
    {
        $stmt = $this->db->prepare('SELECT person_id, full_name, birth_year FROM persons WHERE full_name LIKE :q ORDER BY full_name ASC LIMIT :limit');
        $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function childrenOf(int $personId): array
    {
        $stmt = $this->db->prepare('SELECT person_id, full_name FROM persons WHERE father_id = :id OR mother_id = :id ORDER BY full_name ASC LIMIT 100');
        $stmt->execute([':id' => $personId]);
        return $stmt->fetchAll();
    }

    public function branchMembers(int $branchId, int $limit = 200): array
    {
        $stmt = $this->db->prepare('SELECT person_id, full_name, gender, birth_year FROM persons WHERE branch_id = :branch_id ORDER BY full_name ASC LIMIT :limit');
        $stmt->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}