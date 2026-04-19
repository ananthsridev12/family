<?php
declare(strict_types=1);

final class NotificationModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $userId, string $type, string $title, string $message, ?int $personId = null, ?string $actionUrl = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notifications (user_id, person_id, notification_type, title, message, action_url)
             VALUES (:user_id, :person_id, :type, :title, :message, :url)'
        );
        $stmt->execute([
            ':user_id'   => $userId,
            ':person_id' => $personId,
            ':type'      => $type,
            ':title'     => $title,
            ':message'   => $message,
            ':url'       => $actionUrl,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findByUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0'
        );
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function markRead(int $notificationId, int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = :id AND user_id = :uid'
        );
        $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :uid AND is_read = 0'
        );
        $stmt->execute([':uid' => $userId]);
    }

    public function todayReminderExists(int $userId, string $type, int $personId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM notifications
             WHERE user_id = :uid AND notification_type = :type AND person_id = :pid
               AND DATE(created_at) = CURDATE()
             LIMIT 1'
        );
        $stmt->execute([':uid' => $userId, ':type' => $type, ':pid' => $personId]);
        return (bool)$stmt->fetchColumn();
    }
}
