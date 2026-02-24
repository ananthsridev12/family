<?php
declare(strict_types=1);

final class PersonModel
{
    private PDO $db;
    private ?bool $hasFatherColumn = null;
    private ?bool $hasMotherColumn = null;
    private ?bool $hasParentChildTable = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM persons WHERE person_id = :id AND (is_deleted = 0 OR is_deleted IS NULL)');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function searchByName(string $q, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT person_id, full_name, birth_year
             FROM persons
             WHERE (is_deleted = 0 OR is_deleted IS NULL)
               AND full_name LIKE :q
             ORDER BY full_name ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':q', '%' . $q . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function childrenOf(int $personId): array
    {
        if ($this->supportsParentColumns()) {
            $stmt = $this->db->prepare(
                'SELECT person_id, full_name
                 FROM persons
                 WHERE (father_id = :id OR mother_id = :id)
                   AND (is_deleted = 0 OR is_deleted IS NULL)
                 ORDER BY full_name ASC
                 LIMIT 100'
            );
            $stmt->execute([':id' => $personId]);
            return $stmt->fetchAll();
        }

        if ($this->hasParentChildTable()) {
            $stmt = $this->db->prepare(
                'SELECT p.person_id, p.full_name
                 FROM parent_child pc
                 INNER JOIN persons p ON p.person_id = pc.child_id
                 WHERE pc.parent_id = :id
                   AND (p.is_deleted = 0 OR p.is_deleted IS NULL)
                 ORDER BY p.full_name ASC
                 LIMIT 100'
            );
            $stmt->execute([':id' => $personId]);
            return $stmt->fetchAll();
        }

        return [];
    }

    public function branchMembers(int $branchId, int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            'SELECT person_id, full_name, gender, birth_year
             FROM persons
             WHERE branch_id = :branch_id
               AND (is_deleted = 0 OR is_deleted IS NULL)
             ORDER BY full_name ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function all(int $limit = 500): array
    {
        $stmt = $this->db->prepare(
            'SELECT person_id, full_name, gender, date_of_birth, birth_year, date_of_death, current_location, native_location, spouse_id, father_id, mother_id, created_by, is_locked
             FROM persons
             WHERE (is_deleted = 0 OR is_deleted IS NULL)
             ORDER BY full_name ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn($id): bool => (int)$id > 0)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            'SELECT person_id, full_name
             FROM persons
             WHERE person_id IN (' . $placeholders . ')
               AND (is_deleted = 0 OR is_deleted IS NULL)'
        );
        foreach ($ids as $i => $id) {
            $stmt->bindValue($i + 1, (int)$id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO persons (
                full_name, gender, date_of_birth, birth_year, date_of_death, blood_group,
                occupation, mobile, email, address, current_location, native_location, is_alive,
                father_id, mother_id, spouse_id, branch_id, created_by, editable_scope, is_locked, is_deleted
             ) VALUES (
                :full_name, :gender, :date_of_birth, :birth_year, :date_of_death, :blood_group,
                :occupation, :mobile, :email, :address, :current_location, :native_location, :is_alive,
                :father_id, :mother_id, :spouse_id, :branch_id, :created_by, :editable_scope, :is_locked, :is_deleted
             )'
        );
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data[':id'] = $id;
        $stmt = $this->db->prepare(
            'UPDATE persons SET
                full_name = :full_name,
                gender = :gender,
                date_of_birth = :date_of_birth,
                birth_year = :birth_year,
                date_of_death = :date_of_death,
                blood_group = :blood_group,
                occupation = :occupation,
                mobile = :mobile,
                email = :email,
                address = :address,
                current_location = :current_location,
                native_location = :native_location,
                is_alive = :is_alive
             WHERE person_id = :id'
        );
        $stmt->execute($data);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE persons SET is_deleted = 1 WHERE person_id = :id');
        $stmt->execute([':id' => $id]);
    }

    private function supportsParentColumns(): bool
    {
        if ($this->hasFatherColumn !== null && $this->hasMotherColumn !== null) {
            return $this->hasFatherColumn && $this->hasMotherColumn;
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT COLUMN_NAME
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table
                   AND COLUMN_NAME IN (\'father_id\', \'mother_id\')'
            );
            $stmt->execute([':table' => 'persons']);
            $found = array_map(static fn(array $r): string => (string)$r['COLUMN_NAME'], $stmt->fetchAll());
            $this->hasFatherColumn = in_array('father_id', $found, true);
            $this->hasMotherColumn = in_array('mother_id', $found, true);
        } catch (Throwable) {
            $this->hasFatherColumn = false;
            $this->hasMotherColumn = false;
        }
        return $this->hasFatherColumn && $this->hasMotherColumn;
    }

    private function hasParentChildTable(): bool
    {
        if ($this->hasParentChildTable !== null) {
            return $this->hasParentChildTable;
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table'
            );
            $stmt->execute([':table' => 'parent_child']);
            $this->hasParentChildTable = ((int)$stmt->fetchColumn() > 0);
        } catch (Throwable) {
            $this->hasParentChildTable = false;
        }
        return $this->hasParentChildTable;
    }
}
