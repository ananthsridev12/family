SET NAMES utf8mb4;

-- Per-user permission settings
ALTER TABLE users ADD COLUMN IF NOT EXISTS permissions JSON DEFAULT NULL;

-- Default permissions for members who join via an invite link
ALTER TABLE invite_links ADD COLUMN IF NOT EXISTS default_permissions JSON DEFAULT NULL;
