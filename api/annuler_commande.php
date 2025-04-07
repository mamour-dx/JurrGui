<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action'
    ]);
    exit;
}

// Vérifier si l'ID de la commande est fourni
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['commande_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de la commande non fourni'
    ]);
    exit;
}

try {
    $conn = connectDB();
    
    // Vérifier que la commande existe, appartient à l'utilisateur et est en attente
    $stmt = $conn->prepare("
        SELECT id, statut 
        FROM commandes 
        WHERE id = ? AND acheteur_id = ?
    ");
    $stmt->bind_param("ii", $data['commande_id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $commande = $result->fetch_assoc();
    
    if (!$commande) {
        echo json_encode([
            'success' => false,
            'message' => 'Commande non trouvée'
        ]);
        exit;
    }
    
    if ($commande['statut'] !== 'en_attente') {
        echo json_encode([
            'success' => false,
            'message' => 'Cette commande ne peut plus être annulée'
        ]);
        exit;
    }
    
    // Démarrer une transaction
    $conn->begin_transaction();
    
    try {
        // Mettre à jour le statut de la commande
        $stmt = $conn->prepare("
            UPDATE commandes 
            SET statut = 'annule', 
                date_modification = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $data['commande_id']);
        $stmt->execute();
        
        // Récupérer les articles de la commande
        $stmt = $conn->prepare("
            SELECT betail_id 
            FROM commande_articles 
            WHERE commande_id = ?
        ");
        $stmt->bind_param("i", $data['commande_id']);
        $stmt->execute();
        $articles = $stmt->get_result();
        
        // Remettre les articles en stock
        while ($article = $articles->fetch_assoc()) {
            $stmt = $conn->prepare("
                UPDATE betail 
                SET statut = 'disponible' 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $article['betail_id']);
            $stmt->execute();
        }
        
        // Notifier les vendeurs
        $stmt = $conn->prepare("
            SELECT DISTINCT vendeur_id 
            FROM commande_articles 
            WHERE commande_id = ?
        ");
        $stmt->bind_param("i", $data['commande_id']);
        $stmt->execute();
        $vendeurs = $stmt->get_result();
        
        while ($vendeur = $vendeurs->fetch_assoc()) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (
                    user_id,
                    type,
                    message,
                    lien
                ) VALUES (?, 'commande_annulee', ?, ?)
            ");
            
            $message = "La commande #{$data['commande_id']} a été annulée";
            $lien = "detail_commande.php?id={$data['commande_id']}";
            
            $stmt->bind_param("iss", $vendeur['vendeur_id'], $message, $lien);
            $stmt->execute();
        }
        
        // Valider la transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Commande annulée avec succès'
        ]);
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'annulation de la commande'
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 