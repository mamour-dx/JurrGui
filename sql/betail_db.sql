-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 17, 2025 at 03:05 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `betail_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `avis`
--

CREATE TABLE `avis` (
  `id` int(11) NOT NULL,
  `acheteur_id` int(11) NOT NULL,
  `vendeur_id` int(11) NOT NULL,
  `note` int(11) NOT NULL CHECK (`note` between 1 and 5),
  `commentaire` text DEFAULT NULL,
  `date_avis` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

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
  `statut` enum('disponible','reserve','vendu') DEFAULT 'disponible',
  `date_publication` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vendeur_id` (`vendeur_id`),
  CONSTRAINT `betail_ibfk_1` FOREIGN KEY (`vendeur_id`) REFERENCES `users` (`id`)
  `date_publication` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `betail`
--

INSERT INTO `betail` (`id`, `vendeur_id`, `categorie`, `nom_betail`, `description`, `prix`, `photo`, `date_publication`) VALUES
(7, 1, 'bovins', 'Taureau Gobra', 'Magnifique taureau de race Gobra, parfait pour l\'élevage', 800000.00, 'assets/images/betail/taureau_gobra.jpeg', '2025-02-17 13:27:25'),
(8, 1, 'ovins', 'Bélier Ladoum', 'Bélier Ladoum pure race, idéal pour la reproduction', 450000.00, 'assets/images/betail/belier_ladoum.jpeg', '2025-02-17 13:27:25'),
(9, 1, 'caprins', 'Chèvre Djallonké', 'Chèvre Djallonké en bonne santé, bonne productrice de lait', 120000.00, 'assets/images/betail/chevre_djallonke.jpeg', '2025-02-17 13:27:25');

-- --------------------------------------------------------

--
-- Table structure for table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acheteur_id` int(11) NOT NULL,
  `nom_livraison` varchar(100) NOT NULL,
  `telephone_livraison` varchar(20) NOT NULL,
  `adresse_livraison` text NOT NULL,
  `methode_paiement` enum('wave','orange_money','livraison') NOT NULL,
  `statut` enum('en_attente','paye','livre','annule') DEFAULT 'en_attente',
  `date_commande` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `acheteur_id` (`acheteur_id`),
  CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`acheteur_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `commande_articles`
--

CREATE TABLE `commande_articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commande_id` int(11) NOT NULL,
  `betail_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `vendeur_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `commande_id` (`commande_id`),
  KEY `betail_id` (`betail_id`),
  KEY `vendeur_id` (`vendeur_id`),
  CONSTRAINT `commande_articles_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  CONSTRAINT `commande_articles_ibfk_2` FOREIGN KEY (`betail_id`) REFERENCES `betail` (`id`),
  CONSTRAINT `commande_articles_ibfk_3` FOREIGN KEY (`vendeur_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `lien` varchar(255) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `lu` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `panier`
--

CREATE TABLE `panier` (
  `id` int(11) NOT NULL,
  `acheteur_id` int(11) NOT NULL,
  `betail_id` int(11) NOT NULL,
  `quantite` int(11) DEFAULT 1,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('vendeur','acheteur','admin') NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nom`, `email`, `password_hash`, `role`, `telephone`, `actif`, `date_creation`) VALUES
(1, 'Amadou Diallo', 'amadou@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendeur', '771234567', 1, '2025-02-17 13:27:25'),
(2, 'Fatou Sow', 'fatou@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'acheteur', '772345678', 1, '2025-02-17 13:27:25'),
(3, 'Admin', 'admin@jurrgui.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '773456789', 1, '2025-02-17 13:27:25'),
(4, 'Mamour Dieng', 'mamourdiengg@gmail.com', '$2y$10$53f8jQfL0uA0KjM5H6mjyeN4vGb2oh4wmZsX6FcBb56pc1nObRQQu', 'acheteur', '778171725', 1, '2025-02-17 13:29:08'),
(5, 'Mamour Dieng', 'mamourdieng@esp.sn', '$2y$10$cH0DBgxXwt4NKwEvB5zgAOGGEb1IPBASg8r7O1dNjiGlmNUYYkyiq', 'acheteur', '778171725', 1, '2025-02-17 13:40:03');

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commande_id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `methode_paiement` enum('wave','orange_money','livraison') NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `commande_id` (`commande_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `acheteur_id` (`acheteur_id`),
  ADD KEY `vendeur_id` (`vendeur_id`);

--
-- Indexes for table `betail`
--
ALTER TABLE `betail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendeur_id` (`vendeur_id`);

--
-- Indexes for table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `acheteur_id` (`acheteur_id`);

--
-- Indexes for table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `acheteur_id` (`acheteur_id`),
  ADD KEY `betail_id` (`betail_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `betail`
--
ALTER TABLE `betail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `panier`
--
ALTER TABLE `panier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`acheteur_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`vendeur_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `betail`
--
ALTER TABLE `betail`
  ADD CONSTRAINT `betail_ibfk_1` FOREIGN KEY (`vendeur_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`acheteur_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`acheteur_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `panier_ibfk_2` FOREIGN KEY (`betail_id`) REFERENCES `betail` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
