<?php
declare(strict_types=1);

final class AttachmentModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $personId, string $fileName, string $storedName, string $mimeType, int $fileSize, string $type, int $uploadedBy): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO person_attachments (person_id, file_name, stored_name, mime_type, file_size, attachment_type, uploaded_by)
             VALUES (:person_id, :file_name, :stored_name, :mime_type, :file_size, :type, :uploaded_by)'
        );
        $stmt->execute([
            ':person_id'   => $personId,
            ':file_name'   => $fileName,
            ':stored_name' => $storedName,
            ':mime_type'   => $mimeType,
            ':file_size'   => $fileSize,
            ':type'        => $type,
            ':uploaded_by' => $uploadedBy,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findByPersonId(int $personId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM person_attachments WHERE person_id = :id ORDER BY uploaded_at DESC'
        );
        $stmt->execute([':id' => $personId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM person_attachments WHERE attachment_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM person_attachments WHERE attachment_id = :id');
        $stmt->execute([':id' => $id]);
    }
}
