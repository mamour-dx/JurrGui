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

if (!isset($data['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID utilisateur manquant'
    ]);
    exit();
}

$conn = connectDB();

// Vérification que l'utilisateur n'est pas un admin
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $data['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user['role'] === 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Impossible de bloquer un administrateur'
    ]);
    exit();
}

// Blocage de l'utilisateur
$stmt = $conn->prepare("UPDATE users SET actif = 0 WHERE id = ?");
$stmt->bind_param("i", $data['user_id']);

if ($stmt->execute()) {
    // Envoi d'un email à l'utilisateur
    $stmt = $conn->prepare("SELECT email, nom FROM users WHERE id = ?");
    $stmt->bind_param("i", $data['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    $sujet = "Votre compte a été bloqué";
    $message = "
    Bonjour {$user['nom']},
    
    Votre compte sur Marché Bétail a été bloqué par un administrateur.
    Si vous pensez qu'il s'agit d'une erreur, veuillez nous contacter.
    
    Cordialement,
    L'équipe Marché Bétail
    ";
    
    mail($user['email'], $sujet, $message);
    
    echo json_encode([
        'success' => true,
        'message' => 'Utilisateur bloqué avec succès'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du blocage de l\'utilisateur'
    ]);
}

$conn->close();
