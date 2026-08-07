<?php
declare(strict_types=1);

final class ViewController
{
    private PDO $db;
    private PersonModel $people;
    private ViewTokenModel $tokens;
    private ViewCorrectionModel $corrections;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->people = new PersonModel($db);
        $this->tokens = new ViewTokenModel($db);
        $this->corrections = new ViewCorrectionModel($db);
    }

    public function show(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        if ($token === '') {
            $this->renderInvalid('No token provided.');
            return;
        }

        $tokenRow = null;
        try { $tokenRow = $this->tokens->findByToken($token); } catch (Throwable $e) {}

        if ($tokenRow === null || !$this->tokens->isValid($tokenRow)) {
            $this->renderInvalid('This link has expired or is no longer valid.');
            return;
        }

        $personId = (int)$tokenRow['person_id'];
        $person = null;
        try { $person = $this->people->findWithRelations($personId); } catch (Throwable $e) {}

        if ($person === null) {
            $this->renderInvalid('Person not found.');
            return;
        }

        // Fetch full details for each immediate family member
        $father = null;
        $mother = null;
        $spouse = null;
        try {
            if ((int)($person['father_id'] ?? 0) > 0) {
                $father = $this->people->findById((int)$person['father_id']);
            }
        } catch (Throwable $e) {}
        try {
            if ((int)($person['mother_id'] ?? 0) > 0) {
                $mother = $this->people->findById((int)$person['mother_id']);
            }
        } catch (Throwable $e) {}
        try {
            if ((int)($person['spouse_id'] ?? 0) > 0) {
                $spouse = $this->people->findById((int)$person['spouse_id']);
            }
        } catch (Throwable $e) {}

        // Children with full details
        $children = [];
        try {
            $basicChildren = $this->people->childrenOf($personId);
            foreach ($basicChildren as $c) {
                $full = null;
                try { $full = $this->people->findById((int)$c['person_id']); } catch (Throwable $e) {}
                $children[] = $full ?? $c;
            }
        } catch (Throwable $e) {}

        // Siblings with full details
        $siblings = [];
        try {
            $fatherId = (int)($person['father_id'] ?? 0);
            $motherId = (int)($person['mother_id'] ?? 0);
            if ($fatherId > 0 || $motherId > 0) {
                $basicSiblings = $this->fetchSiblings($personId, $fatherId, $motherId);
                foreach ($basicSiblings as $s) {
                    $full = null;
                    try { $full = $this->people->findById((int)$s['person_id']); } catch (Throwable $e) {}
                    $siblings[] = $full ?? $s;
                }
            }
        } catch (Throwable $e) {}

        $flash = $_SESSION['view_flash'] ?? null;
        unset($_SESSION['view_flash']);

        include __DIR__ . '/../views/view/profile.php';
    }

    public function requestCorrection(): void
    {
        $token    = trim((string)($_POST['token'] ?? ''));
        $personId = (int)($_POST['person_id'] ?? 0);
        $name     = trim((string)($_POST['requester_name'] ?? ''));
        $contact  = trim((string)($_POST['requester_contact'] ?? ''));
        $note     = trim((string)($_POST['correction_note'] ?? ''));

        $redirect = '/index.php?route=view&token=' . urlencode($token);

        if ($token === '' || $personId === 0 || $note === '') {
            $_SESSION['view_flash'] = ['type' => 'error', 'msg' => 'Please describe the correction before submitting.'];
            header('Location: ' . $redirect);
            exit;
        }

        // Only validate the token is valid — person_id can be any family member on the page
        $tokenRow = null;
        try { $tokenRow = $this->tokens->findByToken($token); } catch (Throwable $e) {}

        if ($tokenRow === null || !$this->tokens->isValid($tokenRow)) {
            $_SESSION['view_flash'] = ['type' => 'error', 'msg' => 'Invalid or expired link.'];
            header('Location: ' . $redirect);
            exit;
        }

        try {
            $this->corrections->create($token, $personId, $name, $contact, $note);
            $this->notifyAdmins($personId, $name ?: 'Anonymous');
        } catch (Throwable $e) {
            $_SESSION['view_flash'] = ['type' => 'error', 'msg' => 'Could not submit. Please try again.'];
            header('Location: ' . $redirect);
            exit;
        }

        $_SESSION['view_flash'] = ['type' => 'success', 'msg' => 'Correction request submitted. The admin will review and update the details.'];
        header('Location: ' . $redirect);
        exit;
    }

    private function fetchSiblings(int $personId, int $fatherId, int $motherId): array
    {
        $conditions = [];
        $params = [':pid' => $personId];
        if ($fatherId > 0) {
            $conditions[] = 'father_id = :fid';
            $params[':fid'] = $fatherId;
        }
        if ($motherId > 0) {
            $conditions[] = 'mother_id = :mid';
            $params[':mid'] = $motherId;
        }
        $where = implode(' OR ', $conditions);
        $stmt = $this->db->prepare(
            "SELECT person_id, full_name, gender, birth_year, birth_order
             FROM persons
             WHERE ($where) AND person_id != :pid AND (is_deleted = 0 OR is_deleted IS NULL)
             ORDER BY birth_order ASC, birth_year ASC, full_name ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function notifyAdmins(int $personId, string $requesterName): void
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO notifications (user_id, person_id, notification_type, title, message, action_url)
                 SELECT user_id, :person_id, 'view_correction', 'Correction Request', :message, :url
                 FROM users WHERE role = 'admin' AND is_active = 1"
            );
            $stmt->execute([
                ':person_id' => $personId,
                ':message'   => $requesterName . ' submitted a correction request via a view link.',
                ':url'       => '/index.php?route=admin/view-corrections',
            ]);
        } catch (Throwable $e) {}
    }

    private function renderInvalid(string $reason): void
    {
        http_response_code(410);
        $message = $reason;
        include __DIR__ . '/../views/view/invalid.php';
    }
}
