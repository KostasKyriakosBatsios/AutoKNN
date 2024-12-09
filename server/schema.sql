-- --------------------------------------------------------
-- Διακομιστής:                  127.0.0.1
-- Έκδοση διακομιστή:            10.4.32-MariaDB - mariadb.org binary distribution
-- Λειτ. σύστημα διακομιστή:     Win64
-- HeidiSQL Έκδοση:              12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for autoknn_db
CREATE DATABASE IF NOT EXISTS `autoknn_db` /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_bin */;
USE `autoknn_db`;

-- Dumping structure for πίνακας autoknn_db.dataset_execution
DROP TABLE IF EXISTS `dataset_execution`;
CREATE TABLE IF NOT EXISTS `dataset_execution` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_of_user` int(11) NOT NULL,
  `name_of_dataset` varchar(255) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `status` enum('Not Started','In Progress','Completed','Failed') DEFAULT 'Not Started',
  `results_path` varchar(255) DEFAULT NULL,
  `creation_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `models` (`id_of_user`),
  CONSTRAINT `FK_dataset_execution_users` FOREIGN KEY (`id_of_user`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table autoknn_db.dataset_execution: ~0 rows (approximately)

-- Dumping structure for πίνακας autoknn_db.models
DROP TABLE IF EXISTS `models`;
CREATE TABLE IF NOT EXISTS `models` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_of_executed_dataset` int(11) NOT NULL,
  `name_of_model` varchar(50) NOT NULL,
  `features` text NOT NULL,
  `name_of_class` varchar(50) NOT NULL,
  `k` int(11) NOT NULL,
  `metric_distance` varchar(50) NOT NULL,
  `p` int(11) DEFAULT NULL,
  `stratified_sampling` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `classes` (`id_of_executed_dataset`) USING BTREE,
  CONSTRAINT `FK_models_dataset_execution` FOREIGN KEY (`id_of_executed_dataset`) REFERENCES `dataset_execution` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table autoknn_db.models: ~0 rows (approximately)

-- Dumping structure for πίνακας autoknn_db.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fname` varchar(50) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `pass` varchar(100) NOT NULL,
  `token` varchar(100) NOT NULL,
  `email_verification` tinyint(1) NOT NULL DEFAULT 0,
  `allowPublic` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table autoknn_db.users: ~0 rows (approximately)
INSERT INTO `users` (`id`, `fname`, `lname`, `email`, `pass`, `token`, `email_verification`, `allowPublic`) VALUES
	(47, 'John', 'Doe', 'konstantinos.mpatsios@gmail.com', '$2y$10$we5Gm9LRwo20K4UHcGkVeu8nXra60XbiXKoL.V85OEwvTdXS5S4E6', 'ca0a2c907c46cb55caeb91fe42fccccb73c434ba65270a0a70e5b7010fad4eb31c0f3b816ea0aafcfc406610fce48d2a38db', 1, 0);

-- Dumping structure for πίνακας autoknn_db.verify_account
DROP TABLE IF EXISTS `verify_account`;
CREATE TABLE IF NOT EXISTS `verify_account` (
  `id` int(11) NOT NULL,
  `verification_key` varchar(100) NOT NULL,
  `creation_time` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_verify_account_users` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- Dumping data for table autoknn_db.verify_account: ~0 rows (approximately)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
