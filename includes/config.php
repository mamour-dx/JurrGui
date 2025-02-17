<?php
// Informations de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'betail_db');

// Configuration du site
define('SITE_NAME', 'Marché Bétail');
define('SITE_URL', 'http://localhost/JurrGui');
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Configuration des emails
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'votre-email@gmail.com');
define('MAIL_PASSWORD', 'votre-mot-de-passe-app');
define('MAIL_FROM', 'contact@marchebetail.com');
define('MAIL_FROM_NAME', 'Marché Bétail');

// Configuration OAuth Google
define('GOOGLE_CLIENT_ID', 'votre-client-id');
define('GOOGLE_CLIENT_SECRET', 'votre-client-secret');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/google_callback.php');

// Configuration des paiements
define('WAVE_API_KEY', 'votre-wave-api-key');
define('OM_API_KEY', 'votre-om-api-key');
define('COMMISSION_PERCENTAGE', 2); // 2% de commission sur les ventes

// Sécurité
define('JWT_SECRET', 'votre-secret-jwt');
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_LIFETIME', 86400); // 24 heures

// Messages d'erreur
define('ERROR_MESSAGES', [
    'auth' => [
        'invalid_credentials' => 'Email ou mot de passe incorrect',
        'email_exists' => 'Cet email est déjà utilisé',
        'weak_password' => 'Le mot de passe doit faire au moins 8 caractères',
        'account_inactive' => 'Votre compte a été désactivé'
    ],
    'upload' => [
        'size_exceeded' => 'La taille du fichier ne doit pas dépasser 5MB',
        'invalid_type' => 'Seuls les fichiers JPG, JPEG, PNG et WEBP sont acceptés',
        'upload_failed' => 'Erreur lors du téléchargement du fichier'
    ],
    'betail' => [
        'not_found' => 'Bétail non trouvé',
        'not_available' => 'Ce bétail n\'est plus disponible',
        'invalid_price' => 'Le prix doit être supérieur à 0'
    ],
    'payment' => [
        'invalid_method' => 'Méthode de paiement invalide',
        'insufficient_funds' => 'Fonds insuffisants',
        'transaction_failed' => 'La transaction a échoué'
    ]
]);

// Initialisation de la session
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_start(); 