CREATE TABLE IF NOT EXISTS `GUID` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO GUID(`value`,`text`)
SELECT `Rights-Guid`, `Common-Name` 
FROM 20120706_142245_Objects WHERE `Rights-Guid` IS NOT NULL 
	AND `Rights-Guid` != '';

INSERT INTO GUID(`value`,`text`)
SELECT `Schema-ID-Guid`, `Common-Name` 
FROM 20120706_142245_Objects WHERE `Schema-ID-Guid` IS NOT NULL 
	AND `Schema-ID-Guid` != '0'
	AND `Schema-ID-Guid` != '00000000-0000-0000-0000-000000000000';
