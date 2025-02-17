<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    echo json_encode([
        'success' => false,
        'message' => 'Accès non autorisé'
    ]);
    exit();
}

require_once '../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['commande_id']) || !isset($data['methode_paiement']) || 
    !isset($data['transaction_id']) || !isset($data['montant'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Données de paiement incomplètes'
    ]);
    exit();
}

$conn = connectDB();
$conn->begin_transaction();

try {
    // Création des commandes pour chaque article
    foreach ($_SESSION['panier'] as $betail_id => $quantite) {
        $stmt = $conn->prepare("
            INSERT INTO commandes (
                commande_id, 
                acheteur_id, 
                betail_id, 
                quantite,
                montant,
                methode_paiement,
                transaction_id,
                statut
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente')
        ");
        
        $stmt->bind_param(
            "siiiiss",
            $data['commande_id'],
            $_SESSION['user_id'],
            $betail_id,
            $quantite,
            $data['montant'],
            $data['methode_paiement'],
            $data['transaction_id']
        );
        
        $stmt->execute();
    }
    
    // Envoi des notifications aux vendeurs
    foreach ($_SESSION['panier'] as $betail_id => $quantite) {
        $stmt = $conn->prepare("
            SELECT u.email, u.nom, b.nom_betail
            FROM betail b
            JOIN users u ON b.vendeur_id = u.id
            WHERE b.id = ?
        ");
        $stmt->bind_param("i", $betail_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $vendeur = $result->fetch_assoc();
        
        // Envoi de l'email au vendeur
        envoyerEmailVendeur(
            $vendeur['email'],
            $vendeur['nom'],
            $data['commande_id'],
            $vendeur['nom_betail'],
            $quantite,
            $data['montant']
        );
    }
    
    $conn->commit();
    
    // Vider le panier après succès
    $_SESSION['panier'] = [];
    
    echo json_encode([
        'success' => true,
        'commande_id' => $data['commande_id']
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du traitement de la commande'
    ]);
}

$conn->close();

function envoyerEmailVendeur($email, $nom_vendeur, $commande_id, $nom_betail, $quantite, $montant) {
    $sujet = "Nouvelle commande - $commande_id";
    
    $message = "
    Bonjour $nom_vendeur,
    
    Vous avez reçu une nouvelle commande !
    
    Détails de la commande :
    - Référence : $commande_id
    - Article : $nom_betail
    - Quantité : $quantite
    - Montant : " . number_format($montant, 0, ',', ' ') . " FCFA
    
    Veuillez vous connecter à votre compte pour gérer cette commande.
    
    Cordialement,
    L'équipe Marché Bétail
    ";
    
    $headers = "From: noreply@marchebetail.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    mail($email, $sujet, $message, $headers);
}
