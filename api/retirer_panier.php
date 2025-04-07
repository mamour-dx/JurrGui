<?php
// Désactiver l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);

// Définir l'en-tête JSON
header('Content-Type: application/json');

session_start();
require_once '../includes/header.php';

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

// Vérifier si betail_id est présent
if (!isset($data['betail_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID du bétail manquant'
    ]);
    exit();
}

$betail_id = intval($data['betail_id']);
$user_id = $_SESSION['user_id'];

try {
    // Connexion à la base de données
    $conn = connectDB();

    // Vérifier si l'article existe dans le panier
    $stmt = $conn->prepare("
        SELECT id, quantite 
        FROM panier 
        WHERE acheteur_id = ? AND betail_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $betail_id);
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
    $panier_id = $article['id'];

    // Si la quantité est supérieure à 1, décrémenter
    if ($quantite > 1) {
        $stmt = $conn->prepare("
            UPDATE panier 
            SET quantite = quantite - 1 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $panier_id);
    } else {
        // Sinon, supprimer l'article
        $stmt = $conn->prepare("
            DELETE FROM panier 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $panier_id);
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