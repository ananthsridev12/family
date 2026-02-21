-- Person schema for strict relationship engine inputs

CREATE TABLE IF NOT EXISTS persons (
    person_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(160) NOT NULL,
    gender ENUM('male','female','other','unknown') NOT NULL DEFAULT 'unknown',
    date_of_birth DATE NULL,
    birth_year SMALLINT NULL,
    date_of_death DATE NULL,
    blood_group VARCHAR(10) NULL,
    occupation VARCHAR(120) NULL,
    mobile VARCHAR(40) NULL,
    email VARCHAR(160) NULL,
    address VARCHAR(255) NULL,
    current_location VARCHAR(160) NULL,
    native_location VARCHAR(160) NULL,
    is_alive TINYINT(1) NOT NULL DEFAULT 1,
    father_id INT NULL,
    mother_id INT NULL,
    spouse_id INT NULL,
    branch_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_person_father FOREIGN KEY (father_id) REFERENCES persons(person_id) ON DELETE SET NULL,
    CONSTRAINT fk_person_mother FOREIGN KEY (mother_id) REFERENCES persons(person_id) ON DELETE SET NULL,
    CONSTRAINT fk_person_spouse FOREIGN KEY (spouse_id) REFERENCES persons(person_id) ON DELETE SET NULL,
    CONSTRAINT fk_person_branch FOREIGN KEY (branch_id) REFERENCES branches(branch_id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE persons ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL AFTER gender;
ALTER TABLE persons ADD COLUMN IF NOT EXISTS date_of_death DATE NULL AFTER birth_year;
ALTER TABLE persons ADD COLUMN IF NOT EXISTS blood_group VARCHAR(10) NULL AFTER date_of_death;
ALTER TABLE persons ADD COLUMN IF NOT EXISTS occupation VARCHAR(120) NULL AFTER blood_group;
ALTER TABLE persons ADD COLUMN IF NOT EXISTS mobile VARCHAR(40) NULL AFTER occupation;
ALTER TABLE persons ADD COLUMN IF NOT EXISTS email VARCHAR(160) NULL AFTER mobile;
ALTER TABLE persons ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL AFTER email;
ALTER TABLE persons ADD COLUMN IF NOT EXISTS current_location VARCHAR(160) NULL AFTER address;
ALTER TABLE persons ADD COLUMN IF NOT EXISTS native_location VARCHAR(160) NULL AFTER current_location;
ALTER TABLE persons ADD COLUMN IF NOT EXISTS is_alive TINYINT(1) NOT NULL DEFAULT 1 AFTER native_location;

CREATE TABLE IF NOT EXISTS marriages (
    marriage_id INT AUTO_INCREMENT PRIMARY KEY,
    person1_id INT NOT NULL,
    person2_id INT NOT NULL,
    marriage_date DATE NULL,
    divorce_date DATE NULL,
    status ENUM('married','divorced','widowed') NOT NULL DEFAULT 'married',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_marriage_person1 FOREIGN KEY (person1_id) REFERENCES persons(person_id) ON DELETE CASCADE,
    CONSTRAINT fk_marriage_person2 FOREIGN KEY (person2_id) REFERENCES persons(person_id) ON DELETE CASCADE,
    CONSTRAINT uq_marriage_pair UNIQUE (person1_id, person2_id)
) ENGINE=InnoDB;

DELIMITER $$

DROP TRIGGER IF EXISTS trg_person_self_ref_insert$$
CREATE TRIGGER trg_person_self_ref_insert
BEFORE INSERT ON persons
FOR EACH ROW
BEGIN
    IF NEW.father_id IS NOT NULL AND NEW.father_id = NEW.person_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'father_id cannot equal person_id';
    END IF;
    IF NEW.mother_id IS NOT NULL AND NEW.mother_id = NEW.person_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'mother_id cannot equal person_id';
    END IF;
    IF NEW.spouse_id IS NOT NULL AND NEW.spouse_id = NEW.person_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'spouse_id cannot equal person_id';
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_person_self_ref_update$$
CREATE TRIGGER trg_person_self_ref_update
BEFORE UPDATE ON persons
FOR EACH ROW
BEGIN
    IF NEW.father_id IS NOT NULL AND NEW.father_id = NEW.person_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'father_id cannot equal person_id';
    END IF;
    IF NEW.mother_id IS NOT NULL AND NEW.mother_id = NEW.person_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'mother_id cannot equal person_id';
    END IF;
    IF NEW.spouse_id IS NOT NULL AND NEW.spouse_id = NEW.person_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'spouse_id cannot equal person_id';
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_person_spouse_insert$$
CREATE TRIGGER trg_person_spouse_insert
AFTER INSERT ON persons
FOR EACH ROW
BEGIN
    IF NEW.spouse_id IS NOT NULL THEN
        UPDATE persons
        SET spouse_id = NEW.person_id
        WHERE person_id = NEW.spouse_id
          AND (spouse_id IS NULL OR spouse_id <> NEW.person_id);
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_person_spouse_update$$
CREATE TRIGGER trg_person_spouse_update
AFTER UPDATE ON persons
FOR EACH ROW
BEGIN
    IF NEW.spouse_id IS NOT NULL THEN
        UPDATE persons
        SET spouse_id = NEW.person_id
        WHERE person_id = NEW.spouse_id
          AND (spouse_id IS NULL OR spouse_id <> NEW.person_id);
    END IF;
END$$

DELIMITER ;
