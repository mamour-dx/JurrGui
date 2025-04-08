<?php
// Désactiver l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);

// Définir l'en-tête JSON
header('Content-Type: application/json');

session_start();
require_once '../includes/db.php';

// Vérifier si l'utilisateur est connecté et est un acheteur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'acheteur') {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté en tant qu\'acheteur pour retirer un article du panier'
    ]);
    exit();
}

// Récupérer les données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Données invalides'
    ]);
    exit();
}

// Accepter soit id soit betail_id
$panier_id = null;
if (isset($data['id'])) {
    $panier_id = intval($data['id']);
} else if (isset($data['betail_id'])) {
    $panier_id = intval($data['betail_id']);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID de l\'article manquant'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Connexion à la base de données
    $conn = connectDB();

    // Si on a reçu betail_id, on cherche d'abord l'id du panier
    if (isset($data['betail_id'])) {
        $stmt = $conn->prepare("
            SELECT id 
            FROM panier 
            WHERE acheteur_id = ? AND betail_id = ?
        ");
        $stmt->bind_param("ii", $user_id, $panier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Article non trouvé dans le panier'
            ]);
            exit();
        }
        $panier_id = $result->fetch_assoc()['id'];
    }

    // Vérifier si l'article existe dans le panier
    $stmt = $conn->prepare("
        SELECT id, quantite 
        FROM panier 
        WHERE id = ? AND acheteur_id = ?
    ");
    $stmt->bind_param("ii", $panier_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Article non trouvé dans le panier'
        ]);
        exit();
    }

    $article = $result->fetch_assoc();
    $quantite = $article['quantite'];

    // Si la quantité est supérieure à 1, décrémenter
    if ($quantite > 1) {
        $stmt = $conn->prepare("
            UPDATE panier 
            SET quantite = quantite - 1 
            WHERE id = ? AND acheteur_id = ?
        ");
        $stmt->bind_param("ii", $panier_id, $user_id);
    } else {
        // Sinon, supprimer l'article
        $stmt = $conn->prepare("
            DELETE FROM panier 
            WHERE id = ? AND acheteur_id = ?
        ");
        $stmt->bind_param("ii", $panier_id, $user_id);
    }

    if ($stmt->execute()) {
        // Mettre à jour le compteur du panier
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(quantite), 0) as total 
            FROM panier 
            WHERE acheteur_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];

        echo json_encode([
            'success' => true,
            'message' => 'Article retiré du panier avec succès',
            'cart_count' => $total
        ]);
    } else {
        throw new Exception("Erreur lors de l'exécution de la requête");
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 