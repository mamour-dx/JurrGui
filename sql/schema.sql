-- Création de la base de données
CREATE DATABASE IF NOT EXISTS betail_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE betail_db;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('vendeur', 'acheteur', 'admin') NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    actif BOOLEAN DEFAULT TRUE,
    avatar VARCHAR(255) DEFAULT NULL
);

-- Table du bétail
CREATE TABLE betail (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendeur_id INT NOT NULL,
    categorie ENUM('bovins', 'ovins', 'caprins') NOT NULL,
    nom_betail VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    photo VARCHAR(255) NOT NULL,
    date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
    disponible BOOLEAN DEFAULT TRUE,
    vues INT DEFAULT 0,
    FOREIGN KEY (vendeur_id) REFERENCES users(id)
);

-- Table du panier
CREATE TABLE panier (
    id INT PRIMARY KEY AUTO_INCREMENT,
    acheteur_id INT NOT NULL,
    betail_id INT NOT NULL,
    quantite INT DEFAULT 1,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (acheteur_id) REFERENCES users(id),
    FOREIGN KEY (betail_id) REFERENCES betail(id)
);

-- Table des commandes
CREATE TABLE commandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reference VARCHAR(50) NOT NULL UNIQUE,
    acheteur_id INT NOT NULL,
    betail_id INT NOT NULL,
    vendeur_id INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    methode_paiement ENUM('wave', 'orange_money') NOT NULL,
    numero_paiement VARCHAR(20) NOT NULL,
    statut ENUM('en_attente', 'paye', 'livre', 'annule') DEFAULT 'en_attente',
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_paiement DATETIME DEFAULT NULL,
    date_livraison DATETIME DEFAULT NULL,
    FOREIGN KEY (acheteur_id) REFERENCES users(id),
    FOREIGN KEY (betail_id) REFERENCES betail(id),
    FOREIGN KEY (vendeur_id) REFERENCES users(id)
);

-- Table des avis
CREATE TABLE avis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    acheteur_id INT NOT NULL,
    vendeur_id INT NOT NULL,
    commande_id INT NOT NULL,
    note TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    date_avis DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (acheteur_id) REFERENCES users(id),
    FOREIGN KEY (vendeur_id) REFERENCES users(id),
    FOREIGN KEY (commande_id) REFERENCES commandes(id),
    UNIQUE KEY unique_avis (acheteur_id, commande_id)
);

-- Table des notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('paiement', 'livraison', 'avis', 'message') NOT NULL,
    message TEXT NOT NULL,
    lu BOOLEAN DEFAULT FALSE,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table des messages
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    expediteur_id INT NOT NULL,
    destinataire_id INT NOT NULL,
    message TEXT NOT NULL,
    lu BOOLEAN DEFAULT FALSE,
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediteur_id) REFERENCES users(id),
    FOREIGN KEY (destinataire_id) REFERENCES users(id)
);

-- Table des statistiques de vente
CREATE TABLE statistiques_ventes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    betail_id INT NOT NULL,
    vendeur_id INT NOT NULL,
    nombre_ventes INT DEFAULT 0,
    montant_total DECIMAL(10,2) DEFAULT 0,
    derniere_mise_a_jour DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (betail_id) REFERENCES betail(id),
    FOREIGN KEY (vendeur_id) REFERENCES users(id)
);

-- Création d'un utilisateur admin par défaut
-- Mot de passe : Admin123!
INSERT INTO users (nom, email, password_hash, role, telephone, actif)
VALUES ('Administrateur', 'admin@marchebetail.com', '$2y$10$8tUJWFKMWbGxVr0wOHBJZO.DAXUGQXyYJ4hE8MS0zOt4Ej.sBpVSi', 'admin', '+221770000000', 1);

-- Index pour optimiser les recherches
CREATE INDEX idx_betail_categorie ON betail(categorie);
CREATE INDEX idx_betail_prix ON betail(prix);
CREATE INDEX idx_betail_date ON betail(date_publication);
CREATE INDEX idx_commandes_date ON commandes(date_commande);
CREATE INDEX idx_avis_vendeur ON avis(vendeur_id);
CREATE INDEX idx_notifications_user ON notifications(user_id, lu);
CREATE INDEX idx_messages_users ON messages(expediteur_id, destinataire_id); 