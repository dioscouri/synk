CREATE TABLE IF NOT EXISTS `#__synk_config` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `ordering` int(11) NOT NULL,
  `published` tinyint(1) NOT NULL,
  `checked_out` int(11) unsigned NOT NULL default '0',
  `checked_out_time` datetime NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`id`)
) Engine=MyISAM ;

CREATE TABLE IF NOT EXISTS `#__synk_databases` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `checked_out` int(11) unsigned NOT NULL default '0',
  `checked_out_time` datetime NOT NULL,
  `published` tinyint(1) NOT NULL,
  `publish_up` datetime NOT NULL,
  `publish_down` datetime NOT NULL,
  `driver` varchar(255) NOT NULL,
  `host` varchar(255) NOT NULL,
  `port` int(11) NOT NULL,
  `user` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `database` varchar(255) NOT NULL,
  `prefix` varchar(255) NOT NULL,
  `verified` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
) Engine=MyISAM ;


CREATE TABLE IF NOT EXISTS `#__synk_logs` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `synchronizationid` int(11) NOT NULL,
  `databaseid` int(11) NOT NULL,
  `eventid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `contentid` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`)
) Engine=MyISAM  ;


CREATE TABLE IF NOT EXISTS `#__synk_s2e` (
  `synchronizationid` int(11) NOT NULL,
  `eventid` int(11) NOT NULL,
  `parameter` varchar(255) NOT NULL,
  PRIMARY KEY  (`synchronizationid`,`eventid`)
) Engine=MyISAM;


CREATE TABLE IF NOT EXISTS `#__synk_synchronizations` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `checked_out` int(11) unsigned NOT NULL default '0',
  `checked_out_time` datetime NOT NULL,
  `published` tinyint(1) NOT NULL,
  `publish_up` datetime NOT NULL,
  `publish_down` datetime NOT NULL,
  `databaseid` int(11) NOT NULL,
  `insert` tinyint(1) NOT NULL,
  `use_custom` tinyint(1) NOT NULL,
  `custom_query` text NOT NULL,
  `limit_hourly` int(11) NOT NULL,
  `limit_daily` int(11) NOT NULL,
  `limit_weekly` int(11) NOT NULL,
  `limit_monthly` int(11) NOT NULL,
  `limit_yearly` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) Engine=MyISAM ;


CREATE TABLE IF NOT EXISTS `#__synk_events` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `type` tinyint(1) NOT NULL default '0',
  `published` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) Engine=MyISAM ;