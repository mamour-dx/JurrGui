<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Accès non autorisé'
    ]);
    exit();
}

require_once '../../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['annonce_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID annonce manquant'
    ]);
    exit();
}

$conn = connectDB();

// Récupération des informations de l'annonce
$stmt = $conn->prepare("
    SELECT b.*, u.email, u.nom as vendeur_nom 
    FROM betail b 
    JOIN users u ON b.vendeur_id = u.id 
    WHERE b.id = ?
");
$stmt->bind_param("i", $data['annonce_id']);
$stmt->execute();
$annonce = $stmt->get_result()->fetch_assoc();

if (!$annonce) {
    echo json_encode([
        'success' => false,
        'message' => 'Annonce non trouvée'
    ]);
    exit();
}

// Suppression de l'image
if (file_exists('../../' . $annonce['photo'])) {
    unlink('../../' . $annonce['photo']);
}

// Suppression de l'annonce
$stmt = $conn->prepare("DELETE FROM betail WHERE id = ?");
$stmt->bind_param("i", $data['annonce_id']);

if ($stmt->execute()) {
    // Notification au vendeur
    $sujet = "Votre annonce a été supprimée";
    $message = "
    Bonjour {$annonce['vendeur_nom']},
    
    Votre annonce \"{$annonce['nom_betail']}\" a été supprimée par un administrateur.
    Si vous pensez qu'il s'agit d'une erreur, veuillez nous contacter.
    
    Cordialement,
    L'équipe Marché Bétail
    ";
    
    mail($annonce['email'], $sujet, $message);
    
    echo json_encode([
        'success' => true,
        'message' => 'Annonce supprimée avec succès'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression de l\'annonce'
    ]);
}

$conn->close();
