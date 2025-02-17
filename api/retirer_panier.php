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

// Vérifier si l'ID de l'article est fourni
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de l\'article non fourni'
    ]);
    exit;
}

try {
    $conn = connectDB();
    
    // Vérifier que l'article appartient bien à l'utilisateur
    $stmt = $conn->prepare("
        SELECT id 
        FROM panier 
        WHERE id = ? AND acheteur_id = ?
    ");
    $stmt->bind_param("ii", $data['id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Article non trouvé dans votre panier'
        ]);
        exit;
    }
    
    // Supprimer l'article du panier
    $stmt = $conn->prepare("DELETE FROM panier WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Article retiré du panier avec succès'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors du retrait de l\'article'
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 