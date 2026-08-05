SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS invite_links (
    link_id    INT AUTO_INCREMENT PRIMARY KEY,
    token      VARCHAR(64)  NOT NULL,
    created_by INT          NOT NULL,
    label      VARCHAR(255) DEFAULT NULL,
    max_uses   INT          DEFAULT 0,
    used_count INT          DEFAULT 0,
    expires_at TIMESTAMP    NULL DEFAULT NULL,
    is_active  TINYINT(1)   DEFAULT 1,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_token (token),
    INDEX idx_invite_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
