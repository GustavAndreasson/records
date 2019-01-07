CREATE DATABASE `records` /*!40100 DEFAULT CHARACTER SET utf8 */;

use records;

CREATE TABLE `discogs_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `cover` varchar(255) DEFAULT NULL,
  `format` varchar(255) DEFAULT NULL,
  `year` int(4) DEFAULT NULL,
  `updated` date DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `user_records` (
  `user_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `added_date` date DEFAULT NULL,
  PRIMARY KEY (`user_id`,`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `artists` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `updated` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `artist_members` (
  `group_id` INT(11) NOT NULL,
  `member_id` INT(11) NOT NULL,
  `active` INT(1) NULL,
  PRIMARY KEY (`group_id`, `member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `record_artists` (
  `record_id` int(11) NOT NULL,
  `artist_id` int(11) NOT NULL,
  `delimiter` varchar(255) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`record_id`,`artist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `tracks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_id` int(11) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8319 DEFAULT CHARSET=latin1;

CREATE TABLE `track_artists` (
  `track_id` int(11) NOT NULL,
  `artist_id` int(11) NOT NULL,
  `delimiter` varchar(255) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  PRIMARY KEY (`track_id`,`artist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
