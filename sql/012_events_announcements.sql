SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS family_events (
    event_id    INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    event_type  ENUM('wedding','birth','graduation','reunion','death','other') NOT NULL DEFAULT 'other',
    event_date  DATE NULL,
    description TEXT NULL,
    drive_link  VARCHAR(500) NULL,
    person_ids  TEXT NULL,
    created_by  INT NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255) NOT NULL,
    body            TEXT NOT NULL,
    is_pinned       TINYINT(1) NOT NULL DEFAULT 0,
    created_by      INT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE persons ADD COLUMN obituary TEXT NULL;
