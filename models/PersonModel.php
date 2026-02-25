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

    public function searchByNameWithRelations(string $q, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.person_id, p.full_name, p.birth_year, p.spouse_id,
                    f.full_name AS father_name,
                    m.full_name AS mother_name,
                    s.full_name AS spouse_name
             FROM persons p
             LEFT JOIN persons f ON f.person_id = p.father_id
             LEFT JOIN persons m ON m.person_id = p.mother_id
             LEFT JOIN persons s ON s.person_id = p.spouse_id
             WHERE (p.is_deleted = 0 OR p.is_deleted IS NULL)
               AND p.full_name LIKE :q
             ORDER BY p.full_name ASC
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
                'SELECT DISTINCT p.person_id, p.full_name, p.date_of_birth, p.birth_year, p.birth_order,
                        f.full_name AS father_name,
                        s.full_name AS spouse_name
                 FROM persons p
                 LEFT JOIN persons f ON f.person_id = p.father_id
                 LEFT JOIN persons s ON s.person_id = p.spouse_id
                 WHERE (p.father_id = :father_id OR p.mother_id = :mother_id)
                   AND (p.is_deleted = 0 OR p.is_deleted IS NULL)
                 LIMIT 200'
            );
            $stmt->execute([
                ':father_id' => $personId,
                ':mother_id' => $personId,
            ]);
            $rows = $stmt->fetchAll();
            return $this->sortSiblings($rows);
        }

        if ($this->hasParentChildTable()) {
            $stmt = $this->db->prepare(
                'SELECT DISTINCT p.person_id, p.full_name, p.date_of_birth, p.birth_year, p.birth_order,
                        f.full_name AS father_name,
                        s.full_name AS spouse_name
                 FROM parent_child pc
                 INNER JOIN persons p ON p.person_id = pc.child_id
                 LEFT JOIN persons f ON f.person_id = p.father_id
                 LEFT JOIN persons s ON s.person_id = p.spouse_id
                 WHERE pc.parent_id = :id
                   AND (p.is_deleted = 0 OR p.is_deleted IS NULL)
                 LIMIT 200'
            );
            $stmt->execute([':id' => $personId]);
            $rows = $stmt->fetchAll();
            return $this->sortSiblings($rows);
        }

        return [];
    }

    private function sortSiblings(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }

        $hasDob = true;
        $hasDobOrYear = true;
        $hasYear = true;
        $hasOrder = true;

        foreach ($rows as $row) {
            $dob = trim((string)($row['date_of_birth'] ?? ''));
            $year = (int)($row['birth_year'] ?? 0);
            $order = (int)($row['birth_order'] ?? 0);

            if ($dob === '') {
                $hasDob = false;
            }
            if ($dob === '' && $year <= 0) {
                $hasDobOrYear = false;
            }
            if ($year <= 0) {
                $hasYear = false;
            }
            if ($order <= 0) {
                $hasOrder = false;
            }
        }

        if ($hasDob) {
            usort($rows, static function (array $a, array $b): int {
                return strcmp((string)$a['date_of_birth'], (string)$b['date_of_birth']);
            });
            return $rows;
        }

        if ($hasDobOrYear) {
            usort($rows, static function (array $a, array $b): int {
                $aDob = trim((string)($a['date_of_birth'] ?? ''));
                $bDob = trim((string)($b['date_of_birth'] ?? ''));
                $aKey = $aDob !== '' ? $aDob : sprintf('%04d-01-01', (int)($a['birth_year'] ?? 0));
                $bKey = $bDob !== '' ? $bDob : sprintf('%04d-01-01', (int)($b['birth_year'] ?? 0));
                return strcmp($aKey, $bKey);
            });
            return $rows;
        }

        if ($hasYear) {
            usort($rows, static function (array $a, array $b): int {
                return (int)$a['birth_year'] <=> (int)$b['birth_year'];
            });
            return $rows;
        }

        if ($hasOrder) {
            usort($rows, static function (array $a, array $b): int {
                return (int)$a['birth_order'] <=> (int)$b['birth_order'];
            });
            return $rows;
        }

        usort($rows, static function (array $a, array $b): int {
            return strcasecmp((string)$a['full_name'], (string)$b['full_name']);
        });

        return $rows;
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

    public function allWithRelations(int $limit = 500): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.person_id, p.full_name, p.gender, p.date_of_birth, p.birth_year, p.date_of_death,
                    p.current_location, p.native_location, p.spouse_id, p.father_id, p.mother_id, p.birth_order, p.created_by, p.is_locked,
                    f.full_name AS father_name,
                    m.full_name AS mother_name,
                    s.full_name AS spouse_name
             FROM persons p
             LEFT JOIN persons f ON f.person_id = p.father_id
             LEFT JOIN persons m ON m.person_id = p.mother_id
             LEFT JOIN persons s ON s.person_id = p.spouse_id
             WHERE (p.is_deleted = 0 OR p.is_deleted IS NULL)
             ORDER BY p.full_name ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findWithRelations(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*,
                    f.full_name AS father_name,
                    m.full_name AS mother_name,
                    s.full_name AS spouse_name
             FROM persons p
             LEFT JOIN persons f ON f.person_id = p.father_id
             LEFT JOIN persons m ON m.person_id = p.mother_id
             LEFT JOIN persons s ON s.person_id = p.spouse_id
             WHERE p.person_id = :id
               AND (p.is_deleted = 0 OR p.is_deleted IS NULL)
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
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
                father_id, mother_id, spouse_id, branch_id, birth_order, created_by, editable_scope, is_locked, is_deleted
             ) VALUES (
                :full_name, :gender, :date_of_birth, :birth_year, :date_of_death, :blood_group,
                :occupation, :mobile, :email, :address, :current_location, :native_location, :is_alive,
                :father_id, :mother_id, :spouse_id, :branch_id, :birth_order, :created_by, :editable_scope, :is_locked, :is_deleted
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
                birth_order = :birth_order,
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
