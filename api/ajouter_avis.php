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

// Récupérer et valider les données
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['vendeur_id']) || !isset($data['note']) || !isset($data['commentaire'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Données manquantes'
    ]);
    exit;
}

// Valider la note
$note = intval($data['note']);
if ($note < 1 || $note > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'La note doit être comprise entre 1 et 5'
    ]);
    exit;
}

// Valider le commentaire
$commentaire = trim($data['commentaire']);
if (empty($commentaire)) {
    echo json_encode([
        'success' => false,
        'message' => 'Le commentaire ne peut pas être vide'
    ]);
    exit;
}

try {
    $conn = connectDB();
    
    // Vérifier que l'utilisateur a bien une commande livrée avec ce vendeur
    $stmt = $conn->prepare("
        SELECT c.id 
        FROM commandes c
        JOIN betail b ON c.betail_id = b.id
        WHERE c.acheteur_id = ? 
        AND b.vendeur_id = ? 
        AND c.statut = 'livre'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $_SESSION['user_id'], $data['vendeur_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Vous ne pouvez pas noter ce vendeur'
        ]);
        exit;
    }
    
    // Vérifier si l'utilisateur a déjà donné un avis
    $stmt = $conn->prepare("
        SELECT id 
        FROM avis 
        WHERE acheteur_id = ? AND vendeur_id = ?
    ");
    $stmt->bind_param("ii", $_SESSION['user_id'], $data['vendeur_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Vous avez déjà donné un avis pour ce vendeur'
        ]);
        exit;
    }
    
    // Ajouter l'avis
    $stmt = $conn->prepare("
        INSERT INTO avis (acheteur_id, vendeur_id, note, commentaire, date_creation) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiis", $_SESSION['user_id'], $data['vendeur_id'], $note, $commentaire);
    $stmt->execute();
    
    // Mettre à jour la note moyenne du vendeur
    $stmt = $conn->prepare("
        UPDATE users 
        SET note_moyenne = (
            SELECT AVG(note) 
            FROM avis 
            WHERE vendeur_id = ?
        )
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $data['vendeur_id'], $data['vendeur_id']);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Avis ajouté avec succès'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'ajout de l\'avis'
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 