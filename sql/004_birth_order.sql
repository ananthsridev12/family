-- Add birth order for sibling ranking

SET @has_birth_order := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'persons'
      AND COLUMN_NAME = 'birth_order'
);
SET @sql := IF(
    @has_birth_order = 0,
    'ALTER TABLE persons ADD COLUMN birth_order SMALLINT NULL AFTER birth_year',
    'SELECT ''skip persons.birth_order'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
