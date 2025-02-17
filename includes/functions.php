<?php
require_once 'config.php';

/**
 * Connexion à la base de données
 * @return mysqli La connexion à la base de données
 */
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Erreur de connexion à la base de données: " . $conn->connect_error);
        die("Une erreur est survenue. Veuillez réessayer plus tard.");
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Nettoie une chaîne de caractères pour la base de données
 * @param mysqli $conn La connexion à la base de données
 * @param string $data La chaîne à nettoyer
 * @return string La chaîne nettoyée
 */
function clean($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

/**
 * Génère un token JWT
 * @param array $payload Les données à encoder
 * @return string Le token JWT
 */
function generateJWT($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode($payload);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool True si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * @param string $role Le rôle à vérifier
 * @return bool True si l'utilisateur a le rôle
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

/**
 * Redirige vers la page de connexion si l'utilisateur n'est pas connecté
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "/connexion.php");
        exit();
    }
}

/**
 * Vérifie si l'utilisateur a le rôle requis
 * @param string $role Le rôle requis
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: " . SITE_URL . "/index.php");
        exit();
    }
}

/**
 * Génère une référence unique pour une commande
 * @return string La référence générée
 */
function generateReference() {
    return 'CMD-' . time() . '-' . bin2hex(random_bytes(4));
}

/**
 * Formate un prix en FCFA
 * @param float $price Le prix à formater
 * @return string Le prix formaté
 */
function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' FCFA';
}

/**
 * Formate une date
 * @param string $date La date à formater
 * @return string La date formatée
 */
function formatDate($date) {
    return date('d/m/Y à H:i', strtotime($date));
}

/**
 * Upload une image
 * @param array $file Le fichier uploadé ($_FILES['input_name'])
 * @param string $directory Le dossier de destination
 * @return string|false Le chemin du fichier ou false si erreur
 */
function uploadImage($file, $directory = 'uploads') {
    // Vérification du type de fichier
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    // Vérification de la taille
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Création du dossier si nécessaire
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }
    
    // Génération d'un nom unique
    $fileName = uniqid() . '.' . $fileType;
    $targetPath = $directory . '/' . $fileName;
    
    // Upload du fichier
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    
    return false;
}

/**
 * Envoie un email
 * @param string $to Email du destinataire
 * @param string $subject Sujet de l'email
 * @param string $message Contenu de l'email
 * @return bool True si l'email a été envoyé
 */
function sendEmail($to, $subject, $message) {
    $headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Crée une notification
 * @param int $userId ID de l'utilisateur
 * @param string $type Type de notification
 * @param string $message Message de la notification
 * @return bool True si la notification a été créée
 */
function createNotification($userId, $type, $message) {
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $type, $message);
    $result = $stmt->execute();
    $conn->close();
    return $result;
}

/**
 * Récupère les notifications non lues d'un utilisateur
 * @param int $userId ID de l'utilisateur
 * @return array Les notifications non lues
 */
function getUnreadNotifications($userId) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND lu = 0 ORDER BY date_creation DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    return $result;
}

/**
 * Met à jour les statistiques de vente
 * @param int $betailId ID du bétail
 * @param int $vendeurId ID du vendeur
 * @param float $montant Montant de la vente
 */
function updateSalesStats($betailId, $vendeurId, $montant) {
    $conn = connectDB();
    
    // Vérifie si une entrée existe déjà
    $stmt = $conn->prepare("SELECT id FROM statistiques_ventes WHERE betail_id = ? AND vendeur_id = ?");
    $stmt->bind_param("ii", $betailId, $vendeurId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Mise à jour des statistiques existantes
        $stmt = $conn->prepare("UPDATE statistiques_ventes SET nombre_ventes = nombre_ventes + 1, montant_total = montant_total + ?, derniere_mise_a_jour = NOW() WHERE betail_id = ? AND vendeur_id = ?");
        $stmt->bind_param("dii", $montant, $betailId, $vendeurId);
    } else {
        // Création d'une nouvelle entrée
        $stmt = $conn->prepare("INSERT INTO statistiques_ventes (betail_id, vendeur_id, nombre_ventes, montant_total) VALUES (?, ?, 1, ?)");
        $stmt->bind_param("iid", $betailId, $vendeurId, $montant);
    }
    
    $stmt->execute();
    $conn->close();
}

/**
 * Vérifie si un acheteur peut laisser un avis
 * @param int $acheteurId ID de l'acheteur
 * @param int $vendeurId ID du vendeur
 * @param int $commandeId ID de la commande
 * @return bool True si l'acheteur peut laisser un avis
 */
function canLeaveReview($acheteurId, $vendeurId, $commandeId) {
    $conn = connectDB();
    
    // Vérifie si la commande existe et est livrée
    $stmt = $conn->prepare("SELECT id FROM commandes WHERE id = ? AND acheteur_id = ? AND vendeur_id = ? AND statut = 'livre'");
    $stmt->bind_param("iii", $commandeId, $acheteurId, $vendeurId);
    $stmt->execute();
    $commandeExists = $stmt->get_result()->num_rows > 0;
    
    // Vérifie si un avis existe déjà
    $stmt = $conn->prepare("SELECT id FROM avis WHERE commande_id = ? AND acheteur_id = ?");
    $stmt->bind_param("ii", $commandeId, $acheteurId);
    $stmt->execute();
    $avisExists = $stmt->get_result()->num_rows > 0;
    
    $conn->close();
    
    return $commandeExists && !$avisExists;
} 