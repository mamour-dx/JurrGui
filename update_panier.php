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

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['article_id']) || !isset($data['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres manquants'
    ]);
    exit();
}

$article_id = $data['article_id'];
$action = $data['action'];

switch ($action) {
    case 'increase':
        if (isset($_SESSION['panier'][$article_id])) {
            $_SESSION['panier'][$article_id]++;
        }
        break;
        
    case 'decrease':
        if (isset($_SESSION['panier'][$article_id])) {
            $_SESSION['panier'][$article_id]--;
            if ($_SESSION['panier'][$article_id] <= 0) {
                unset($_SESSION['panier'][$article_id]);
            }
        }
        break;
        
    case 'remove':
        if (isset($_SESSION['panier'][$article_id])) {
            unset($_SESSION['panier'][$article_id]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Action non valide'
        ]);
        exit();
}

echo json_encode([
    'success' => true,
    'cart_count' => count($_SESSION['panier'])
]);
