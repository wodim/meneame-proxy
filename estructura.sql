SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `hits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vhost` int(11) NOT NULL,
  `uri` varchar(512) NOT NULL,
  `date` datetime NOT NULL,
  `ip` varchar(15) NOT NULL,
  `user_agent` varchar(128) NOT NULL,
  `referer` varchar(512) NOT NULL,
  `time` float NOT NULL,
  `real_time` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;

