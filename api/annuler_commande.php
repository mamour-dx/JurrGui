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
    
    // Mettre à jour le statut de la commande
    $stmt = $conn->prepare("
        UPDATE commandes 
        SET statut = 'annule', 
            date_modification = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $data['commande_id']);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Commande annulée avec succès'
    ]);
    
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