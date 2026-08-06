SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS person_add_proposals (
    proposal_id  INT AUTO_INCREMENT PRIMARY KEY,
    proposed_by  INT          NOT NULL,
    status       ENUM('pending','approved','rejected') DEFAULT 'pending',
    proposed_data JSON        NOT NULL,
    admin_notes  TEXT         DEFAULT NULL,
    reviewed_by  INT          DEFAULT NULL,
    reviewed_at  TIMESTAMP    NULL DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_addprop_status (status),
    INDEX idx_addprop_user   (proposed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
