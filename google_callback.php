<?php
require_once 'includes/header.php';
require_once 'includes/google_config.php';

use Google\Service\Oauth2;

if (isset($_GET['code'])) {
    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if (!isset($token['error'])) {
        $googleClient->setAccessToken($token['access_token']);
        
        // Get user data
        $google_oauth = new Oauth2($googleClient);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $google_id = $google_account_info->id;
        
        $conn = connectDB();
        
        // Vérifier si l'utilisateur existe
        $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
        $stmt->bind_param("ss", $google_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Utilisateur existant
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
        } else {
            // Nouvel utilisateur
            $stmt = $conn->prepare("INSERT INTO users (nom, email, google_id, role, password_hash) VALUES (?, ?, ?, 'acheteur', '')");
            $stmt->bind_param("sss", $name, $email, $google_id);
            $stmt->execute();
            
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['nom'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'acheteur';
        }
        
        $conn->close();
        
        header("Location: dashboard_acheteur.php");
        exit();
    }
}

// En cas d'erreur, redirection vers la page de connexion
header("Location: connexion.php");
exit();