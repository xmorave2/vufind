SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `ebook_issues`;
CREATE TABLE `ebook_issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `cardnumber` varchar(50) DEFAULT NULL,
  `username` varchar(200) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `ereading_id` varchar(120) DEFAULT NULL,
  `record_id` varchar(120) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `author` varchar(200) DEFAULT NULL,
  `year` mediumint(9) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(4) DEFAULT NULL,
  `status_string` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ebook_issues_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP VIEW IF EXISTS `ebook_issues_statistics`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `ebook_issues_statistics` AS select date_format(`ebook_issues`.`timestamp`,'%Y-%m') AS `Mesic`,count(0) AS `Pocet` from `ebook_issues` where (`ebook_issues`.`status` = 1) group by date_format(`ebook_issues`.`timestamp`,'%Y-%m') order by date_format(`ebook_issues`.`timestamp`,'%Y-%m') desc;
