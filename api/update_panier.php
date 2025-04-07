<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté en tant qu\'acheteur'
    ]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['betail_id']) || !isset($data['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres manquants'
    ]);
    exit();
}

require_once '../includes/db.php';
$conn = connectDB();

try {
    // Vérifier si l'article existe dans le panier
    $stmt = $conn->prepare("SELECT id, quantite FROM panier WHERE acheteur_id = ? AND betail_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $data['betail_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Article non trouvé dans le panier'
        ]);
        exit();
    }

    $panier = $result->fetch_assoc();
    
    if ($data['action'] === 'increase') {
        // Augmenter la quantité
        $stmt = $conn->prepare("UPDATE panier SET quantite = quantite + 1 WHERE id = ?");
    } else if ($data['action'] === 'decrease' && $panier['quantite'] > 1) {
        // Décrémenter la quantité si elle est supérieure à 1
        $stmt = $conn->prepare("UPDATE panier SET quantite = quantite - 1 WHERE id = ?");
    } else if ($data['action'] === 'decrease' && $panier['quantite'] === 1) {
        // Supprimer l'article si la quantité est 1
        $stmt = $conn->prepare("DELETE FROM panier WHERE id = ?");
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Action non valide'
        ]);
        exit();
    }
    
    $stmt->bind_param("i", $panier['id']);
    $stmt->execute();

    // Récupérer le nouveau nombre total d'articles dans le panier
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantite), 0) as total FROM panier WHERE acheteur_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'cart_count' => $total,
        'message' => $data['action'] === 'increase' ? 'Quantité augmentée' : 'Quantité diminuée'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la mise à jour du panier'
    ]);
} finally {
    $conn->close();
}
?>
