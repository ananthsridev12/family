-- Core metadata + relationship dictionary (hardened)

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS branches (
    branch_id INT AUTO_INCREMENT PRIMARY KEY,
    branch_name VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','member') NOT NULL DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS relationship_dictionary (
    `key` VARCHAR(80) PRIMARY KEY,
    title_en VARCHAR(120) NOT NULL,
    title_ta VARCHAR(120) NOT NULL,
    category VARCHAR(40) NOT NULL,
    side ENUM('Paternal','Maternal','Direct','In-Law','Any') NOT NULL DEFAULT 'Any',
    generation INT NOT NULL,
    degree INT NULL,
    gender ENUM('male','female','any') NOT NULL DEFAULT 'any',
    cousin_level INT NULL,
    removed INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELETE FROM relationship_dictionary;

INSERT INTO relationship_dictionary
(`key`, title_en, title_ta, category, side, generation, degree, gender, cousin_level, removed)
VALUES
('self','Self','தானே','direct','Direct',0,0,'any',NULL,NULL),
('unknown','Unknown','தெரியாது','direct','Any',0,NULL,'any',NULL,NULL),
('relative','Relative','உறவினர்','direct','Any',0,NULL,'any',NULL,NULL),
('no_blood_relation','No blood relation found','இரத்த உறவு இல்லை','direct','Any',0,NULL,'any',NULL,NULL),

('father','Father','அப்பா','direct','Paternal',-1,1,'male',NULL,NULL),
('mother','Mother','அம்மா','direct','Maternal',-1,1,'female',NULL,NULL),
('son','Son','மகன்','direct','Direct',1,1,'male',NULL,NULL),
('daughter','Daughter','மகள்','direct','Direct',1,1,'female',NULL,NULL),
('husband','Husband','கணவன்','direct','Direct',0,1,'male',NULL,NULL),
('wife','Wife','மனைவி','direct','Direct',0,1,'female',NULL,NULL),
('brother','Brother','சகோதரர்','sibling','Direct',0,1,'male',NULL,NULL),
('sister','Sister','சகோதரி','sibling','Direct',0,1,'female',NULL,NULL),
('elder_brother','Elder Brother','அண்ணன்','sibling','Direct',0,1,'male',NULL,NULL),
('younger_brother','Younger Brother','தம்பி','sibling','Direct',0,1,'male',NULL,NULL),
('elder_sister','Elder Sister','அக்கா','sibling','Direct',0,1,'female',NULL,NULL),
('younger_sister','Younger Sister','தங்கை','sibling','Direct',0,1,'female',NULL,NULL),
('nephew','Nephew','சகோதரர் மகன்','extended','Any',1,2,'male',NULL,NULL),
('niece','Niece','சகோதரி மகள்','extended','Any',1,2,'female',NULL,NULL),
('nephew_brother_son','Nephew (Brother''s Son)','சகோதரர் மகன்','extended','Any',1,2,'male',NULL,NULL),
('nephew_sister_son','Nephew (Sister''s Son)','சகோதரி மகன்','extended','Any',1,2,'male',NULL,NULL),
('niece_brother_daughter','Niece (Brother''s Daughter)','சகோதரி மகள்','extended','Any',1,2,'female',NULL,NULL),
('niece_sister_daughter','Niece (Sister''s Daughter)','சகோதரி மகள்','extended','Any',1,2,'female',NULL,NULL),
('first_cousin_male','First Cousin (Male)','Cousin (Male)','extended','Any',0,2,'male',1,0),
('first_cousin_female','First Cousin (Female)','Cousin (Female)','extended','Any',0,2,'female',1,0),

('grandfather','Grandfather','தாத்தா','ancestor','Any',-2,2,'male',NULL,NULL),
('grandmother','Grandmother','பாட்டி','ancestor','Any',-2,2,'female',NULL,NULL),
('paternal_grandfather','Paternal Grandfather','தாத்தா (தந்தை வழி)','ancestor','Paternal',-2,2,'male',NULL,NULL),
('paternal_grandmother','Paternal Grandmother','பாட்டி (தந்தை வழி)','ancestor','Paternal',-2,2,'female',NULL,NULL),
('maternal_grandfather','Maternal Grandfather','தாத்தா (தாய் வழி)','ancestor','Maternal',-2,2,'male',NULL,NULL),
('maternal_grandmother','Maternal Grandmother','பாட்டி (தாய் வழி)','ancestor','Maternal',-2,2,'female',NULL,NULL),
('ancestor','Ancestor','முன்னோர்','ancestor','Any',-3,NULL,'any',NULL,NULL),
('descendant','Descendant','பின்வந்தவர்','descendant','Any',3,NULL,'any',NULL,NULL),
('grandson','Grandson','பேரன்','descendant','Any',2,2,'male',NULL,NULL),
('granddaughter','Granddaughter','பேத்தி','descendant','Any',2,2,'female',NULL,NULL),

('paternal_uncle','Paternal Uncle','தந்தை சகோதரர்','extended','Paternal',-1,2,'male',NULL,NULL),
('mama','Maternal Uncle','தாய் மாமா','extended','Maternal',-1,2,'male',NULL,NULL),
('paternal_aunt','Paternal Aunt','அத்தை','extended','Paternal',-1,2,'female',NULL,NULL),
('maternal_aunt','Maternal Aunt','தாய் சகோதரி','extended','Maternal',-1,2,'female',NULL,NULL),
('periyamma','Elder Aunt','பெரியம்மா','extended','Any',-1,2,'female',NULL,NULL),
('chithi','Younger Aunt','சித்தி','extended','Any',-1,2,'female',NULL,NULL),
('periyappa','Paternal Elder Uncle','பெரியப்பா','extended','Paternal',-1,2,'male',NULL,NULL),
('chithappa','Paternal Younger Uncle','சித்தப்பா','extended','Paternal',-1,2,'male',NULL,NULL),
('athai','Paternal Aunt','அத்தை','extended','Paternal',-1,2,'female',NULL,NULL),

('father_in_law','Father-in-law','மாமனார்','inlaw','In-Law',-1,2,'male',NULL,NULL),
('mother_in_law','Mother-in-law','மாமியார்','inlaw','In-Law',-1,2,'female',NULL,NULL),
('mami','Maternal Uncle''s Wife','மாமி','inlaw','In-Law',-1,2,'female',NULL,NULL),
('son_in_law','Son-in-law','மாப்பிள்ளை','inlaw','In-Law',1,2,'male',NULL,NULL),
('daughter_in_law','Daughter-in-law','மருமகள்','inlaw','In-Law',1,2,'female',NULL,NULL),
('brother_in_law','Brother-in-law','மைத்துனர்','inlaw','In-Law',0,2,'male',NULL,NULL),
('sister_in_law','Sister-in-law','மைத்துனி','inlaw','In-Law',0,2,'female',NULL,NULL),
('co_sister_brothers_wives','Co-Sister (Brothers'' Wives)','சகோதரரின் மனைவி','inlaw','In-Law',0,2,'female',NULL,NULL),
('husbands_sister_safe','Husband''s Sister','கணவனின் சகோதரி','inlaw','In-Law',0,2,'female',NULL,NULL),
('brothers_wife_safe','Brother''s Wife','சகோதரரின் மனைவி','inlaw','In-Law',0,2,'female',NULL,NULL),
('sisters_husband_safe','Sister''s Husband','சகோதரியின் கணவன்','inlaw','In-Law',0,2,'male',NULL,NULL),
('affinal_relative_level_1','Affinal Relative (Level 1)','திருமண உறவு (நிலை 1)','inlaw','In-Law',0,NULL,'any',NULL,NULL),
('affinal_relative_level_2','Affinal Relative (Level 2)','திருமண உறவு (நிலை 2)','inlaw','In-Law',0,NULL,'any',NULL,NULL),
('affinal_relative_level_3','Affinal Relative (Level 3)','திருமண உறவு (நிலை 3)','inlaw','In-Law',0,NULL,'any',NULL,NULL)
ON DUPLICATE KEY UPDATE
title_en = VALUES(title_en),
title_ta = VALUES(title_ta),
category = VALUES(category),
side = VALUES(side),
generation = VALUES(generation),
degree = VALUES(degree),
gender = VALUES(gender),
cousin_level = VALUES(cousin_level),
removed = VALUES(removed);



