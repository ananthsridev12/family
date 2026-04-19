<?php
declare(strict_types=1);

final class PersonController extends BaseController
{
    private PersonModel $people;
    private AttachmentModel $attachments;

    private const UPLOAD_BASE  = __DIR__ . '/../uploads/persons';
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    private const MAX_BYTES    = 5 * 1024 * 1024;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->people      = new PersonModel($db);
        $this->attachments = new AttachmentModel($db);
    }

    public function search(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) < 2) {
            $this->json([]);
            return;
        }

        $rows = $this->people->searchByNameWithRelations($q, 10);
        $out = [];
        foreach ($rows as $row) {
            $year = (int)($row['birth_year'] ?? 0);
            $label = $row['full_name'] . ($year > 0 ? ' (' . $year . ')' : '');
            $spouseName = trim((string)($row['spouse_name'] ?? ''));
            $fatherName = trim((string)($row['father_name'] ?? ''));
            if ($spouseName !== '') {
                $label .= ' — Spouse: ' . $spouseName;
            } elseif ($fatherName !== '') {
                $label .= ' — Father: ' . $fatherName;
            }
            $out[] = [
                'id' => (int)$row['person_id'],
                'name' => $label,
                'person_id' => (int)$row['person_id'],
                'full_name' => (string)$row['full_name'],
                'display_name' => $label,
                'father_name' => (string)($row['father_name'] ?? ''),
                'mother_name' => (string)($row['mother_name'] ?? ''),
                'spouse_name' => (string)($row['spouse_name'] ?? ''),
            ];
        }

        $this->json($out);
    }

    public function checkDuplicate(): void
    {
        $name = trim((string)($_GET['name'] ?? ''));
        $birthYear = (int)($_GET['birth_year'] ?? 0);
        $gender = (string)($_GET['gender'] ?? '');

        if (mb_strlen($name) < 2) {
            $this->json([]);
            return;
        }

        $rows = $this->people->findPotentialDuplicates(
            $name,
            $birthYear > 0 ? $birthYear : null,
            in_array($gender, ['male', 'female', 'other', 'unknown'], true) ? $gender : null
        );

        $out = [];
        foreach ($rows as $row) {
            $year = (int)($row['birth_year'] ?? 0);
            $label = $row['full_name'] . ($year > 0 ? ' (' . $year . ')' : '');
            $fatherName = trim((string)($row['father_name'] ?? ''));
            $spouseName = trim((string)($row['spouse_name'] ?? ''));
            if ($fatherName !== '') {
                $label .= ' — Father: ' . $fatherName;
            } elseif ($spouseName !== '') {
                $label .= ' — Spouse: ' . $spouseName;
            }
            $out[] = [
                'person_id' => (int)$row['person_id'],
                'full_name' => (string)$row['full_name'],
                'birth_year' => $year,
                'gender' => (string)($row['gender'] ?? ''),
                'label' => $label,
                'match_score' => (int)$row['match_score'],
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
            $fatherName = trim((string)($row['father_name'] ?? ''));
            $spouseName = trim((string)($row['spouse_name'] ?? ''));
            $label = (string)$row['full_name'];
            if ($spouseName !== '') {
                $label .= ' — Spouse: ' . $spouseName;
            } elseif ($fatherName !== '') {
                $label .= ' — Father: ' . $fatherName;
            }
            $out[] = [
                'id' => (int)$row['person_id'],
                'name' => $label,
            ];
        }
        $this->json($out);
    }

    public function uploadAttachment(): void
    {
        require_auth();
        $personId = (int)($_POST['person_id'] ?? 0);
        if ($personId <= 0 || !verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(400);
            $this->json(['error' => 'Invalid request.']);
        }

        $person = $this->people->findById($personId);
        if ($person === null) {
            http_response_code(404);
            $this->json(['error' => 'Person not found.']);
        }

        $file = $_FILES['attachment'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->json(['error' => 'No file uploaded or upload error.']);
        }

        $mimeType = (string)mime_content_type((string)($file['tmp_name'] ?? ''));
        if (!in_array($mimeType, self::ALLOWED_MIME, true)) {
            $this->json(['error' => 'File type not allowed. Use JPEG, PNG, WebP or PDF.']);
        }
        if ((int)($file['size'] ?? 0) > self::MAX_BYTES) {
            $this->json(['error' => 'File exceeds 5 MB limit.']);
        }

        $ext = match ($mimeType) {
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'application/pdf' => 'pdf',
            default           => 'bin',
        };
        $attType    = $mimeType === 'application/pdf' ? 'document' : 'photo';
        $storedName = uniqid('att_', true) . '.' . $ext;
        $dir        = self::UPLOAD_BASE . '/' . $personId;
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $destPath = $dir . '/' . $storedName;
        if (!move_uploaded_file((string)($file['tmp_name'] ?? ''), $destPath)) {
            $this->json(['error' => 'Failed to save file.']);
        }

        $userId = (int)(app_user()['user_id'] ?? 0);
        $id = $this->attachments->create(
            $personId,
            basename((string)($file['name'] ?? 'file')),
            $storedName,
            $mimeType,
            (int)($file['size'] ?? 0),
            $attType,
            $userId
        );

        $this->json(['ok' => true, 'attachment_id' => $id]);
    }

    public function deleteAttachment(): void
    {
        require_auth();
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403);
            $this->json(['error' => 'Forbidden.']);
        }

        $id   = (int)($_POST['attachment_id'] ?? 0);
        $role = app_user_role();
        $row  = $this->attachments->findById($id);
        if ($row === null) {
            $this->json(['error' => 'Not found.']);
        }

        $userId = (int)(app_user()['user_id'] ?? 0);
        if ($role !== 'admin' && (int)$row['uploaded_by'] !== $userId) {
            http_response_code(403);
            $this->json(['error' => 'Permission denied.']);
        }

        $filePath = self::UPLOAD_BASE . '/' . (int)$row['person_id'] . '/' . $row['stored_name'];
        if (is_file($filePath)) {
            unlink($filePath);
        }
        $this->attachments->delete($id);
        $this->json(['ok' => true]);
    }

    public function serveAttachment(): void
    {
        require_auth();
        $id  = (int)($_GET['id'] ?? 0);
        $row = $this->attachments->findById($id);
        if ($row === null) {
            http_response_code(404);
            echo 'Not found.';
            exit;
        }

        $filePath = self::UPLOAD_BASE . '/' . (int)$row['person_id'] . '/' . $row['stored_name'];
        if (!is_file($filePath)) {
            http_response_code(404);
            echo 'File missing.';
            exit;
        }

        $mime = (string)($row['mime_type'] ?? 'application/octet-stream');
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: inline; filename="' . addslashes(basename((string)$row['file_name'])) . '"');
        readfile($filePath);
        exit;
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
