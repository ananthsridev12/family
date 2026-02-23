-- RBAC + ownership enhancements (users + persons)

SET NAMES utf8mb4;

-- MySQL 5.7/8.0 compatibility: add columns only if missing.
SET @has_users_name := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'name'
);
SET @sql := IF(
    @has_users_name = 0,
    'ALTER TABLE users ADD COLUMN name VARCHAR(120) NULL AFTER username',
    'SELECT ''skip users.name'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_users_email := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'email'
);
SET @sql := IF(
    @has_users_email = 0,
    'ALTER TABLE users ADD COLUMN email VARCHAR(160) NULL AFTER name',
    'SELECT ''skip users.email'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_users_is_active := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'is_active'
);
SET @sql := IF(
    @has_users_is_active = 0,
    'ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role',
    'SELECT ''skip users.is_active'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_users_person_id := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'person_id'
);
SET @sql := IF(
    @has_users_person_id = 0,
    'ALTER TABLE users ADD COLUMN person_id INT NULL AFTER is_active',
    'SELECT ''skip users.person_id'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Expand role enum to include new roles, migrate legacy values, then lock down.
ALTER TABLE users MODIFY role ENUM('admin','member','full_editor','limited_member') NOT NULL DEFAULT 'limited_member';
UPDATE users SET role = 'limited_member' WHERE role = 'member';
ALTER TABLE users MODIFY role ENUM('admin','full_editor','limited_member') NOT NULL DEFAULT 'limited_member';

SET @has_email_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'uq_users_email'
);
SET @sql := IF(
    @has_email_idx = 0,
    'ALTER TABLE users ADD UNIQUE INDEX uq_users_email (email)',
    'SELECT ''skip users email index'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_persons_created_by := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'persons'
      AND COLUMN_NAME = 'created_by'
);
SET @sql := IF(
    @has_persons_created_by = 0,
    'ALTER TABLE persons ADD COLUMN created_by INT NULL AFTER branch_id',
    'SELECT ''skip persons.created_by'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_persons_editable_scope := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'persons'
      AND COLUMN_NAME = 'editable_scope'
);
SET @sql := IF(
    @has_persons_editable_scope = 0,
    'ALTER TABLE persons ADD COLUMN editable_scope ENUM(''self_branch'',''full_access'') NOT NULL DEFAULT ''self_branch'' AFTER created_by',
    'SELECT ''skip persons.editable_scope'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_persons_is_locked := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'persons'
      AND COLUMN_NAME = 'is_locked'
);
SET @sql := IF(
    @has_persons_is_locked = 0,
    'ALTER TABLE persons ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER editable_scope',
    'SELECT ''skip persons.is_locked'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_persons_is_deleted := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'persons'
      AND COLUMN_NAME = 'is_deleted'
);
SET @sql := IF(
    @has_persons_is_deleted = 0,
    'ALTER TABLE persons ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER is_locked',
    'SELECT ''skip persons.is_deleted'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_created_fk := (
    SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'persons'
      AND CONSTRAINT_NAME = 'fk_person_created_by'
);
SET @sql := IF(
    @has_created_fk = 0,
    'ALTER TABLE persons ADD CONSTRAINT fk_person_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL',
    'SELECT ''skip created_by fk'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE persons SET is_deleted = 0 WHERE is_deleted IS NULL;

CREATE TABLE IF NOT EXISTS audit_log (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action ENUM('add','edit','delete') NOT NULL,
    person_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    old_value JSON NULL,
    new_value JSON NULL,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_person FOREIGN KEY (person_id) REFERENCES persons(person_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
