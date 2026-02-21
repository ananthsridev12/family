-- Person schema for strict relationship engine inputs

CREATE TABLE IF NOT EXISTS persons (
    person_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(160) NOT NULL,
    gender ENUM('male','female','other','unknown') NOT NULL DEFAULT 'unknown',
    birth_year SMALLINT NULL,
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
