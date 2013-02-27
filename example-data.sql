# ************************************************************
# Sequel Pro SQL dump
# Version 4004
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: 127.0.0.1 (MySQL 5.5.9)
# Database: rapidphpme
# Generation Time: 2013-02-15 22:36:17 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table example_phpmvc
# ------------------------------------------------------------


DROP TABLE IF EXISTS `example_phpmvc`;

CREATE TABLE `example_phpmvc` (
  `group_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_parent` int(11) NOT NULL DEFAULT '0',
  `group_name` varchar(220) DEFAULT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `example_phpmvc` WRITE;
/*!40000 ALTER TABLE `example_phpmvc` DISABLE KEYS */;

INSERT INTO `example_phpmvc` (`group_id`, `group_parent`, `group_name`)
VALUES
	(1,0,'Bookmarks Menu'),
	(2,0,'Web Dev'),
	(3,0,'School'),
	(4,0,'Work'),
	(8,0,'Music'),
	(9,0,'News'),
	(10,2,'CSS'),
	(11,2,'PHP'),
	(12,2,'HTML'),
	(13,2,'jQuery'),
	(14,2,'Graphics'),
	(15,8,'Production Tools'),
	(16,8,'Samples'),
	(17,8,'Forums'),
	(18,8,'Labels'),
	(19,2,'Tools'),
	(20,2,'Tips'),
	(21,2,'User Interface'),
	(22,2,'Resources'),
	(23,0,'Shopping'),
	(24,0,'Travel'),
	(25,2,'SEO'),
	(26,24,'Properties'),
	(27,2,'Databases'),
	(28,2,'MySQL');

/*!40000 ALTER TABLE `example_phpmvc` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
