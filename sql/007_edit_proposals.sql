SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS person_edit_proposals (
    proposal_id      INT AUTO_INCREMENT PRIMARY KEY,
    person_id        INT          NOT NULL,
    proposed_by      INT          NOT NULL,
    status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    change_summary   VARCHAR(255) DEFAULT NULL,
    proposed_changes JSON         NOT NULL,
    admin_notes      TEXT         DEFAULT NULL,
    reviewed_by      INT          DEFAULT NULL,
    reviewed_at      TIMESTAMP    NULL,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_prop_status      (status),
    INDEX idx_prop_person      (person_id),
    INDEX idx_prop_proposed_by (proposed_by),
    INDEX idx_prop_reviewed_by (reviewed_by)
) ENGINE=InnoDB;
