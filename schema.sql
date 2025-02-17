CREATE DATABASE IF NOT EXISTS betail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE betail_db;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('vendeur', 'acheteur', 'admin') NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    google_id VARCHAR(255) DEFAULT NULL,
    telephone VARCHAR(20),
    actif BOOLEAN DEFAULT TRUE
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