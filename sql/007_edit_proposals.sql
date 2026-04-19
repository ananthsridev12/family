SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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
    INDEX idx_prop_status (status),
    INDEX idx_prop_person (person_id),
    INDEX idx_prop_proposed_by (proposed_by),
    INDEX idx_prop_reviewed_by (reviewed_by)
) ENGINE=InnoDB;

ALTER TABLE person_edit_proposals
    ADD CONSTRAINT fk_prop_person      FOREIGN KEY (person_id)   REFERENCES persons(person_id) ON DELETE CASCADE;

ALTER TABLE person_edit_proposals
    ADD CONSTRAINT fk_prop_proposed_by FOREIGN KEY (proposed_by) REFERENCES users(user_id)     ON DELETE CASCADE;

ALTER TABLE person_edit_proposals
    ADD CONSTRAINT fk_prop_reviewed_by FOREIGN KEY (reviewed_by) REFERENCES users(user_id)     ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
