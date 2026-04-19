SET NAMES utf8mb4;

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
