<?php
declare(strict_types=1);

final class BranchModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listWithCounts(): array
    {
        $sql = 'SELECT b.branch_id, b.branch_name, COUNT(p.person_id) AS members
                FROM branches b
                LEFT JOIN persons p ON p.branch_id = b.branch_id
                GROUP BY b.branch_id, b.branch_name
                ORDER BY b.branch_name ASC';
        return $this->db->query($sql)->fetchAll();
    }
}