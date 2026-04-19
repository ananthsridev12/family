CREATE TABLE IF NOT EXISTS person_attachments (
    attachment_id  INT AUTO_INCREMENT PRIMARY KEY,
    person_id      INT          NOT NULL,
    file_name      VARCHAR(255) NOT NULL,
    stored_name    VARCHAR(255) NOT NULL,
    mime_type      VARCHAR(100) DEFAULT NULL,
    file_size      INT          DEFAULT NULL,
    attachment_type ENUM('photo','document','certificate','other') DEFAULT 'photo',
    uploaded_by    INT          DEFAULT NULL,
    uploaded_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attach_person FOREIGN KEY (person_id)    REFERENCES persons(person_id) ON DELETE CASCADE,
    CONSTRAINT fk_attach_user   FOREIGN KEY (uploaded_by)  REFERENCES users(user_id)     ON DELETE SET NULL,
    INDEX idx_attach_person (person_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
