<?php
declare(strict_types=1);

final class PersonController extends BaseController
{
    private PersonModel $people;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->people = new PersonModel($db);
    }

    public function search(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) < 2) {
            $this->json([]);
            return;
        }

        $rows = $this->people->searchByName($q, 10);
        $out = [];
        foreach ($rows as $row) {
            $year = (int)($row['birth_year'] ?? 0);
            $label = $row['full_name'] . ($year > 0 ? ' (' . $year . ')' : '');
            $out[] = [
                'id' => (int)$row['person_id'],
                'name' => $label,
            ];
        }

        $this->json($out);
    }

    public function children(): void
    {
        $personId = (int)($_GET['person_id'] ?? 0);
        if ($personId <= 0) {
            $this->json([]);
            return;
        }

        $rows = $this->people->childrenOf($personId);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row['person_id'],
                'name' => (string)$row['full_name'],
            ];
        }
        $this->json($out);
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
