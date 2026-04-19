SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS person_attachments (
    attachment_id   INT AUTO_INCREMENT PRIMARY KEY,
    person_id       INT          NOT NULL,
    file_name       VARCHAR(255) NOT NULL,
    stored_name     VARCHAR(255) NOT NULL,
    mime_type       VARCHAR(100) DEFAULT NULL,
    file_size       INT          DEFAULT NULL,
    attachment_type ENUM('photo','document','certificate','other') DEFAULT 'photo',
    uploaded_by     INT          DEFAULT NULL,
    uploaded_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attach_person (person_id),
    INDEX idx_attach_user   (uploaded_by)
) ENGINE=InnoDB;

-- Add FK constraints separately so they can be skipped if they already exist
ALTER TABLE person_attachments
    ADD CONSTRAINT fk_attach_person FOREIGN KEY (person_id)  REFERENCES persons(person_id) ON DELETE CASCADE;

ALTER TABLE person_attachments
    ADD CONSTRAINT fk_attach_user   FOREIGN KEY (uploaded_by) REFERENCES users(user_id)    ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
