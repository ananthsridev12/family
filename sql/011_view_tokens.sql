SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS person_view_tokens (
    token_id   INT AUTO_INCREMENT PRIMARY KEY,
    token      VARCHAR(64) NOT NULL UNIQUE,
    person_id  INT NOT NULL,
    created_by INT NOT NULL,
    label      VARCHAR(255) NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS view_correction_requests (
    request_id       INT AUTO_INCREMENT PRIMARY KEY,
    token            VARCHAR(64) NOT NULL,
    person_id        INT NOT NULL,
    requester_name   VARCHAR(255) NULL,
    requester_contact VARCHAR(255) NULL,
    correction_note  TEXT NOT NULL,
    status           ENUM('pending','reviewed') NOT NULL DEFAULT 'pending',
    admin_notes      TEXT NULL,
    reviewed_by      INT NULL,
    reviewed_at      DATETIME NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
