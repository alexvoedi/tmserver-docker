-- SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `rcp_web_messages` (
  `id` mediumint(9) unsigned NOT NULL auto_increment,
  `login` varchar(100) collate utf8_unicode_ci NOT NULL,
  `serverid` mediumint(9) unsigned NOT NULL default '0',
  `text` text collate utf8_unicode_ci NOT NULL,
  `status` tinyint(1) unsigned NOT NULL default '0',
  `date` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;