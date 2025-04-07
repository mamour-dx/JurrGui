<?php
session_start();
require_once '../includes/db.php';

// Désactiver l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);

// Définir l'en-tête JSON
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté et est un acheteur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté en tant qu\'acheteur pour passer une commande'
    ]);
    exit();
}

// Récupérer les données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Données invalides'
    ]);
    exit();
}

// Vérifier les données requises
if (empty($data['articles']) || empty($data['nom_livraison']) || empty($data['telephone_livraison']) || empty($data['adresse_livraison']) || empty($data['methode_paiement'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Données manquantes'
    ]);
    exit();
}

// Valider la méthode de paiement
$methodes_paiement = ['wave', 'orange_money', 'virement'];
if (!in_array($data['methode_paiement'], $methodes_paiement)) {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode de paiement invalide'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = connectDB();

try {
    // Démarrer une transaction
    $conn->begin_transaction();

    // Créer la commande principale
    $stmt = $conn->prepare("
        INSERT INTO commandes (
            acheteur_id, 
            nom_livraison, 
            telephone_livraison, 
            adresse_livraison, 
            methode_paiement, 
            statut, 
            date_commande
        ) VALUES (?, ?, ?, ?, ?, 'en_attente', NOW())
    ");
    
    $stmt->bind_param(
        "issss", 
        $user_id,
        $data['nom_livraison'],
        $data['telephone_livraison'],
        $data['adresse_livraison'],
        $data['methode_paiement']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Erreur lors de la création de la commande: " . $conn->error);
    }
    
    $commande_id = $conn->insert_id;
    
    // Ajouter les articles à la commande
    foreach ($data['articles'] as $article) {
        // Vérifier si l'article existe et est disponible
        $stmt = $conn->prepare("
            SELECT prix, vendeur_id, statut 
            FROM betail 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $article['betail_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Article non trouvé");
        }
        
        $betail = $result->fetch_assoc();
        
        if ($betail['statut'] !== 'disponible') {
            throw new Exception("L'article n'est plus disponible");
        }
        
        // Ajouter l'article à la commande
        $stmt = $conn->prepare("
            INSERT INTO commande_articles (
                commande_id, 
                betail_id, 
                quantite, 
                prix_unitaire, 
                vendeur_id
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iiidi", 
            $commande_id,
            $article['betail_id'],
            $article['quantite'],
            $betail['prix'],
            $betail['vendeur_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de l'ajout des articles à la commande: " . $conn->error);
        }
        
        // Mettre à jour le statut du bétail
        $stmt = $conn->prepare("
            UPDATE betail 
            SET statut = 'reserve' 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $article['betail_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la mise à jour du statut du bétail: " . $conn->error);
        }
        
        // Supprimer l'article du panier
        $stmt = $conn->prepare("
            DELETE FROM panier 
            WHERE acheteur_id = ? AND betail_id = ?
        ");
        $stmt->bind_param("ii", $user_id, $article['betail_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la suppression du panier: " . $conn->error);
        }
    }
    
    // Valider la transaction
    $conn->commit();
    
    // Notifier les vendeurs
    $stmt = $conn->prepare("
        SELECT DISTINCT vendeur_id 
        FROM commande_articles 
        WHERE commande_id = ?
    ");
    $stmt->bind_param("i", $commande_id);
    $stmt->execute();
    $vendeurs = $stmt->get_result();
    
    while ($vendeur = $vendeurs->fetch_assoc()) {
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                user_id,
                type,
                message,
                lien
            ) VALUES (?, 'nouvelle_commande', ?, ?)
        ");
        
        $message = "Vous avez reçu une nouvelle commande #$commande_id";
        $lien = "detail_commande.php?id=$commande_id";
        
        $stmt->bind_param("iss", $vendeur['vendeur_id'], $message, $lien);
        $stmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'commande_id' => $commande_id,
        'message' => 'Commande créée avec succès'
    ]);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?> 