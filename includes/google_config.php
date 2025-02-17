<?php
require_once 'vendor/autoload.php';

use Google\Client;

// Configuration Google OAuth
define('GOOGLE_CLIENT_ID', 'votre_client_id');
define('GOOGLE_CLIENT_SECRET', 'votre_client_secret');
define('GOOGLE_REDIRECT_URI', 'https://votre-domaine.com/google_callback.php');

// Initialisation du client Google
$googleClient = new Client();
$googleClient->setClientId(GOOGLE_CLIENT_ID);
$googleClient->setClientSecret(GOOGLE_CLIENT_SECRET);
$googleClient->setRedirectUri(GOOGLE_REDIRECT_URI);
$googleClient->addScope("email");
$googleClient->addScope("profile");

// URL d'authentification Google
$googleAuthUrl = $googleClient->createAuthUrl();