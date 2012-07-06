CREATE TABLE IF NOT EXISTS `ObjectCategory` (
  `LdapDisplayName` varchar(255) NOT NULL,
  `ObjDistName` int(11) NOT NULL,
  KEY `ObjDistName` (`ObjDistName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT INTO ObjectCategory 
SELECT `LDAP-Display-Name`, `Default-Object-Category` 
FROM 20120706_142245_Categories WHERE `Default-Object-Category` != 0;
