CREATE DATABASE IF NOT EXISTS betail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE betail_db;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('vendeur', 'acheteur', 'admin') NOT NULL,
    telephone VARCHAR(20),
    actif BOOLEAN DEFAULT TRUE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE betail (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendeur_id INT NOT NULL,
    categorie ENUM('bovins', 'ovins', 'caprins') NOT NULL,
    nom_betail VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    photo VARCHAR(255),
    date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendeur_id) REFERENCES users(id)
);

CREATE TABLE commandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    acheteur_id INT NOT NULL,
    betail_id INT NOT NULL,
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'paye', 'livre', 'annule') DEFAULT 'en_attente',
    methode_paiement ENUM('wave', 'orange_money'),
    FOREIGN KEY (acheteur_id) REFERENCES users(id),
    FOREIGN KEY (betail_id) REFERENCES betail(id)
);

CREATE TABLE avis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    acheteur_id INT NOT NULL,
    vendeur_id INT NOT NULL,
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    date_avis DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (acheteur_id) REFERENCES users(id),
    FOREIGN KEY (vendeur_id) REFERENCES users(id)
);

CREATE TABLE panier (
    id INT PRIMARY KEY AUTO_INCREMENT,
    acheteur_id INT NOT NULL,
    betail_id INT NOT NULL,
    quantite INT DEFAULT 1,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (acheteur_id) REFERENCES users(id),
    FOREIGN KEY (betail_id) REFERENCES betail(id)
);

-- Désactiver la vérification des clés étrangères
SET FOREIGN_KEY_CHECKS = 0;

-- Vider les tables
TRUNCATE TABLE panier;
TRUNCATE TABLE commandes;
TRUNCATE TABLE avis;
TRUNCATE TABLE betail;
TRUNCATE TABLE users;

-- Réactiver la vérification des clés étrangères
SET FOREIGN_KEY_CHECKS = 1;

-- Insertion des utilisateurs
INSERT INTO users (nom, email, password_hash, role, telephone) VALUES
('Amadou Diallo', 'amadou@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendeur', '771234567'),
('Fatou Sow', 'fatou@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'acheteur', '772345678'),
('Admin', 'admin@jurrgui.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '773456789');

-- Insertion des bétails
INSERT INTO betail (vendeur_id, nom_betail, categorie, description, prix, photo, date_publication) VALUES
(1, 'Taureau Gobra', 'bovins', 'Magnifique taureau de race Gobra, parfait pour l''élevage', 800000.00, 'assets/images/betail/taureau_gobra.jpeg', NOW()),
(1, 'Bélier Ladoum', 'ovins', 'Bélier Ladoum pure race, idéal pour la reproduction', 450000.00, 'assets/images/betail/belier_ladoum.jpeg', NOW()),
(1, 'Chèvre Djallonké', 'caprins', 'Chèvre Djallonké en bonne santé, bonne productrice de lait', 120000.00, 'assets/images/betail/chevre_djallonke.jpeg', NOW());
