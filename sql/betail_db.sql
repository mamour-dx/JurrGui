-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 08, 2025 at 11:43 AM
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
-- Table structure for table `betail`
--

CREATE TABLE `betail` (
  `id` int(11) NOT NULL,
  `vendeur_id` int(11) NOT NULL,
  `categorie` enum('bovins','ovins','caprins') NOT NULL,
  `nom_betail` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `date_publication` datetime DEFAULT current_timestamp(),
  `statut` enum('disponible','reserve','vendu') DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `betail`
--

INSERT INTO `betail` (`id`, `vendeur_id`, `categorie`, `nom_betail`, `description`, `prix`, `photo`, `date_publication`, `statut`) VALUES
(1, 1, 'bovins', 'Taureau Gobra', 'Magnifique taureau de race Gobra, parfait pour l\'élevage', 800000.00, 'assets/images/betail/taureau_gobra.jpeg', '2025-02-17 14:23:38', 'reserve'),
(2, 1, 'ovins', 'Bélier Ladoum', 'Bélier Ladoum pure race, idéal pour la reproduction', 450000.00, 'assets/images/betail/belier_ladoum.jpeg', '2025-02-17 14:23:38', 'disponible'),
(3, 1, 'caprins', 'Chèvre Djallonké', 'Chèvre Djallonké en bonne santé, bonne productrice de lait', 120000.00, 'assets/images/betail/chevre_djallonke.jpeg', '2025-02-17 14:23:38', 'disponible'),
(6, 10, 'bovins', 'Ladoum', 'Race pur de Ladoum', 100000.00, 'uploads/betail/67f4ef320ead4.png', '2025-04-08 09:41:06', 'disponible');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `betail`
--
ALTER TABLE `betail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendeur_id` (`vendeur_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `betail`
--
ALTER TABLE `betail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `betail`
--
ALTER TABLE `betail`
  ADD CONSTRAINT `betail_ibfk_1` FOREIGN KEY (`vendeur_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
