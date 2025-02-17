-- Réinitialisation des tables (dans l'ordre pour respecter les contraintes de clés étrangères)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE avis;
TRUNCATE TABLE panier;
TRUNCATE TABLE commandes;
TRUNCATE TABLE betail;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Insertion des utilisateurs d'abord (car référencés par d'autres tables)
INSERT INTO users (nom, email, password_hash, role, telephone, actif) VALUES
-- Mot de passe: Password123
('Amadou Diallo', 'amadou@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendeur', '771234567', 1),
('Fatou Sow', 'fatou@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'acheteur', '772345678', 1),
('Ousmane Ba', 'ousmane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendeur', '773456789', 1),
('Mariama Bah', 'mariama@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'acheteur', '774567890', 1),
('Admin User', 'admin@jurrgui.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '775678901', 1);

-- Insertion du bétail (après les users car dépend des vendeurs)
INSERT INTO betail (vendeur_id, categorie, nom_betail, description, prix, photo) VALUES
(1, 'bovins', 'Taureau Gobra', 'Magnifique taureau de race Gobra, 3 ans, excellent état de santé', 850000.00, 'assets/images/betail/taureau_gobra.jpg'),
(1, 'ovins', 'Bélier Ladoum', 'Bélier Ladoum pure race, 18 mois, parfait pour Tabaski', 450000.00, 'assets/images/betail/belier_ladoum.jpg'),
(3, 'caprins', 'Chèvre Djallonké', 'Chèvre Djallonké femelle, bonne laitière', 120000.00, 'assets/images/betail/chevre_djallonke.jpg'),
(3, 'bovins', 'Vache Maure', 'Vache laitière de race Maure, 4 ans, production 8L/jour', 950000.00, 'assets/images/betail/vache_maure.jpg'),
(1, 'ovins', 'Mouton Bali-Bali', 'Jeune mouton Bali-Bali, 12 mois, bien entretenu', 280000.00, 'assets/images/betail/mouton_balibali.jpg');

-- Insertion des commandes (après users et betail)
INSERT INTO commandes (acheteur_id, betail_id, statut, methode_paiement) VALUES
(2, 1, 'paye', 'wave'),
(4, 3, 'en_attente', 'orange_money'),
(2, 4, 'livre', 'wave'),
(4, 2, 'paye', 'orange_money');

-- Insertion des avis (après users)
INSERT INTO avis (acheteur_id, vendeur_id, note, commentaire) VALUES
(2, 1, 5, 'Excellent vendeur, bétail conforme à la description'),
(4, 3, 4, 'Très bon service, communication claire'),
(2, 3, 5, 'Transaction parfaite, je recommande'),
(4, 1, 4, 'Bonne expérience globale');

-- Insertion dans le panier (après users et betail)
INSERT INTO panier (acheteur_id, betail_id, quantite) VALUES
(2, 5, 1),
(4, 3, 1);

-- Message de confirmation
SELECT 'Données exemple insérées avec succès!' as message; 