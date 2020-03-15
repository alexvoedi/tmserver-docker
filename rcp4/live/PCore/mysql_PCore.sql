-- SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"

CREATE TABLE IF NOT EXISTS `rcp_challenges` (
  `Id` mediumint(9) unsigned NOT NULL auto_increment,
  `Uid` varchar(27) collate utf8_unicode_ci NOT NULL,
  `Name` varchar(100) collate utf8_unicode_ci NOT NULL,
  `Author` varchar(30) collate utf8_unicode_ci NOT NULL,
  `Environment` varchar(15) collate utf8_unicode_ci NOT NULL,
  `GoodRating` mediumint(9) unsigned default '0',
  `BadRating` mediumint(9) unsigned default '0',
  PRIMARY KEY  (`Id`),
  UNIQUE KEY `Uid` (`Uid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `rcp_cptransactions` (
  `Id` mediumint(9) unsigned NOT NULL auto_increment,
  `Login` varchar(50) collate utf8_unicode_ci NOT NULL,
  `Reason` varchar(15) collate utf8_unicode_ci NOT NULL,
  `Billid` mediumint(9) unsigned NOT NULL,
  `Coppers` mediumint(9) NOT NULL default '0',
  `Date` datetime NOT NULL,
  PRIMARY KEY  (`Id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `rcp_players` (
  `Id` mediumint(9) unsigned NOT NULL auto_increment,
  `Login` varchar(100) collate utf8_unicode_ci NOT NULL,
  `NickName` varchar(100) collate utf8_unicode_ci NOT NULL,
  `UpdatedAt` datetime NOT NULL default '0000-00-00 00:00:00',
  `Wins` mediumint(9) unsigned NOT NULL default '0',
  `TimePlayed` mediumint(9) unsigned NOT NULL default '0',
  `TeamId` mediumint(9) unsigned default NULL,
  PRIMARY KEY  (`Id`),
  UNIQUE KEY `Login` (`Login`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `rcp_players_cache` (
  `PlayerId` mediumint(9) unsigned NOT NULL,
  `ServerId` mediumint(9) unsigned NOT NULL,
  `Plugin` varchar(24) collate utf8_unicode_ci NOT NULL,
  `Var` varchar(24) collate utf8_unicode_ci NOT NULL,
  `Value` varchar(255) collate utf8_unicode_ci NOT NULL,
  UNIQUE KEY `PlayerId` (`PlayerId`,`ServerId`,`Plugin`,`Var`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `rcp_records` (
  `Id` mediumint(9) unsigned NOT NULL auto_increment,
  `ChallengeId` mediumint(9) unsigned NOT NULL default '0',
  `PlayerId` mediumint(9) unsigned NOT NULL default '0',
  `Score` mediumint(9) unsigned NOT NULL default '0',
  `CheckPoints` text collate utf8_unicode_ci,
  `Date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`Id`),
  UNIQUE KEY `ChallengeId` (`ChallengeId`,`PlayerId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `rcp_votes` (
  `Id` mediumint(9) unsigned NOT NULL auto_increment,
  `PlayerId` mediumint(9) unsigned NOT NULL,
  `ChallengeId` mediumint(9) unsigned NOT NULL,
  `Score` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`Id`),
  UNIQUE KEY `Vote` (`PlayerId`,`ChallengeId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;