<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    $conn = connectDB();
    
    // Récupérer les paramètres de recherche
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $categorie = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';
    $prix = isset($_GET['prix']) ? trim($_GET['prix']) : '';
    
    // Construire la requête SQL de base
    $sql = "SELECT b.*, u.nom as vendeur_nom, 
            (SELECT AVG(note) FROM avis WHERE vendeur_id = b.vendeur_id) as note_vendeur
            FROM betail b
            JOIN users u ON b.vendeur_id = u.id
            WHERE 1=1";
    $params = [];
    $types = "";
    
    // Ajouter les conditions de recherche
    if (!empty($search)) {
        $sql .= " AND (
            b.nom_betail LIKE ? 
            OR b.description LIKE ? 
            OR b.categorie LIKE ? 
            OR u.nom LIKE ?
        )";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $types .= "ssss";
    }
    
    if (!empty($categorie)) {
        $sql .= " AND b.categorie = ?";
        $params[] = $categorie;
        $types .= "s";
    }
    
    // Gérer le filtre de prix
    if (!empty($prix)) {
        $prixRange = explode('-', $prix);
        if (count($prixRange) == 2) {
            $minPrix = (int)$prixRange[0];
            $maxPrix = $prixRange[1] === '+' ? PHP_FLOAT_MAX : (int)$prixRange[1];
            $sql .= " AND b.prix BETWEEN ? AND ?";
            $params[] = $minPrix;
            $params[] = $maxPrix;
            $types .= "dd";
        }
    }
    
    // Trier les résultats par pertinence si une recherche est effectuée
    if (!empty($search)) {
        $sql .= " ORDER BY 
            CASE 
                WHEN b.nom_betail LIKE ? THEN 1
                WHEN b.categorie LIKE ? THEN 2
                WHEN b.description LIKE ? THEN 3
                ELSE 4
            END,
            b.date_publication DESC";
        $exactMatch = "$search%";
        $params = array_merge($params, [$exactMatch, $exactMatch, $exactMatch]);
        $types .= "sss";
    } else {
        $sql .= " ORDER BY b.date_publication DESC";
    }
    
    // Préparer et exécuter la requête
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Récupérer les résultats
    $betails = [];
    while ($row = $result->fetch_assoc()) {
        // Ajouter un flag pour indiquer si l'utilisateur peut ajouter au panier
        $row['can_add_to_cart'] = isset($_SESSION['user_id']) && $_SESSION['role'] === 'acheteur';
        $betails[] = $row;
    }
    
    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Retourner les résultats en JSON
    echo json_encode($betails);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Une erreur est survenue lors de la recherche',
        'details' => $e->getMessage()
    ]);
} 