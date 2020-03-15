-- SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `rcp_teams` (
  `Id` mediumint(9) unsigned NOT NULL auto_increment,
  `Name` varchar(100) collate utf8_unicode_ci NOT NULL,
  `LeaderId` mediumint(9) unsigned NOT NULL,
  `JoinCode` varchar(100) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`Id`),
  KEY `JoinCode` (`JoinCode`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;