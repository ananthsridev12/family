SET NAMES utf8mb4;

ALTER TABLE family_events
    MODIFY COLUMN event_type ENUM(
        'wedding','birth','graduation','reunion','death','other',
        'naming_ceremony','ear_piercing','puberty_ceremony','housewarming','birthday'
    ) NOT NULL DEFAULT 'other';

CREATE TABLE IF NOT EXISTS moi_entries (
    moi_id            INT           AUTO_INCREMENT PRIMARY KEY,
    event_id          INT           NULL,
    event_label       VARCHAR(255)  NOT NULL,
    event_date        DATE          NULL,
    giver_name        VARCHAR(255)  NOT NULL,
    giver_father_name VARCHAR(255)  NULL,
    giver_location    VARCHAR(255)  NULL,
    giver_relation    VARCHAR(120)  NULL,
    amount            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    gift_type         ENUM('cash','cheque','gold','gift','other') NOT NULL DEFAULT 'cash',
    notes             TEXT          NULL,
    recorded_by       INT           NOT NULL,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_moi_event FOREIGN KEY (event_id)    REFERENCES family_events(event_id) ON DELETE SET NULL,
    CONSTRAINT fk_moi_user  FOREIGN KEY (recorded_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
