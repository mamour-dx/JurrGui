-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 07 avr. 2025 à 18:29
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `betail_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id` int(11) NOT NULL,
  `acheteur_id` int(11) NOT NULL,
  `vendeur_id` int(11) NOT NULL,
  `note` int(11) NOT NULL CHECK (`note` between 1 and 5),
  `commentaire` text DEFAULT NULL,
  `date_avis` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `betail`
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
-- Déchargement des données de la table `betail`
--

INSERT INTO `betail` (`id`, `vendeur_id`, `categorie`, `nom_betail`, `description`, `prix`, `photo`, `date_publication`, `statut`) VALUES
(1, 1, 'bovins', 'Taureau Gobra', 'Magnifique taureau de race Gobra, parfait pour l\'?levage', 800000.00, 'assets/images/betail/taureau_gobra.jpeg', '2025-02-17 14:23:38', 'reserve'),
(2, 1, 'ovins', 'B?lier Ladoum', 'B?lier Ladoum pure race, id?al pour la reproduction', 450000.00, 'assets/images/betail/belier_ladoum.jpeg', '2025-02-17 14:23:38', 'disponible'),
(3, 1, 'caprins', 'Ch?vre Djallonk?', 'Ch?vre Djallonk? en bonne sant?, bonne productrice de lait', 120000.00, 'assets/images/betail/chevre_djallonke.jpeg', '2025-02-17 14:23:38', 'disponible'),
(4, 4, 'ovins', 'Francois', 'ff', 500000.00, 'uploads/betail/67b3472bdee17.jpg', '2025-02-17 14:26:51', 'reserve'),
(5, 7, 'bovins', 'Francois', 'uiu', 9.00, 'uploads/betail/67f3b934e7400.png', '2025-04-07 11:38:28', 'reserve');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `acheteur_id` int(11) NOT NULL,
  `betail_id` int(11) NOT NULL,
  `date_commande` datetime DEFAULT current_timestamp(),
  `statut` enum('en_attente','paye','livre','annule') DEFAULT 'en_attente',
  `methode_paiement` enum('wave','orange_money','livraison') NOT NULL,
  `date_modification` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `acheteur_id`, `betail_id`, `date_commande`, `statut`, `methode_paiement`, `date_modification`) VALUES
(1, 9, 1, '2025-04-07 14:07:53', 'annule', 'wave', '2025-04-07 14:37:42'),
(2, 9, 5, '2025-04-07 14:07:53', 'livre', 'wave', '2025-04-07 16:23:39'),
(3, 9, 4, '2025-04-07 14:09:13', 'annule', 'livraison', '2025-04-07 14:38:58');

-- --------------------------------------------------------

--
-- Structure de la table `commande_articles`
--

CREATE TABLE `commande_articles` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `betail_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `vendeur_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

CREATE TABLE `panier` (
  `id` int(11) NOT NULL,
  `acheteur_id` int(11) NOT NULL,
  `betail_id` int(11) NOT NULL,
  `quantite` int(11) DEFAULT 1,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `panier`
--

INSERT INTO `panier` (`id`, `acheteur_id`, `betail_id`, `quantite`, `date_ajout`) VALUES
(6, 6, 5, 3, '2025-04-07 12:56:04');

-- --------------------------------------------------------

--
-- Structure de la table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `methode_paiement` enum('wave','orange_money','livraison') NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `email`, `password_hash`, `role`, `telephone`, `actif`, `date_creation`) VALUES
(1, 'Amadou Diallo', 'amadou@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendeur', '771234567', 1, '2025-02-17 14:23:38'),
(2, 'Fatou Sow', 'fatou@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'acheteur', '772345678', 1, '2025-02-17 14:23:38'),
(3, 'Admin', 'admin@jurrgui.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '773456789', 1, '2025-02-17 14:23:38'),
(4, 'mor ngom', 'mor@gmail.com', '$2y$10$3NmDF/mTjsASlbLOmoeFzuHB7mNADrfCjtGs6F8mTgpzilYCpvx.a', 'vendeur', '761417728', 1, '2025-02-17 14:25:15'),
(5, 'mor ngom', 'BIB@gmail.com', '$2y$10$zKxOBSmMd4DHVAW.viI5C.iviopZAt1anRPafC4io0yeudFGSbpH6', 'acheteur', '776666666', 1, '2025-02-19 05:11:25'),
(6, 'bib', 'b@gmail.com', '$2y$10$8FGVdfDLHUDxjmEtwWEfH.OGCKRnrWpdXlYb6cARlHBzS2iWfgCLS', 'acheteur', '775889711', 1, '2025-04-07 11:03:41'),
(7, 'Mouhamadou Abib Mouhamadou Abib DRAME', 'drameabib70@gmail.com', '$2y$10$BNQqWPShMfCE8x6Qpe5kp.8VdH1mKKxfzIgjcRK3yXZVL6WEPlUvO', 'vendeur', '761417728', 1, '2025-04-07 11:37:41'),
(8, 'Mouhamadou Abib Mouhamadou Abib DRAME', 'abib70@gmail.com', '$2y$10$moTXyqlVaufarfEtkeqSMusY22lLvRQeW1u6Ld.NjT84U61Qut3c6', 'vendeur', '776521497', 1, '2025-04-07 12:00:00'),
(9, 'd', 'd@gmail.com', '$2y$10$nIRR1m4kqtOCUJpvWkfFRuT6l/Ay349CAbyQHSJMkJss7WjNpkZZ6', 'acheteur', '779999999', 1, '2025-04-07 13:33:59');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `acheteur_id` (`acheteur_id`),
  ADD KEY `vendeur_id` (`vendeur_id`);

--
-- Index pour la table `betail`
--
ALTER TABLE `betail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendeur_id` (`vendeur_id`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `acheteur_id` (`acheteur_id`),
  ADD KEY `betail_id` (`betail_id`);

--
-- Index pour la table `commande_articles`
--
ALTER TABLE `commande_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `betail_id` (`betail_id`),
  ADD KEY `vendeur_id` (`vendeur_id`);

--
-- Index pour la table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `acheteur_id` (`acheteur_id`),
  ADD KEY `betail_id` (`betail_id`);

--
-- Index pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `betail`
--
ALTER TABLE `betail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `commande_articles`
--
ALTER TABLE `commande_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `panier`
--
ALTER TABLE `panier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`acheteur_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`vendeur_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `betail`
--
ALTER TABLE `betail`
  ADD CONSTRAINT `betail_ibfk_1` FOREIGN KEY (`vendeur_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`acheteur_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`betail_id`) REFERENCES `betail` (`id`);

--
-- Contraintes pour la table `commande_articles`
--
ALTER TABLE `commande_articles`
  ADD CONSTRAINT `commande_articles_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `commande_articles_ibfk_2` FOREIGN KEY (`betail_id`) REFERENCES `betail` (`id`),
  ADD CONSTRAINT `commande_articles_ibfk_3` FOREIGN KEY (`vendeur_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`acheteur_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `panier_ibfk_2` FOREIGN KEY (`betail_id`) REFERENCES `betail` (`id`);

--
-- Contraintes pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
