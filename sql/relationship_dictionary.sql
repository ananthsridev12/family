-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 21, 2026 at 11:59 PM
-- Server version: 5.7.23-23
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `de2shrnx_familyv3`
--

-- --------------------------------------------------------

--
-- Table structure for table `relationship_dictionary`
--

CREATE TABLE `relationship_dictionary` (
  `key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_en` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ta` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `side` enum('Paternal','Maternal','Direct','In-Law','Any') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Any',
  `generation` int(11) NOT NULL,
  `degree` int(11) DEFAULT NULL,
  `gender` enum('male','female','any') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'any',
  `cousin_level` int(11) DEFAULT NULL,
  `removed` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `relationship_dictionary`
--

INSERT INTO `relationship_dictionary` (`key`, `title_en`, `title_ta`, `category`, `side`, `generation`, `degree`, `gender`, `cousin_level`, `removed`) VALUES
('ancestor', 'Ancestor', 'முன்னோர்', 'ancestor', 'Any', -3, NULL, 'any', NULL, NULL),
('athai', 'Paternal Aunt', 'அத்தை', 'extended', 'Paternal', -1, 2, 'female', NULL, NULL),
('brother', 'Brother', 'சகோதரர்', 'sibling', 'Direct', 0, 1, 'male', NULL, NULL),
('brother_in_law', 'Brother-in-law', 'மைத்துனர்', 'inlaw', 'In-Law', 0, 2, 'male', NULL, NULL),
('brothers_wife_safe', 'Brother\'s Wife', 'சகோதரரின் மனைவி', 'inlaw', 'In-Law', 0, 2, 'female', NULL, NULL),
('chithappa', 'Paternal Younger Uncle', 'சித்தப்பா', 'extended', 'Paternal', -1, 2, 'male', NULL, NULL),
('chithi', 'Younger Aunt', 'சித்தி', 'extended', 'Any', -1, 2, 'female', NULL, NULL),
('co_sister_brothers_wives', 'Husband Brother\'s Wife', 'கணவன் சகோதரரின் மனைவி', 'inlaw', 'In-Law', 0, 2, 'female', NULL, NULL),
('daughter', 'Daughter', 'மகள்', 'direct', 'Direct', 1, 1, 'female', NULL, NULL),
('daughter_in_law', 'Daughter-in-law', 'மருமகள்', 'inlaw', 'In-Law', 1, 2, 'female', NULL, NULL),
('descendant', 'Descendant', 'பின்வந்தவர்', 'descendant', 'Any', 3, NULL, 'any', NULL, NULL),
('elder_brother', 'Elder Brother', 'அண்ணன்', 'sibling', 'Direct', 0, 1, 'male', NULL, NULL),
('elder_sister', 'Elder Sister', 'அக்கா', 'sibling', 'Direct', 0, 1, 'female', NULL, NULL),
('father', 'Father', 'அப்பா', 'direct', 'Paternal', -1, 1, 'male', NULL, NULL),
('father_in_law', 'Father-in-law', 'மாமனார்', 'inlaw', 'In-Law', -1, 2, 'male', NULL, NULL),
('first_cousin_female', 'First Cousin (Female)', 'Cousin (Female)', 'extended', 'Any', 0, 2, 'female', 1, 0),
('first_cousin_male', 'First Cousin (Male)', 'Cousin (Male)', 'extended', 'Any', 0, 2, 'male', 1, 0),
('granddaughter', 'Granddaughter', 'பேத்தி', 'descendant', 'Any', 2, 2, 'female', NULL, NULL),
('grandfather', 'Grandfather', 'தாத்தா', 'ancestor', 'Any', -2, 2, 'male', NULL, NULL),
('grandmother', 'Grandmother', 'பாட்டி', 'ancestor', 'Any', -2, 2, 'female', NULL, NULL),
('grandson', 'Grandson', 'பேரன்', 'descendant', 'Any', 2, 2, 'male', NULL, NULL),
('husband', 'Husband', 'கணவன்', 'direct', 'Direct', 0, 1, 'male', NULL, NULL),
('husbands_sister_safe', 'Husband\'s Sister', 'கணவனின் சகோதரி', 'inlaw', 'In-Law', 0, 2, 'female', NULL, NULL),
('mama', 'Maternal Uncle', 'தாய் மாமா', 'extended', 'Maternal', -1, 2, 'male', NULL, NULL),
('mami', 'Maternal Uncle\'s Wife', 'மாமி', 'inlaw', 'In-Law', -1, 2, 'female', NULL, NULL),
('maternal_aunt', 'Maternal Aunt', 'தாய் சகோதரி', 'extended', 'Maternal', -1, 2, 'female', NULL, NULL),
('maternal_grandfather', 'Maternal Grandfather', 'தாத்தா (தாய் வழி)', 'ancestor', 'Maternal', -2, 2, 'male', NULL, NULL),
('maternal_grandmother', 'Maternal Grandmother', 'பாட்டி (தாய் வழி)', 'ancestor', 'Maternal', -2, 2, 'female', NULL, NULL),
('mother', 'Mother', 'அம்மா', 'direct', 'Maternal', -1, 1, 'female', NULL, NULL),
('mother_in_law', 'Mother-in-law', 'மாமியார்', 'inlaw', 'In-Law', -1, 2, 'female', NULL, NULL),
('nephew', 'Nephew', 'சகோதரர் மகன்', 'extended', 'Any', 1, 2, 'male', NULL, NULL),
('nephew_brother_son', 'Nephew (Brother\'s Son)', 'சகோதரர் மகன்', 'extended', 'Any', 1, 2, 'male', NULL, NULL),
('nephew_sister_son', 'Nephew (Sister\'s Son)', 'சகோதரி மகன்', 'extended', 'Any', 1, 2, 'male', NULL, NULL),
('niece', 'Niece', 'சகோதரி மகள்', 'extended', 'Any', 1, 2, 'female', NULL, NULL),
('niece_brother_daughter', 'Niece (Brother\'s Daughter)', 'சகோதரி மகள்', 'extended', 'Any', 1, 2, 'female', NULL, NULL),
('niece_sister_daughter', 'Niece (Sister\'s Daughter)', 'சகோதரி மகள்', 'extended', 'Any', 1, 2, 'female', NULL, NULL),
('no_blood_relation', 'No blood relation found', 'இரத்த உறவு இல்லை', 'direct', 'Any', 0, NULL, 'any', NULL, NULL),
('paternal_aunt', 'Paternal Aunt', 'அத்தை', 'extended', 'Paternal', -1, 2, 'female', NULL, NULL),
('paternal_grandfather', 'Paternal Grandfather', 'தாத்தா (தந்தை வழி)', 'ancestor', 'Paternal', -2, 2, 'male', NULL, NULL),
('paternal_grandmother', 'Paternal Grandmother', 'பாட்டி (தந்தை வழி)', 'ancestor', 'Paternal', -2, 2, 'female', NULL, NULL),
('paternal_uncle', 'Paternal Uncle', 'தந்தை சகோதரர்', 'extended', 'Paternal', -1, 2, 'male', NULL, NULL),
('periyamma', 'Elder Aunt', 'பெரியம்மா', 'extended', 'Any', -1, 2, 'female', NULL, NULL),
('periyappa', 'Paternal Elder Uncle', 'பெரியப்பா', 'extended', 'Paternal', -1, 2, 'male', NULL, NULL),
('relative', 'Relative', 'உறவினர்', 'direct', 'Any', 0, NULL, 'any', NULL, NULL),
('self', 'Self', 'தானே', 'direct', 'Direct', 0, 0, 'any', NULL, NULL),
('sister', 'Sister', 'சகோதரி', 'sibling', 'Direct', 0, 1, 'female', NULL, NULL),
('sister_in_law', 'Sister-in-law', 'மைத்துனி', 'inlaw', 'In-Law', 0, 2, 'female', NULL, NULL),
('sisters_husband_safe', 'Sister\'s Husband', 'சகோதரியின் கணவன்', 'inlaw', 'In-Law', 0, 2, 'male', NULL, NULL),
('son', 'Son', 'மகன்', 'direct', 'Direct', 1, 1, 'male', NULL, NULL),
('son_in_law', 'Son-in-law', 'மாப்பிள்ளை', 'inlaw', 'In-Law', 1, 2, 'male', NULL, NULL),
('unknown', 'Unknown', 'தெரியாது', 'direct', 'Any', 0, NULL, 'any', NULL, NULL),
('wife', 'Wife', 'மனைவி', 'direct', 'Direct', 0, 1, 'female', NULL, NULL),
('younger_brother', 'Younger Brother', 'தம்பி', 'sibling', 'Direct', 0, 1, 'male', NULL, NULL),
('younger_sister', 'Younger Sister', 'தங்கை', 'sibling', 'Direct', 0, 1, 'female', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `relationship_dictionary`
--
ALTER TABLE `relationship_dictionary`
  ADD PRIMARY KEY (`key`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
