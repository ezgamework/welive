DROP TABLE IF EXISTS `welive_admin`;

CREATE TABLE `welive_admin` (
  `aid` int(11) NOT NULL auto_increment,
  `type` tinyint(1) NOT NULL default '0',
  `grid` smallint(5) NOT NULL default '1',
  `activated` tinyint(1) NOT NULL default '0',
  `online` tinyint(1) NOT NULL default '0',
  `username` varchar(64) NOT NULL default '',
  `password` varchar(64) NOT NULL default '',
  `email` varchar(128) NOT NULL default '',
  `verifycode` varchar(8) NOT NULL default '',
  `first` int(11) NOT NULL default '0',
  `last` int(11) NOT NULL default '0',
  `lastip` varchar(64) NOT NULL default '',
  `logins` int(11) NOT NULL default '0',
  `fullname` varchar(64) NOT NULL default '',
  `fullname_en` varchar(64) NOT NULL default '',
  `post` varchar(64) NOT NULL default '',
  `post_en` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`aid`),
  KEY `grid` (`grid`),
  KEY `last` (`last`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS `welive_group`;

CREATE TABLE `welive_group` (
  `id` smallint(5) NOT NULL auto_increment,
  `sort` smallint(5) NOT NULL default '0',
  `activated` tinyint(1) NOT NULL default '1',
  `groupname` varchar(64) NOT NULL default '',
  `groupname_en` varchar(64) NOT NULL default '',
  `description`  varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM;


INSERT INTO `welive_group` VALUES (1, 1, 1, '默认客服组',  'Default Support Team', '这是一个默认的客服组');


DROP TABLE IF EXISTS `welive_comment`;

CREATE TABLE `welive_comment` (
  `cid` int(11) NOT NULL auto_increment,
  `grid` smallint(5) NOT NULL default '0',
  `readed` tinyint(1) NOT NULL default '0',
  `gid` int(11) NOT NULL default '0',
  `fullname` varchar(64) NOT NULL default '',
  `ip` varchar(64) NOT NULL default '',
  `zone` varchar(64) NOT NULL default '',
  `phone` varchar(128) NOT NULL default '',
  `email` varchar(128) NOT NULL default '',
  `content` text,
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`cid`),
  KEY `grid` (`grid`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS `welive_guest`;

CREATE TABLE `welive_guest` (
  `gid` int(11) NOT NULL auto_increment,
  `aid` int(11) NOT NULL default '0',
  `grid` smallint(5) NOT NULL default '0',
  `oid` int(11) NOT NULL default '0',
  `upload` tinyint(1) NOT NULL default '0',
  `lang` tinyint(1) NOT NULL default '0',
  `kickouts` int(11) NOT NULL default '0',
  `logins` int(11) NOT NULL default '0',
  `last` int(11) NOT NULL default '0',
  `lastip` varchar(64) NOT NULL default '',
  `ipzone` varchar(64) NOT NULL default '',
  `browser` varchar(64) NOT NULL default '',
  `mobile` tinyint(1) NOT NULL default '0',
  `fromurl` varchar(255) NOT NULL default '',
  `grade` tinyint(1) NOT NULL default '0',
  `fullname` varchar(64) NOT NULL default '',
  `address` varchar(128) NOT NULL default '',
  `phone` varchar(128) NOT NULL default '',
  `email` varchar(128) NOT NULL default '',
  `remark` text,
  `session` char(32) NOT NULL default '',
  PRIMARY KEY  (`gid`),
  KEY `grid` (`grid`),
  KEY `oid` (`oid`),
  KEY `aid` (`aid`)
) ENGINE=MyISAM AUTO_INCREMENT=1;


DROP TABLE IF EXISTS `welive_msg`;

CREATE TABLE `welive_msg` (
  `mid` int(11) NOT NULL auto_increment,
  `type` tinyint(1) NOT NULL default '0',
  `grid` smallint(5) NOT NULL default '0',
  `fromid` int(11) NOT NULL default '0',
  `fromname` varchar(64) NOT NULL default '',
  `toid` int(11) NOT NULL default '0',
  `toname` varchar(64) NOT NULL default '',
  `msg` text,
  `filetype` tinyint(1) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`mid`),
  KEY `grid` (`grid`),
  KEY `fromid` (`fromid`),
  KEY `toid` (`toid`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS `welive_phrase`;

CREATE TABLE `welive_phrase` (
  `pid` int(11) NOT NULL auto_increment,
  `aid` int(11) NOT NULL default '0',
  `sort` int(11) NOT NULL default '0',
  `activated` tinyint(1) NOT NULL default '0',
  `msg` text,
  `msg_en` text,
  PRIMARY KEY  (`pid`),
  KEY `aid` (`aid`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS `welive_session`;

CREATE TABLE `welive_session` (
  `sid` char(32) NOT NULL default '',
  `aid` int(11) NOT NULL default '0',
  `ip` varchar(32) NOT NULL default '',
  `agent` char(32) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`sid`),
  KEY `aid` (`aid`),
  KEY `time` (`time`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS `welive_vvc`;

CREATE TABLE `welive_vvc` (
  `vid` int(11) NOT NULL auto_increment,
  `code` varchar(9) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`vid`),
  KEY `time` (`time`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS `welive_rating`;

CREATE TABLE `welive_rating` (
  `rid` int(11) NOT NULL auto_increment,
  `gid` int(11) NOT NULL default '0',
  `aid` int(11) NOT NULL default '0',
  `score` tinyint(1) NOT NULL default '0',
  `msg` text,
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rid`),
  KEY `aid` (`aid`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS `welive_robot`;

CREATE TABLE `welive_robot` (
  `id` int(11) NOT NULL auto_increment,
  `activated` tinyint(1) NOT NULL default '0',
  `sort` int(11) NOT NULL default '0',
  `kn` int(3) NOT NULL default '0',
  `keyword` varchar(128) NOT NULL default '',
  `msg` text,
  `avatar` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `kn` (`kn`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS `welive_robotmsg`;

CREATE TABLE `welive_robotmsg` (
  `rmid` int(11) NOT NULL auto_increment,
  `grid` smallint(5) NOT NULL default '0',
  `remark` tinyint(1) NOT NULL default '0',
  `fromid` int(11) NOT NULL default '0',
  `fromname` varchar(64) NOT NULL default '',
  `msg` text,
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`rmid`),
  KEY `grid` (`grid`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS `welive_question`;

CREATE TABLE `welive_question` (
  `qid` int(11) NOT NULL auto_increment,
  `grid` smallint(5) NOT NULL default '0',
  `sort` int(11) NOT NULL default '0',
  `activated` tinyint(1) NOT NULL default '0',
  `title` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`qid`),
  KEY `grid` (`grid`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM;


DROP TABLE IF EXISTS `welive_firewall`;

CREATE TABLE `welive_firewall` (
  `fid` int(11) NOT NULL auto_increment,
  `aid` int(11) NOT NULL default '0',
  `bans` int(11) NOT NULL default '0',
  `ip` varchar(64) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `expire` int(11) NOT NULL default '0',
  PRIMARY KEY  (`fid`),
  KEY `expire` (`expire`)
) ENGINE=MyISAM;
