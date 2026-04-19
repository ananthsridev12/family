SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS notifications (
    notification_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT          NOT NULL,
    person_id         INT          DEFAULT NULL,
    notification_type ENUM('birthday','anniversary','proposal_approved','proposal_rejected','custom') NOT NULL DEFAULT 'custom',
    title             VARCHAR(255) NOT NULL,
    message           TEXT         DEFAULT NULL,
    action_url        VARCHAR(500) DEFAULT NULL,
    is_read           TINYINT(1)   NOT NULL DEFAULT 0,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at           TIMESTAMP    NULL,
    INDEX idx_notif_user_unread (user_id, is_read),
    INDEX idx_notif_person (person_id)
) ENGINE=InnoDB;

ALTER TABLE notifications
    ADD CONSTRAINT fk_notif_user   FOREIGN KEY (user_id)   REFERENCES users(user_id)     ON DELETE CASCADE;

ALTER TABLE notifications
    ADD CONSTRAINT fk_notif_person FOREIGN KEY (person_id) REFERENCES persons(person_id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
