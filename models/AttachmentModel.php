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

    /**
     * For each person id, return the attachment_id of their first (lowest id) photo.
     * Returns [person_id => attachment_id].
     */
    public function findFirstPhotosByPersonIds(array $personIds): array
    {
        $personIds = array_values(array_unique(array_filter($personIds, static fn($id) => (int)$id > 0)));
        if ($personIds === []) return [];
        $ph = implode(',', array_fill(0, count($personIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT pa.person_id, MIN(pa.attachment_id) AS attachment_id
             FROM person_attachments pa
             WHERE pa.person_id IN ($ph)
               AND pa.attachment_type = 'photo'
               AND pa.mime_type IN ('image/jpeg','image/png','image/webp')
             GROUP BY pa.person_id"
        );
        foreach ($personIds as $i => $id) {
            $stmt->bindValue($i + 1, (int)$id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int)$row['person_id']] = (int)$row['attachment_id'];
        }
        return $result;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM person_attachments WHERE attachment_id = :id');
        $stmt->execute([':id' => $id]);
    }
}
