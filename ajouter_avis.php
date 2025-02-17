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

if (!isset($data['vendeur_id']) || !isset($data['note']) || !isset($data['commentaire'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Données manquantes'
    ]);
    exit();
}

$conn = connectDB();

// Vérification que l'acheteur a bien effectué un achat auprès de ce vendeur
$stmt = $conn->prepare("
    SELECT COUNT(*) as has_bought
    FROM commandes c
    JOIN betail b ON c.betail_id = b.id
    WHERE c.acheteur_id = ? AND b.vendeur_id = ? AND c.statut = 'livre'
");
$stmt->bind_param("ii", $_SESSION['user_id'], $data['vendeur_id']);
$stmt->execute();
$result = $stmt->get_result();
$has_bought = $result->fetch_assoc()['has_bought'];

if (!$has_bought) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez avoir effectué un achat pour laisser un avis'
    ]);
    exit();
}

// Vérification si un avis existe déjà
$stmt = $conn->prepare("
    SELECT id FROM avis 
    WHERE acheteur_id = ? AND vendeur_id = ?
");
$stmt->bind_param("ii", $_SESSION['user_id'], $data['vendeur_id']);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous avez déjà laissé un avis pour ce vendeur'
    ]);
    exit();
}

// Ajout de l'avis
$stmt = $conn->prepare("
    INSERT INTO avis (acheteur_id, vendeur_id, note, commentaire)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param(
    "iiis",
    $_SESSION['user_id'],
    $data['vendeur_id'],
    $data['note'],
    $data['commentaire']
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Avis ajouté avec succès'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout de l\'avis'
    ]);
}

$conn->close();
