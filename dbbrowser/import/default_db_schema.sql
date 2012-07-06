SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `auditad`
--

-- --------------------------------------------------------

--
-- Table structure for table `ACE_AD`
--

CREATE TABLE IF NOT EXISTS `ACE_AD` (
  `CommonName` varchar(255) DEFAULT NULL,
  `OU` varchar(255) DEFAULT NULL,
  `ObjectCategory` int(11) NOT NULL,
  `ObjectSID` varchar(255) NOT NULL,
  `sd_id` int(11) DEFAULT NULL,
  `PrimaryOwner` varchar(255) DEFAULT NULL,
  `PrimaryGroup` varchar(255) DEFAULT NULL,
  `AceType` int(11) DEFAULT NULL,
  `AceFlags` int(11) DEFAULT NULL,
  `AccessMask` int(11) DEFAULT NULL,
  `Flags` int(11) DEFAULT NULL,
  `ObjectType` varchar(255) DEFAULT NULL,
  `InheritedObjectType` varchar(255) DEFAULT NULL,
  `TrusteeSID` varchar(255) DEFAULT NULL,
  `TrusteeCN` varchar(255) DEFAULT NULL,
  KEY `CommonName` (`CommonName`),
  KEY `TrusteeCN` (`TrusteeCN`),
  KEY `AccessMask` (`AccessMask`),
  KEY `AceFlags` (`AceFlags`),
  KEY `AceType` (`AceType`),
  KEY `ObjectCategory` (`ObjectCategory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `ACE_AD`
--


-- --------------------------------------------------------

--
-- Table structure for table `ACE_EXCH`
--

CREATE TABLE IF NOT EXISTS `ACE_EXCH` (
  `CommonName` varchar(255) DEFAULT NULL,
  `OU` varchar(255) DEFAULT NULL,
  `ObjectCategory` int(11) NOT NULL,
  `ObjectSID` varchar(255) NOT NULL,
  `sd_id` int(11) DEFAULT NULL,
  `PrimaryOwner` varchar(255) DEFAULT NULL,
  `PrimaryGroup` varchar(255) DEFAULT NULL,
  `AceType` int(11) DEFAULT NULL,
  `AceFlags` int(11) DEFAULT NULL,
  `AccessMask` int(11) DEFAULT NULL,
  `Flags` int(11) DEFAULT NULL,
  `ObjectType` varchar(255) DEFAULT NULL,
  `InheritedObjectType` varchar(255) DEFAULT NULL,
  `TrusteeSID` varchar(255) DEFAULT NULL,
  `TrusteeCN` varchar(255) DEFAULT NULL,
  KEY `CommonName` (`CommonName`),
  KEY `TrusteeCN` (`TrusteeCN`),
  KEY `AccessMask` (`AccessMask`),
  KEY `AceFlags` (`AceFlags`),
  KEY `AceType` (`AceType`),
  KEY `ObjectCategory` (`ObjectCategory`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `ACE_EXCH`
--


-- --------------------------------------------------------

--
-- Table structure for table `ADEMPTY`
--

CREATE TABLE IF NOT EXISTS `ADEMPTY` (
  `objectCategory` varchar(255) DEFAULT NULL,
  `DN` varchar(255) DEFAULT NULL,
  `AccessMask` int(11) DEFAULT NULL,
  `AceFlags` int(11) DEFAULT NULL,
  `AceType` int(11) DEFAULT NULL,
  `Flags` int(11) DEFAULT NULL,
  `InheritedObjectType` varchar(255) DEFAULT NULL,
  `ObjectType` varchar(255) DEFAULT NULL,
  `Trustee` varchar(255) DEFAULT NULL,
  `SID` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `ADEMPTY`
--

-- --------------------------------------------------------

--
-- Table structure for table `GUID`
--

CREATE TABLE IF NOT EXISTS `GUID` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) DEFAULT NULL,
  `text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `GUID`
--


-- --------------------------------------------------------

--
-- Table structure for table `ObjectCategory`
--

CREATE TABLE IF NOT EXISTS `ObjectCategory` (
  `LdapDisplayName` varchar(255) NOT NULL,
  `ObjDistName` int(11) NOT NULL,
  KEY `ObjDistName` (`ObjDistName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `ObjectCategory`
--



-- --------------------------------------------------------

--
-- Table structure for table `SID`
--

CREATE TABLE IF NOT EXISTS `SID` (
  `LDAPDisplayName` varchar(255) DEFAULT NULL,
  `ObjectSID` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `SID`
--

INSERT INTO `SID` (`LDAPDisplayName`, `ObjectSID`) VALUES
('Null Authority', 'S-1-0'),
('Nobody', 'S-1-0-0'),
('World Authority', 'S-1-1'),
('Everyone', 'S-1-1-0'),
('Local Authority', 'S-1-2'),
('Creator Authority', 'S-1-3'),
('Creator Owner', 'S-1-3-0'),
('Creator Group', 'S-1-3-1'),
('Creator Owner Server', 'S-1-3-2'),
('NT Authority', 'S-1-5'),
('Dialup', 'S-1-5-1'),
('Network', 'S-1-5-2'),
('Batch', 'S-1-5-3'),
('Interactive', 'S-1-5-4'),
('Logon Session', 'S-1-5-5- X - Y'),
('Service', 'S-1-5-6'),
('Anonymous', 'S-1-5-7'),
('Proxy', 'S-1-5-8'),
('Enterprise Domain Controllers', 'S-1-5-9'),
('Principal Self (or Self)', 'S-1-5-10'),
('Authenticated Users', 'S-1-5-11'),
('Restricted Code', 'S-1-5-12'),
('Local System', 'S-1-5-18'),
('Administrator', 'S-1-5-%-500'),
('Guest', 'S-1-5-%-501'),
('KRBTGT', 'S-1-5-%-502'),
('Domain Admins', 'S-1-5-%-512'),
('Domain Users', 'S-1-5-%-513'),
('Domain Guests', 'S-1-5-%-514'),
('Domain Computers', 'S-1-5-%-515'),
('Domain Controllers', 'S-1-5-%-516'),
('Cert Publishers', 'S-1-5-%-517'),
('Schema Admins', 'S-1-5-%-518'),
('Enterprise Admins', 'S-1-5-%-519'),
('Group Policy Creators Owners', 'S-1-5-%-520'),
('RAS and IAS Servers', 'S-1-5-%-553'),
('Administrators', 'S-1-5-32-544'),
('Users', 'S-1-5-32-545'),
('Guests', 'S-1-5-32-546'),
('Power Users', 'S-1-5-32-547'),
('Account Operators', 'S-1-5-32-548'),
('Server Operators', 'S-1-5-32-549'),
('Print Operators', 'S-1-5-32-550'),
('Backup Operators', 'S-1-5-32-551'),
('Replicators', 'S-1-5-32-552'),
('BUILTIN\\Pre-Windows 2000 Compatible Access', 'S-1-5-32-554'),
('BUILTIN\\Remote Desktop Users', 'S-1-5-32-555'),
('BUILTIN\\Network Configuration Operators', 'S-1-5-32-556'),
('BUILTIN\\Incoming Forest Trust Builders', 'S-1-5-32-557'),
('BUILTIN\\Performance Monitor Users', 'S-1-5-32-558'),
('BUILTIN\\Performance Log Users', 'S-1-5-32-559'),
('BUILTIN\\Windows Authorization Access Group', 'S-1-5-32-560'),
('BUILTIN\\Terminal Server License Servers', 'S-1-5-32-561'),
('BUILTIN\\Distributed COM Users', 'S-1-5-32-562'),
('Enterprise Read-only Domain Controllers ', 'S-1-5-21-%-498'),
('Read-only Domain Controllers', 'S-1-5-21-%-521'),
('BUILTIN\\Cryptographic Operators', 'S-1-5-32-569'),
('Allowed RODC Password Replication Group ', 'S-1-5-21-%-571'),
('Denied RODC Password Replication Group ', 'S-1-5-21-%-572'),
('BUILTIN\\Event Log Readers ', 'S-1-5-32-573'),
('BUILTIN\\Certificate Service DCOM Access ', 'S-1-5-32-574'),
('Cloneable Domain Controllers', 'S-1-5-21-%-522'),
('BUILTIN\\RDS Remote Access Servers', 'S-1-5-32-575'),
('BUILTIN\\RDS Endpoint Servers', 'S-1-5-32-576'),
('BUILTIN\\RDS Management Servers', 'S-1-5-32-577'),
('BUILTIN\\Hyper-V Administrators', 'S-1-5-32-578'),
('BUILTIN\\Access Control Assistance Operators', 'S-1-5-32-579'),
('BUILTIN\\Remote Management Users', 'S-1-5-32-580');

