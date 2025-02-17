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

// Initialisation du panier si nécessaire
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Ajout ou mise à jour de la quantité
$betail_id = $data['betail_id'];
if (isset($_SESSION['panier'][$betail_id])) {
    $_SESSION['panier'][$betail_id]++;
} else {
    $_SESSION['panier'][$betail_id] = 1;
}

// Si c'est un achat direct
$redirect = isset($data['achat_direct']) && $data['achat_direct'];

echo json_encode([
    'success' => true,
    'cart_count' => count($_SESSION['panier']),
    'redirect' => $redirect ? 'paiement.php' : false
]);

$conn->close();
