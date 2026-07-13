-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for tebar_db
DROP DATABASE IF EXISTS `tebar_db`;
CREATE DATABASE IF NOT EXISTS `tebar_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `tebar_db`;

-- Dumping structure for table tebar_db.drivers
DROP TABLE IF EXISTS `drivers`;
CREATE TABLE IF NOT EXISTS `drivers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `motor` varchar(100) NOT NULL,
  `plat_nomor` varchar(20) NOT NULL,
  `sim` varchar(50) DEFAULT NULL,
  `stnk` varchar(50) DEFAULT NULL,
  `status_online` enum('0','1') DEFAULT '0',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `drivers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table tebar_db.drivers: ~1 rows (approximately)
INSERT INTO `drivers` (`id`, `user_id`, `motor`, `plat_nomor`, `sim`, `stnk`, `status_online`, `latitude`, `longitude`) VALUES
	(1, 3, 'Aerox Hedon', 'H 5524 G', NULL, NULL, '1', -7.02464943, 110.46972244);

-- Dumping structure for table tebar_db.orders
DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `driver_id` int DEFAULT NULL,
  `pickup_lat` decimal(10,8) NOT NULL,
  `pickup_lng` decimal(11,8) NOT NULL,
  `dropoff_lat` decimal(10,8) NOT NULL,
  `dropoff_lng` decimal(11,8) NOT NULL,
  `jarak_km` decimal(5,2) NOT NULL,
  `harga` int NOT NULL,
  `status` enum('Menunggu Driver','Driver Menuju Lokasi','Driver Tiba','Perjalanan Dimulai','Selesai','Batal') DEFAULT 'Menunggu Driver',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `driver_id` (`driver_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table tebar_db.orders: ~3 rows (approximately)
INSERT INTO `orders` (`id`, `user_id`, `driver_id`, `pickup_lat`, `pickup_lng`, `dropoff_lat`, `dropoff_lng`, `jarak_km`, `harga`, `status`, `created_at`) VALUES
	(1, 2, NULL, -7.02392849, 110.46527624, -7.02498857, 110.46565175, 0.30, 5000, 'Menunggu Driver', '2026-07-05 15:07:41'),
	(2, 3, 1, -7.02543237, 110.46611309, -7.02547499, 110.46607018, 0.00, 5000, 'Driver Menuju Lokasi', '2026-07-05 15:20:43'),
	(3, 2, 1, -7.02309100, 110.46249750, -7.04230000, 110.44020000, 4.20, 10500, 'Selesai', '2026-07-05 18:16:55');

-- Dumping structure for table tebar_db.payments
DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `jumlah` int NOT NULL,
  `metode` enum('Cash','Transfer') DEFAULT 'Cash',
  `status` enum('Pending','Lunas') DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table tebar_db.payments: ~0 rows (approximately)

-- Dumping structure for table tebar_db.ratings
DROP TABLE IF EXISTS `ratings`;
CREATE TABLE IF NOT EXISTS `ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `bintang` int DEFAULT NULL,
  `review` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `ratings_chk_1` CHECK (((`bintang` >= 1) and (`bintang` <= 5)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table tebar_db.ratings: ~0 rows (approximately)

-- Dumping structure for table tebar_db.reports
DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `pelapor_id` int NOT NULL,
  `pesan` text NOT NULL,
  `status` enum('Darurat','Diproses','Selesai') DEFAULT 'Darurat',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `pelapor_id` (`pelapor_id`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`pelapor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table tebar_db.reports: ~0 rows (approximately)

-- Dumping structure for table tebar_db.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fakultas` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default.jpg',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nim` (`nim`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table tebar_db.users: ~2 rows (approximately)
INSERT INTO `users` (`id`, `nim`, `nama`, `email`, `password`, `fakultas`, `no_hp`, `foto`, `created_at`) VALUES
	(1, '13182420098', 'Reysa Arjuna Fathan Adabi', 'reysaarjuna06@gmail.com', '$2y$10$WqsA9jNM4rO3n/kfLSzsueEi9mzgAf9ZD/8qMwVufUGZDt8lrd18W', NULL, NULL, 'default.jpg', '2026-07-05 15:05:49'),
	(2, '13182420123', 'Anan Maulana Rafi', 'ananmaulana@gmail.com', '$2y$10$jwTChJtkbjsH.3etCuRyhel6fCn5f6lSi.O81QmS4Vtn55gMpFpRa', NULL, NULL, 'default.jpg', '2026-07-05 15:06:53'),
	(3, '13182420112', 'Aktsaru Dinar', 'Aktsaru@gmail.com', '$2y$10$sC8hbHM6kWPT7kj3k/hG.uwsSFPY6.DrSaSAKKI8mWu0oQFYwtgXK', NULL, NULL, 'default.jpg', '2026-07-05 15:19:26');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
