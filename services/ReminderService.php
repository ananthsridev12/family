<?php
declare(strict_types=1);

final class ReminderService
{
    private PDO $db;
    private NotificationModel $notifications;

    public function __construct(PDO $db)
    {
        $this->db            = $db;
        $this->notifications = new NotificationModel($db);
    }

    public function generateForUser(int $userId, int $daysAhead = 7): void
    {
        $this->generateBirthdays($userId, $daysAhead);
        $this->generateAnniversaries($userId, $daysAhead);
    }

    private function generateBirthdays(int $userId, int $daysAhead): void
    {
        $stmt = $this->db->prepare(
            'SELECT person_id, full_name, date_of_birth
             FROM persons
             WHERE (is_deleted = 0 OR is_deleted IS NULL)
               AND is_alive = 1
               AND date_of_birth IS NOT NULL
               AND DAYOFYEAR(date_of_birth) BETWEEN DAYOFYEAR(CURDATE()) AND DAYOFYEAR(DATE_ADD(CURDATE(), INTERVAL :days DAY))'
        );
        $stmt->bindValue(':days', $daysAhead, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        foreach ($rows as $row) {
            $personId = (int)$row['person_id'];
            if ($this->notifications->todayReminderExists($userId, 'birthday', $personId)) {
                continue;
            }
            $dob  = new DateTimeImmutable((string)$row['date_of_birth']);
            $today = new DateTimeImmutable('today');
            $next  = $dob->setDate((int)$today->format('Y'), (int)$dob->format('m'), (int)$dob->format('d'));
            if ($next < $today) {
                $next = $next->modify('+1 year');
            }
            $diffDays = (int)$today->diff($next)->days;
            $when = $diffDays === 0 ? 'today' : "in {$diffDays} day" . ($diffDays > 1 ? 's' : '');

            $this->notifications->create(
                $userId,
                'birthday',
                'Birthday: ' . $row['full_name'],
                $row['full_name'] . "'s birthday is {$when}.",
                $personId,
                '/index.php?route=member/person-view&id=' . $personId
            );
        }
    }

    private function generateAnniversaries(int $userId, int $daysAhead): void
    {
        $stmt = $this->db->prepare(
            'SELECT m.marriage_id, m.marriage_date,
                    p1.person_id AS p1_id, p1.full_name AS p1_name,
                    p2.person_id AS p2_id, p2.full_name AS p2_name
             FROM marriages m
             INNER JOIN persons p1 ON p1.person_id = m.person1_id AND (p1.is_deleted = 0 OR p1.is_deleted IS NULL)
             INNER JOIN persons p2 ON p2.person_id = m.person2_id AND (p2.is_deleted = 0 OR p2.is_deleted IS NULL)
             WHERE m.status = \'married\'
               AND m.marriage_date IS NOT NULL
               AND DAYOFYEAR(m.marriage_date) BETWEEN DAYOFYEAR(CURDATE()) AND DAYOFYEAR(DATE_ADD(CURDATE(), INTERVAL :days DAY))'
        );
        $stmt->bindValue(':days', $daysAhead, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        foreach ($rows as $row) {
            $p1Id = (int)$row['p1_id'];
            if ($this->notifications->todayReminderExists($userId, 'anniversary', $p1Id)) {
                continue;
            }
            $md    = new DateTimeImmutable((string)$row['marriage_date']);
            $today = new DateTimeImmutable('today');
            $next  = $md->setDate((int)$today->format('Y'), (int)$md->format('m'), (int)$md->format('d'));
            if ($next < $today) {
                $next = $next->modify('+1 year');
            }
            $diffDays = (int)$today->diff($next)->days;
            $when = $diffDays === 0 ? 'today' : "in {$diffDays} day" . ($diffDays > 1 ? 's' : '');
            $names = $row['p1_name'] . ' & ' . $row['p2_name'];

            $this->notifications->create(
                $userId,
                'anniversary',
                'Anniversary: ' . $names,
                'Wedding anniversary of ' . $names . " is {$when}.",
                $p1Id,
                '/index.php?route=member/person-view&id=' . $p1Id
            );
        }
    }
}
