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

if (!isset($data['betail_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID du bétail manquant'
    ]);
    exit();
}

require_once '../includes/db.php';
$conn = connectDB();

try {
    // Vérification que le bétail existe
    $stmt = $conn->prepare("SELECT id, prix FROM betail WHERE id = ?");
    $stmt->bind_param("i", $data['betail_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Bétail non trouvé'
        ]);
        exit();
    }

    // Vérifier si l'article est déjà dans le panier
    $stmt = $conn->prepare("SELECT id, quantite FROM panier WHERE acheteur_id = ? AND betail_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $data['betail_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Mettre à jour la quantité
        $panier = $result->fetch_assoc();
        $stmt = $conn->prepare("UPDATE panier SET quantite = quantite + 1 WHERE id = ?");
        $stmt->bind_param("i", $panier['id']);
    } else {
        // Ajouter un nouvel article
        $stmt = $conn->prepare("INSERT INTO panier (acheteur_id, betail_id, quantite) VALUES (?, ?, 1)");
        $stmt->bind_param("ii", $_SESSION['user_id'], $data['betail_id']);
    }
    
    $stmt->execute();

    // Récupérer le nombre total d'articles dans le panier
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantite), 0) as total FROM panier WHERE acheteur_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'cart_count' => $total,
        'redirect' => isset($data['achat_direct']) && $data['achat_direct'] ? 'paiement.php' : false
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'ajout au panier'
    ]);
} finally {
    $conn->close();
}
