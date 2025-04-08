-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 08, 2025 at 11:43 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

CREATE DATABASE IF NOT EXISTS `betail_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `betail_db`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `role` enum('acheteur','vendeur','admin') NOT NULL,
  `date_inscription` datetime DEFAULT current_timestamp(),
  `actif` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`nom`, `email`, `password_hash`, `telephone`, `role`) VALUES
('Admin', 'admin@marchebetail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '777777777', 'admin'),
('Vendeur Test', 'vendeur@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '777777778', 'vendeur');

--
-- Table structure for table `betail`
--

CREATE TABLE `betail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendeur_id` int(11) NOT NULL,
  `categorie` enum('bovins','ovins','caprins') NOT NULL,
  `nom_betail` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `date_publication` datetime DEFAULT current_timestamp(),
  `statut` enum('disponible','reserve','vendu') DEFAULT 'disponible',
  PRIMARY KEY (`id`),
  KEY `vendeur_id` (`vendeur_id`),
  CONSTRAINT `betail_ibfk_1` FOREIGN KEY (`vendeur_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `betail`
--

INSERT INTO `betail` (`vendeur_id`, `categorie`, `nom_betail`, `description`, `prix`, `photo`, `date_publication`, `statut`) VALUES
(1, 'bovins', 'Taureau Gobra', 'Magnifique taureau de race Gobra, parfait pour l\'élevage', 800000.00, 'assets/images/betail/taureau_gobra.jpeg', '2025-02-17 14:23:38', 'reserve'),
(1, 'ovins', 'Bélier Ladoum', 'Bélier Ladoum pure race, idéal pour la reproduction', 450000.00, 'assets/images/betail/belier_ladoum.jpeg', '2025-02-17 14:23:38', 'disponible'),
(1, 'caprins', 'Chèvre Djallonké', 'Chèvre Djallonké en bonne santé, bonne productrice de lait', 120000.00, 'assets/images/betail/chevre_djallonke.jpeg', '2025-02-17 14:23:38', 'disponible');

--
-- Table structure for table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(50) NOT NULL UNIQUE,
  `acheteur_id` int(11) NOT NULL,
  `betail_id` int(11) NOT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `statut` enum('en_attente','payee','annulee','livree') DEFAULT 'en_attente',
  `date_commande` datetime DEFAULT current_timestamp(),
  `date_paiement` datetime DEFAULT NULL,
  `date_livraison` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acheteur_id` (`acheteur_id`),
  KEY `betail_id` (`betail_id`),
  CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`acheteur_id`) REFERENCES `users` (`id`),
  CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`betail_id`) REFERENCES `betail` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `avis`
--

CREATE TABLE `avis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acheteur_id` int(11) NOT NULL,
  `vendeur_id` int(11) NOT NULL,
  `note` int(11) NOT NULL CHECK (`note` between 1 and 5),
  `commentaire` text DEFAULT NULL,
  `date_avis` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `acheteur_id` (`acheteur_id`),
  KEY `vendeur_id` (`vendeur_id`),
  CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`acheteur_id`) REFERENCES `users` (`id`),
  CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`vendeur_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `panier`
--

CREATE TABLE `panier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acheteur_id` int(11) NOT NULL,
  `betail_id` int(11) NOT NULL,
  `date_ajout` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `acheteur_betail` (`acheteur_id`, `betail_id`),
  KEY `betail_id` (`betail_id`),
  CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`acheteur_id`) REFERENCES `users` (`id`),
  CONSTRAINT `panier_ibfk_2` FOREIGN KEY (`betail_id`) REFERENCES `betail` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
