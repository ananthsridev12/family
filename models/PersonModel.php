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
        $stmt = $this->db->prepare('SELECT * FROM persons WHERE person_id = :id');
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

    public function all(int $limit = 500): array
    {
        $stmt = $this->db->prepare(
            'SELECT person_id, full_name, gender, birth_year, current_location, native_location, spouse_id, father_id, mother_id
             FROM persons
             ORDER BY full_name ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO persons (
                full_name, gender, date_of_birth, birth_year, date_of_death, blood_group,
                occupation, mobile, email, address, current_location, native_location, is_alive,
                father_id, mother_id, spouse_id, branch_id
             ) VALUES (
                :full_name, :gender, :date_of_birth, :birth_year, :date_of_death, :blood_group,
                :occupation, :mobile, :email, :address, :current_location, :native_location, :is_alive,
                :father_id, :mother_id, :spouse_id, :branch_id
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
}
